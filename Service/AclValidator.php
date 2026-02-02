<?php

declare(strict_types=1);

namespace Freento\Mcp\Service;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Api\Data\AclRoleInterface;
use Freento\Mcp\Api\Data\OAuthClientInterface;
use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as OAuthClientResource;
use Magento\Framework\Exception\NoSuchEntityException;

class AclValidator
{
    /**
     * @param AclRoleRepositoryInterface $aclRoleRepository
     * @param TokenGenerator $tokenGenerator
     * @param OAuthClientResource $oauthClientResource
     * @param ClientFactory $clientFactory
     */
    public function __construct(
        private AclRoleRepositoryInterface $aclRoleRepository,
        private TokenGenerator $tokenGenerator,
        private OAuthClientResource $oauthClientResource,
        private ClientFactory $clientFactory
    ) {
    }

    /**
     * Validate token and return OAuth client or null
     *
     * @param string $token
     * @return OAuthClientInterface|null
     */
    public function validateToken(string $token): ?OAuthClientInterface
    {
        $tokenHash = $this->tokenGenerator->hash($token);
        $clientData = $this->oauthClientResource->getByTokenHash($tokenHash);

        if ($clientData === null) {
            return null;
        }

        $client = $this->clientFactory->create();
        $client->setData($clientData);
        return $client;
    }

    /**
     * Check if client can use specific tool
     *
     * @param OAuthClientInterface $client
     * @param string $toolName
     * @return bool
     */
    public function canUseTool(OAuthClientInterface $client, string $toolName): bool
    {
        $roleId = $client->getRoleId();

        if ($roleId === null) {
            return false;
        }

        try {
            $role = $this->aclRoleRepository->getById($roleId);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        if ($role->getAccessType() === AclRoleInterface::ACCESS_TYPE_ALL) {
            return true;
        }

        $allowedTools = $this->aclRoleRepository->getRoleTools($roleId);
        return in_array($toolName, $allowedTools, true);
    }

    /**
     * Get list of allowed tools for OAuth client
     *
     * @param OAuthClientInterface $client
     * @return string[]|null Returns null if client has access to all tools
     */
    public function getAllowedTools(OAuthClientInterface $client): ?array
    {
        $roleId = $client->getRoleId();

        if ($roleId === null) {
            return [];
        }

        try {
            $role = $this->aclRoleRepository->getById($roleId);
        } catch (NoSuchEntityException $e) {
            return [];
        }

        if ($role->getAccessType() === AclRoleInterface::ACCESS_TYPE_ALL) {
            return null;
        }

        return $this->aclRoleRepository->getRoleTools($roleId);
    }
}
