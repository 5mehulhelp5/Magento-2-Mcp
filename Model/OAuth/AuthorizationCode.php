<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\OAuth;

use Freento\Mcp\Model\ResourceModel\OAuth\AuthorizationCode as AuthorizationCodeResource;
use Magento\Framework\Model\AbstractModel;

class AuthorizationCode extends AbstractModel
{
    public const ENTITY_ID = 'entity_id';
    public const CODE_HASH = 'code_hash';
    public const CLIENT_ID = 'client_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const STATE = 'state';
    public const CODE_CHALLENGE = 'code_challenge';
    public const CODE_CHALLENGE_METHOD = 'code_challenge_method';
    public const EXPIRES_AT = 'expires_at';
    public const CREATED_AT = 'created_at';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(AuthorizationCodeResource::class);
    }

    /**
     * Get code hash
     *
     * @return string|null
     */
    public function getCodeHash(): ?string
    {
        return $this->getData(self::CODE_HASH);
    }

    /**
     * Set code hash
     *
     * @param string $codeHash
     * @return $this
     */
    public function setCodeHash(string $codeHash): self
    {
        return $this->setData(self::CODE_HASH, $codeHash);
    }

    /**
     * Get client id
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->getData(self::CLIENT_ID);
    }

    /**
     * Set client id
     *
     * @param string $clientId
     * @return $this
     */
    public function setClientId(string $clientId): self
    {
        return $this->setData(self::CLIENT_ID, $clientId);
    }

    /**
     * Get admin user id
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int
    {
        $value = $this->getData(self::ADMIN_USER_ID);
        return $value !== null ? (int)$value : null;
    }

    /**
     * Set admin user id
     *
     * @param int $adminUserId
     * @return $this
     */
    public function setAdminUserId(int $adminUserId): self
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * Get state
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->getData(self::STATE);
    }

    /**
     * Set state
     *
     * @param string|null $state
     * @return $this
     */
    public function setState(?string $state): self
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * Get code challenge
     *
     * @return string|null
     */
    public function getCodeChallenge(): ?string
    {
        return $this->getData(self::CODE_CHALLENGE);
    }

    /**
     * Set code challenge
     *
     * @param string $codeChallenge
     * @return $this
     */
    public function setCodeChallenge(string $codeChallenge): self
    {
        return $this->setData(self::CODE_CHALLENGE, $codeChallenge);
    }

    /**
     * Get code challenge method
     *
     * @return string|null
     */
    public function getCodeChallengeMethod(): ?string
    {
        return $this->getData(self::CODE_CHALLENGE_METHOD);
    }

    /**
     * Set code challenge method
     *
     * @param string $codeChallengeMethod
     * @return $this
     */
    public function setCodeChallengeMethod(string $codeChallengeMethod): self
    {
        return $this->setData(self::CODE_CHALLENGE_METHOD, $codeChallengeMethod);
    }

    /**
     * Get expired at
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData(self::EXPIRES_AT);
    }

    /**
     * Set expired at
     *
     * @param string $expiresAt
     * @return $this
     */
    public function setExpiresAt(string $expiresAt): self
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    /**
     * Is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        if ($expiresAt === null) {
            return true;
        }
        return strtotime($expiresAt) < time();
    }
}
