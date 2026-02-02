<?php

declare(strict_types=1);

namespace Freento\Mcp\Api\Data;

interface OAuthClientInterface
{
    public const ENTITY_ID = 'entity_id';
    public const CLIENT_ID = 'client_id';
    public const CLIENT_SECRET = 'client_secret';
    public const NAME = 'name';
    public const ROLE_ID = 'role_id';
    public const IS_ACTIVE = 'is_active';
    public const TOKEN_HASH = 'token_hash';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const OTP_HASH = 'otp_hash';
    public const OTP_EXPIRES_AT = 'otp_expires_at';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get entity id
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Get client id
     *
     * @return string|null
     */
    public function getClientId(): ?string;

    /**
     * Get client secret
     *
     * @return string|null
     */
    public function getClientSecret(): ?string;

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Get role id
     *
     * @return int|null
     */
    public function getRoleId(): ?int;

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get token hash
     *
     * @return string|null
     */
    public function getTokenHash(): ?string;

    /**
     * Get admin user id
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Get otp hash
     *
     * @return string|null
     */
    public function getOtpHash(): ?string;

    /**
     * Get otp exores at
     *
     * @return string|null
     */
    public function getOtpExpiresAt(): ?string;
}
