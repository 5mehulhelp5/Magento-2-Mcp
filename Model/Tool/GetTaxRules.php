<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\TaxRuleResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetTaxRules extends AbstractTool
{
    /**
     * @param TaxRuleResource $taxRuleResource
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly TaxRuleResource $taxRuleResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper,
        DateTimeHelper $dateTimeHelper
    ) {
        parent::__construct($resultFactory, $stringHelper, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getResource(): AbstractResource
    {
        return $this->taxRuleResource;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'tax_rule',
            table: 'tax_calculation',
            fields: [
                new Field(
                    name: 'tax_calculation_id',
                    type: 'integer',
                    description: 'Tax calculation record ID'
                ),
                new Field(
                    name: 'tax_calculation_rule_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Tax rule ID'
                ),
                new Field(
                    name: 'rule_code',
                    type: 'string',
                    column: 'tax_calculation_rule.code',
                    filter: false,
                    sortable: false,
                    description: 'Tax rule name/code'
                ),
                new Field(
                    name: 'rule_priority',
                    type: 'integer',
                    column: 'tax_calculation_rule.priority',
                    filter: false,
                    description: 'Tax rule priority (lower = applied first)'
                ),
                new Field(
                    name: 'customer_tax_class_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Customer tax class ID',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'customer_tax_class_name',
                    type: 'string',
                    column: 'customer_tax_class.class_name',
                    filter: false,
                    sortable: false,
                    description: 'Customer tax class name'
                ),
                new Field(
                    name: 'product_tax_class_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Product tax class ID',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'product_tax_class_name',
                    type: 'string',
                    column: 'product_tax_class.class_name',
                    filter: false,
                    sortable: false,
                    description: 'Product tax class name'
                ),
                new Field(
                    name: 'rate_code',
                    type: 'string',
                    column: 'tax_calculation_rate.code',
                    filter: false,
                    sortable: false,
                    description: 'Tax rate identifier code'
                ),
                new Field(
                    name: 'rate',
                    type: 'numeric',
                    column: 'tax_calculation_rate.rate',
                    description: 'Tax rate percentage (e.g. 8.25 means 8.25%)',
                    allowAggregate: true
                ),
                new Field(
                    name: 'tax_country_id',
                    type: 'string',
                    column: 'tax_calculation_rate.tax_country_id',
                    sortable: false,
                    description: 'Country code for tax rate (e.g. US, GB, DE)',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'tax_region_id',
                    type: 'integer',
                    column: 'tax_calculation_rate.tax_region_id',
                    sortable: false,
                    description: 'Region/state ID for tax rate (0 = all regions)',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'tax_postcode',
                    type: 'string',
                    column: 'tax_calculation_rate.tax_postcode',
                    sortable: false,
                    description: 'Postcode or postcode pattern for tax rate (* = all)'
                ),
                new Field(
                    name: 'calculate_subtotal',
                    type: 'integer',
                    column: 'tax_calculation_rule.calculate_subtotal',
                    filter: false,
                    sortable: false,
                    description: 'Whether to calculate on subtotal (1 = yes)'
                ),
            ],
            defaultLimit: 50,
            maxLimit: 200
        );
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'Find applicable tax rates for customer/product/location combinations',
            'To calculate final price with tax: 1) get customer tax class from customer group,'
                . ' 2) get product tax class from product, 3) filter this tool by both class IDs'
                . ' and country/region to find the rate',
            'Analyze tax configuration across regions and product types',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show all tax rules',
            'Find tax rate for US customers',
            'What tax applies to Taxable Goods product class?',
            'Show tax rates by country',
            'What is the tax rate for customer_tax_class_id=3 and product_tax_class_id=2 in the US?',
        ];
    }
}
