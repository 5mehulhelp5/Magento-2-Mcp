<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\OAuth;

use Freento\Mcp\Api\Data\OAuthClientInterface;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Magento\Framework\Model\AbstractModel;

class Client extends AbstractModel implements OAuthClientInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ClientResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        $value = parent::getId();
        return $value !== null ? (int)$value : null;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getClientSecret(): ?string
    {
        return $this->getData(self::CLIENT_SECRET);
    }

    /**
     * Set client secret
     *
     * @param string $secret
     * @return $this
     */
    public function setClientSecret(string $secret): self
    {
        return $this->setData(self::CLIENT_SECRET, $secret);
    }

    /**
     * @inheritDoc
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getRoleId(): ?int
    {
        $value = $this->getData(self::ROLE_ID);
        return $value !== null ? (int)$value : null;
    }

    /**
     * Set role id
     *
     * @param int|null $roleId
     * @return $this
     */
    public function setRoleId(?int $roleId): self
    {
        return $this->setData(self::ROLE_ID, $roleId);
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }

    /**
     * @inheritDoc
     */
    public function getTokenHash(): ?string
    {
        return $this->getData(self::TOKEN_HASH);
    }

    /**
     * Set token hash
     *
     * @param string|null $tokenHash
     * @return $this
     */
    public function setTokenHash(?string $tokenHash): self
    {
        return $this->setData(self::TOKEN_HASH, $tokenHash);
    }

    /**
     * @inheritDoc
     */
    public function getAdminUserId(): ?int
    {
        $value = $this->getData(self::ADMIN_USER_ID);
        return $value !== null ? (int)$value : null;
    }

    /**
     * Set admin user id
     *
     * @param int|null $adminUserId
     * @return $this
     */
    public function setAdminUserId(?int $adminUserId): self
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getOtpHash(): ?string
    {
        return $this->getData(self::OTP_HASH);
    }

    /**
     * Set otp hash
     *
     * @param string|null $otpHash
     * @return $this
     */
    public function setOtpHash(?string $otpHash): self
    {
        return $this->setData(self::OTP_HASH, $otpHash);
    }

    /**
     * @inheritDoc
     */
    public function getOtpExpiresAt(): ?string
    {
        return $this->getData(self::OTP_EXPIRES_AT);
    }

    /**
     * Set otp expires at
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setOtpExpiresAt(?string $expiresAt): self
    {
        return $this->setData(self::OTP_EXPIRES_AT, $expiresAt);
    }

    /**
     * Is otp expired
     *
     * @return bool
     */
    public function isOtpExpired(): bool
    {
        $expiresAt = $this->getOtpExpiresAt();
        if (!$expiresAt) {
            return true;
        }
        return strtotime($expiresAt) < time();
    }
}
