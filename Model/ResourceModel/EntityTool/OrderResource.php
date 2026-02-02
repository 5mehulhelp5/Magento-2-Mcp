<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Framework\DB\Select;

class OrderResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $paymentTable = $this->resourceConnection->getTableName('sales_order_payment');
        $select->joinLeft(
            ['payment' => $paymentTable],
            'main_table.entity_id = payment.parent_id',
            []
        );
    }
}
