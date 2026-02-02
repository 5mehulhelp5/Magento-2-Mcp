<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\OAuth;

use Freento\Mcp\Service\OAuthService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * OAuth 2.0 Token endpoint
 * Exchanges authorization code for access token
 */
class Token implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var string|null
     */
    private ?string $authClientId = null;

    /**
     * @var string|null
     */
    private ?string $authClientSecret = null;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param OAuthService $oauthService
     */
    public function __construct(
        private RequestInterface $request,
        private JsonFactory $jsonFactory,
        private OAuthService $oauthService
    ) {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true; // OAuth token endpoint should not require CSRF validation
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        if ($this->request->getParam('grant_type') !== 'authorization_code') {
            return $result->setHttpResponseCode(400)->setData([
                'error' => 'unsupported_grant_type',
                'error_description' => 'Only authorization_code grant type is supported',
            ]);
        }

        $code = $this->request->getParam('code');
        if (!$code) {
            return $result->setHttpResponseCode(400)->setData([
                'error' => 'invalid_request',
                'error_description' => 'Missing required parameter: code)',
            ]);
        }

        // Get client credentials from POST params or Basic Auth header
        $clientId = $this->getClientId();
        // Validate required parameters
        if (!$clientId) {
            return $result->setHttpResponseCode(400)->setData([
                'error' => 'invalid_request',
                'error_description' => 'Missing required parameters: client_id',
            ]);
        }

        $clientSecret = $this->getClientSecret();
        $codeVerifier = $this->request->getParam('code_verifier');
        // Either client_secret or code_verifier must be provided
        if (!$clientSecret && !$codeVerifier) {
            return $result->setHttpResponseCode(400)->setData([
                'error' => 'invalid_request',
                'error_description' => 'Either client_secret or code_verifier is required',
            ]);
        }

        try {
            // Use PKCE flow for public clients, secret flow for confidential clients
            if ($codeVerifier) {
                $tokenData = $this->oauthService->exchangeCodeWithPkce(
                    $code,
                    $clientId,
                    $codeVerifier
                );
            } else {
                $tokenData = $this->oauthService->exchangeCodeWithSecret(
                    $code,
                    $clientId,
                    $clientSecret
                );
            }

            return $result->setData($tokenData);
        } catch (LocalizedException $e) {
            return $result->setHttpResponseCode(400)->setData([
                'error' => 'invalid_grant',
                'error_description' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get client id from request data
     *
     * @return string|null
     */
    private function getClientId(): ?string
    {
        $this->initClientAuthData();
        return $this->authClientId;
    }

    /**
     * Get client secret from request data
     *
     * @return string|null
     */
    private function getClientSecret(): ?string
    {
        $this->initClientAuthData();
        return $this->authClientSecret;
    }

    /**
     * Fill client id and secret from request data
     *
     * @return void
     */
    private function initClientAuthData(): void
    {
        if ($this->authClientId !== null && $this->authClientSecret !== null) {
            return;
        }

        $this->authClientId = $this->request->getParam('client_id');
        $this->authClientSecret = $this->request->getParam('client_secret');
        if ($this->authClientId && $this->authClientSecret) {
            return;
        }

        $authHeader = $this->request->getHeader('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- Required for Basic Auth header decoding per RFC 7617
        $decoded = base64_decode(substr($authHeader, 6));
        if ($decoded && str_contains($decoded, ':')) {
            [$this->authClientId, $this->authClientSecret] = explode(':', $decoded, 2);
        }
    }
}
