<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Framework\DB\Select;

class TaxRuleResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $select->joinLeft(
            ['tax_calculation_rule' => $this->resourceConnection->getTableName('tax_calculation_rule')],
            'main_table.tax_calculation_rule_id = tax_calculation_rule.tax_calculation_rule_id',
            []
        );

        $select->joinLeft(
            ['tax_calculation_rate' => $this->resourceConnection->getTableName('tax_calculation_rate')],
            'main_table.tax_calculation_rate_id = tax_calculation_rate.tax_calculation_rate_id',
            []
        );

        $taxClassTable = $this->resourceConnection->getTableName('tax_class');
        $select->joinLeft(
            ['customer_tax_class' => $taxClassTable],
            'main_table.customer_tax_class_id = customer_tax_class.class_id',
            []
        );
        $select->joinLeft(
            ['product_tax_class' => $taxClassTable],
            'main_table.product_tax_class_id = product_tax_class.class_id',
            []
        );
    }
}
