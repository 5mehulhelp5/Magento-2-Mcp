<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Framework\DB\Select;

class ProductTierPriceResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $select->join(
            ['catalog_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'main_table.entity_id = catalog_product_entity.entity_id',
            []
        );

        $select->joinLeft(
            ['customer_group' => $this->resourceConnection->getTableName('customer_group')],
            'main_table.customer_group_id = customer_group.customer_group_id',
            []
        );
    }
}
