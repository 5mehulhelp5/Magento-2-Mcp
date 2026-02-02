<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Api\Data\AclRoleInterface;
use Freento\Mcp\Model\ResourceModel\AclRole as AclRoleResource;
use Freento\Mcp\Model\ResourceModel\AclRole\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class AclRoleRepository implements AclRoleRepositoryInterface
{
    /**
     * @param AclRoleResource $resource
     * @param AclRoleFactory $roleFactory
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly AclRoleResource $resource,
        private readonly AclRoleFactory $roleFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getById(int $roleId): AclRoleInterface
    {
        $role = $this->roleFactory->create();
        $this->resource->load($role, $roleId);

        if (!$role->getRoleId()) {
            throw new NoSuchEntityException(__('ACL Role with id "%1" does not exist.', $roleId));
        }

        return $role;
    }

    /**
     * @inheritDoc
     */
    public function save(AclRoleInterface $role): AclRoleInterface
    {
        try {
            $this->resource->save($role);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save ACL Role: %1', $e->getMessage()));
        }

        return $role;
    }

    /**
     * @inheritDoc
     */
    public function delete(AclRoleInterface $role): bool
    {
        try {
            $this->resource->delete($role);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete ACL Role: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $roleId): bool
    {
        return $this->delete($this->getById($roleId));
    }

    /**
     * @inheritDoc
     */
    public function getList(): array
    {
        $collection = $this->collectionFactory->create();
        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getRoleTools(int $roleId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('freento_mcp_acl_role_tool');

        $select = $connection->select()
            ->from($tableName, ['tool_name'])
            ->where('role_id = ?', $roleId);

        return $connection->fetchCol($select);
    }

    /**
     * @inheritDoc
     */
    public function saveRoleTools(int $roleId, array $toolNames): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('freento_mcp_acl_role_tool');

        // Delete existing tools
        $connection->delete($tableName, ['role_id = ?' => $roleId]);

        // Insert new tools
        if (!empty($toolNames)) {
            $data = [];
            foreach ($toolNames as $toolName) {
                $data[] = [
                    'role_id' => $roleId,
                    'tool_name' => $toolName
                ];
            }
            $connection->insertMultiple($tableName, $data);
        }
    }
}
