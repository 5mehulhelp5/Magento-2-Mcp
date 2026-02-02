<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\DB\Select;

class CouponResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $ruleTable = $this->resourceConnection->getTableName('salesrule');
        $select->joinLeft(
            ['rule' => $ruleTable],
            'main_table.rule_id = rule.rule_id',
            []
        );
    }
}
