<?php

declare(strict_types=1);

namespace Freento\Mcp\Service;

use Freento\Mcp\Model\OAuth\AuthorizationCodeFactory;
use Freento\Mcp\Model\OAuth\Client;
use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\AuthorizationCode as AuthorizationCodeResource;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;

class OAuthService
{
    private const CODE_EXPIRATION_SECONDS = 600; // 10 minutes

    /**
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     * @param AuthorizationCodeFactory $authCodeFactory
     * @param AuthorizationCodeResource $authCodeResource
     * @param TokenGenerator $tokenGenerator
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private ClientFactory $clientFactory,
        private ClientResource $clientResource,
        private AuthorizationCodeFactory $authCodeFactory,
        private AuthorizationCodeResource $authCodeResource,
        private TokenGenerator $tokenGenerator,
        private EncryptorInterface $encryptor
    ) {
    }

    /**
     * Get decrypted secret
     *
     * @param int $entityId
     * @return string|null
     * @throws LocalizedException
     */
    public function getDecryptedSecret(int $entityId): ?string
    {
        $data = $this->clientResource->getByEntityId($entityId);
        if ($data === null || empty($data['client_secret'])) {
            return null;
        }

        return $this->encryptor->decrypt($data['client_secret']);
    }

    /**
     * Get client by client id
     *
     * @param string $clientId
     * @return Client|null
     * @throws LocalizedException
     */
    public function getClientByClientId(string $clientId): ?Client
    {
        $data = $this->clientResource->getByClientId($clientId);
        if ($data === null) {
            return null;
        }

        $client = $this->clientFactory->create();
        $client->setData($data);
        return $client;
    }

    /**
     * Create authorization code
     *
     * @param string $clientId
     * @param int $adminUserId
     * @param string|null $state
     * @param string|null $codeChallenge
     * @param string|null $codeChallengeMethod
     * @return string Authorization code (plain)
     * @throws LocalizedException
     * @throws AlreadyExistsException
     */
    public function createAuthorizationCode(
        string $clientId,
        int $adminUserId,
        ?string $state = null,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null
    ): string {
        // Clean up expired codes
        $this->authCodeResource->deleteExpired();

        $code = $this->tokenGenerator->generate();
        $codeHash = $this->tokenGenerator->hash($code);

        $authCode = $this->authCodeFactory->create();
        $authCode->setCodeHash($codeHash);
        $authCode->setClientId($clientId);
        $authCode->setAdminUserId($adminUserId);
        $authCode->setState($state);
        $authCode->setExpiresAt(date('Y-m-d H:i:s', time() + self::CODE_EXPIRATION_SECONDS));

        // Store PKCE code_challenge if provided
        if ($codeChallenge !== null) {
            $authCode->setCodeChallenge($codeChallenge);
            $authCode->setCodeChallengeMethod($codeChallengeMethod ?? 'plain');
        }

        $this->authCodeResource->save($authCode);

        return $code;
    }

    /**
     * Exchange authorization code for token using client_secret (confidential clients)
     *
     * @param string $code
     * @param string $clientId
     * @param string $clientSecret
     * @return array{access_token: string, token_type: string}
     * @throws LocalizedException
     */
    public function exchangeCodeWithSecret(
        string $code,
        string $clientId,
        string $clientSecret
    ): array {
        $client = $this->validateClient($clientId);

        $storedSecret = $this->encryptor->decrypt($client->getClientSecret());
        if ($storedSecret !== $clientSecret) {
            throw new LocalizedException(__('Invalid client_secret'));
        }

        $authCode = $this->validateAuthorizationCode($code, $clientId);

        return $this->createAccessToken($authCode, $client);
    }

    /**
     * Exchange authorization code for token using PKCE (public clients)
     *
     * @param string $code
     * @param string $clientId
     * @param string $codeVerifier
     * @return array{access_token: string, token_type: string}
     * @throws LocalizedException
     */
    public function exchangeCodeWithPkce(
        string $code,
        string $clientId,
        string $codeVerifier
    ): array {
        $client = $this->validateClient($clientId);
        $authCode = $this->validateAuthorizationCode($code, $clientId);

        $storedCodeChallenge = $authCode->getCodeChallenge();
        if ($storedCodeChallenge === null) {
            throw new LocalizedException(__('PKCE was not initiated for this authorization'));
        }

        if (!$this->validatePkceChallenge($codeVerifier, $storedCodeChallenge, $authCode->getCodeChallengeMethod())) {
            throw new LocalizedException(__('Invalid code_verifier'));
        }

        return $this->createAccessToken($authCode, $client);
    }

    /**
     * Validate client
     *
     * @param string $clientId
     * @return Client
     * @throws LocalizedException
     */
    private function validateClient(string $clientId): Client
    {
        $client = $this->getClientByClientId($clientId);
        if ($client === null || !$client->isActive()) {
            throw new LocalizedException(__('Invalid client_id'));
        }

        return $client;
    }

    /**
     * Validate authorization code
     *
     * @param string $code
     * @param string $clientId
     * @return \Freento\Mcp\Model\OAuth\AuthorizationCode
     * @throws LocalizedException
     */
    private function validateAuthorizationCode(
        string $code,
        string $clientId
    ): \Freento\Mcp\Model\OAuth\AuthorizationCode {
        $codeHash = $this->tokenGenerator->hash($code);
        $codeData = $this->authCodeResource->getByCodeHash($codeHash);

        if ($codeData === null) {
            throw new LocalizedException(__('Invalid authorization code'));
        }

        $authCode = $this->authCodeFactory->create();
        $authCode->setData($codeData);

        if ($authCode->isExpired()) {
            $this->authCodeResource->deleteByCodeHash($codeHash);
            throw new LocalizedException(__('Authorization code expired'));
        }

        if ($authCode->getClientId() !== $clientId) {
            throw new LocalizedException(__('Client mismatch'));
        }

        // Delete used authorization code
        $this->authCodeResource->deleteByCodeHash($codeHash);

        return $authCode;
    }

    /**
     * Create access token
     *
     * @param \Freento\Mcp\Model\OAuth\AuthorizationCode $authCode
     * @param Client $client
     * @return array{access_token: string, token_type: string}
     * @throws LocalizedException
     */
    private function createAccessToken(
        \Freento\Mcp\Model\OAuth\AuthorizationCode $authCode,
        Client $client
    ): array {
        $accessToken = $this->tokenGenerator->generate();
        $tokenHash = $this->tokenGenerator->hash($accessToken);

        // Store token directly in the OAuth client (1:1 relationship)
        $this->clientResource->updateToken(
            (int)$client->getEntityId(),
            $tokenHash,
            $authCode->getAdminUserId()
        );

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Validate PKCE code_verifier against stored code_challenge
     *
     * @param string $codeVerifier
     * @param string $codeChallenge
     * @param string|null $codeChallengeMethod
     * @return bool
     */
    private function validatePkceChallenge(
        string $codeVerifier,
        string $codeChallenge,
        ?string $codeChallengeMethod
    ): bool {
        if ($codeChallengeMethod === 'S256') {
            // S256: BASE64URL(SHA256(code_verifier)) == code_challenge
            $hash = hash('sha256', $codeVerifier, true);
            $computedChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
            return hash_equals($codeChallenge, $computedChallenge);
        }

        // plain: code_verifier == code_challenge
        return hash_equals($codeChallenge, $codeVerifier);
    }
}
