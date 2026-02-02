<?php

declare(strict_types=1);

namespace Freento\Mcp\Ui\DataProvider;

use Freento\Mcp\Model\ResourceModel\AclRole\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class AclRoleListingDataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resourceConnection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $data = parent::getData();

        // Add tools_count and clients_count for each role
        $connection = $this->resourceConnection->getConnection();
        $toolsTable = $this->resourceConnection->getTableName('freento_mcp_acl_role_tool');
        $clientsTable = $this->resourceConnection->getTableName('freento_mcp_oauth_client');

        foreach ($data['items'] as &$item) {
            $roleId = $item['role_id'];

            // Tools count
            if ($item['access_type'] === 'all') {
                $item['tools_count'] = '—';
            } else {
                $select = $connection->select()
                    ->from($toolsTable, ['COUNT(*)'])
                    ->where('role_id = ?', $roleId);
                $item['tools_count'] = (int)$connection->fetchOne($select);
            }

            // Clients count (OAuth clients with this role that have tokens)
            $select = $connection->select()
                ->from($clientsTable, ['COUNT(*)'])
                ->where('role_id = ?', $roleId)
                ->where('token_hash IS NOT NULL');
            $item['users_count'] = (int)$connection->fetchOne($select);
        }

        return $data;
    }
}
