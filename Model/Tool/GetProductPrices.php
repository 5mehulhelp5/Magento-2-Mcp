<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\ProductPriceResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetProductPrices extends AbstractTool
{
    /**
     * @param ProductPriceResource $productPriceResource
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly ProductPriceResource $productPriceResource,
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
        return $this->productPriceResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'product_price',
            table: 'catalog_product_index_price',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Product entity ID'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
                    column: 'catalog_product_entity.sku',
                    sortable: false,
                    description: 'Filter by SKU. Supports wildcards:'
                        . ' "ABC%" (starts with), "%ABC" (ends with), "%ABC%" (contains)'
                ),
                new Field(
                    name: 'customer_group_id',
                    type: 'integer',
                    description: 'Customer group ID',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'customer_group_code',
                    type: 'string',
                    column: 'customer_group.customer_group_code',
                    filter: false,
                    sortable: false,
                    description: 'Customer group name'
                ),
                new Field(
                    name: 'website_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Website ID',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'tax_class_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Tax class ID',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'price',
                    type: 'currency',
                    description: 'Base price',
                    allowAggregate: true
                ),
                new Field(
                    name: 'final_price',
                    type: 'currency',
                    description: 'Final calculated price',
                    allowAggregate: true
                ),
                new Field(
                    name: 'min_price',
                    type: 'currency',
                    description: 'Minimum price (for grouped/bundle products)',
                    allowAggregate: true
                ),
                new Field(
                    name: 'max_price',
                    type: 'currency',
                    description: 'Maximum price (for grouped/bundle products)',
                    allowAggregate: true
                ),
                new Field(
                    name: 'tier_price',
                    type: 'currency',
                    description: 'Best tier price for this customer group',
                    allowAggregate: true
                ),
            ],
            defaultLimit: 20,
            maxLimit: 100
        );
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'Query pre-calculated product prices from the price index (fast)',
            'View final prices per customer group and website',
            'Analyze price differences across customer groups',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me product prices',
            'Get prices for SKU ABC-123',
            'Compare prices across customer groups',
            'What are the price ranges for bundle products',
        ];
    }
}
