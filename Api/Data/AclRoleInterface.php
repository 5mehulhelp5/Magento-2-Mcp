<?php

declare(strict_types=1);

namespace Freento\Mcp\Api\Data;

interface AclRoleInterface
{
    public const ROLE_ID = 'role_id';
    public const NAME = 'name';
    public const ACCESS_TYPE = 'access_type';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const ACCESS_TYPE_ALL = 'all';
    public const ACCESS_TYPE_SPECIFIED = 'specified';

    /**
     * Get role id
     *
     * @return int|null
     */
    public function getRoleId(): ?int;

    /**
     * Set role id
     *
     * @param int $roleId
     * @return self
     */
    public function setRoleId(int $roleId): self;

    /**
     * Get name
     *
     * @return string|null
     */

    public function getName(): ?string;

    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self;

    /**
     * Get access type
     *
     * @return string
     */

    public function getAccessType(): string;

    /**
     * Set access type
     *
     * @param string $accessType
     * @return self
     */
    public function setAccessType(string $accessType): self;

    /**
     * Get created at
     *
     * @return string|null
     */

    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self;
}
