<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\ProductResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetProducts extends AbstractTool
{
    /**
     * @param ProductResource $productResource
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly ProductResource $productResource,
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
        return $this->productResource;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'product',
            table: 'catalog_product_entity',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Product entity ID'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
                    description: 'Filter by SKU. Supports wildcards:'
                                . ' "ABC%" (starts with), "%ABC" (ends with), "%ABC%" (contains)'
                ),
                new Field(
                    name: 'type_id',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Filter by product type (simple, configurable, grouped, bundle, virtual, downloadable)'
                ),
                new Field(
                    name: 'attribute_set_id',
                    type: 'integer',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Filter by attribute set ID'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Filter products created on or after/before this date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date',
                    description: 'Filter products updated on or after/before this date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'visibility',
                    type: 'int',
                    column: false,
                    filter: true,
                    sortable: false,
                    description: 'Filter by visibility (1 = not visible, 2 = catalog, 3 = search, 4 = catalog+search)'
                ),
                new Field(
                    name: 'status',
                    type: 'int',
                    column: false,
                    filter: true,
                    sortable: false,
                    description: 'Filter by status (1 = enabled, 2 = disabled)'
                ),
                new Field(
                    name: 'name',
                    type: 'string',
                    column: false,
                    filter: true,
                    sortable: false
                ),
                new Field(
                    name: 'price',
                    type: 'currency',
                    column: false,
                    filter: true,
                    sortable: false
                ),
                new Field(
                    name: 'cost',
                    type: 'currency',
                    column: false,
                    filter: true,
                    sortable: false
                ),
                new Field(
                    name: 'special_price',
                    type: 'currency',
                    column: false,
                    filter: true,
                    sortable: false
                ),
                new Field(
                    name: 'special_from_date',
                    type: 'date',
                    column: false,
                    filter: true,
                    sortable: false
                ),
                new Field(
                    name: 'special_to_date',
                    type: 'date',
                    column: false,
                    filter: true,
                    sortable: false
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
            'Search for products by SKU, ID, or dates',
            'Filter products by any attribute (e.g., color, size, manufacturer)',
            'Analyze product catalog data',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent products',
            'Get products with SKU containing ABC',
            'Find products updated in the last week',
            'List products where color is red',
            'Get products with manufacturer = Nike',
        ];
    }
}
