<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\Category\CategoryResourceProxy;
use Freento\Mcp\Model\ToolResultFactory;

class GetCategories extends AbstractTool
{
    /**
     * @param CategoryResourceProxy $categoryResource
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly CategoryResourceProxy $categoryResource,
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
        return $this->categoryResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'category',
            table: 'catalog_category_entity',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Category entity ID'
                ),
                new Field(
                    name: 'parent_id',
                    type: 'integer',
                    description: 'Parent category ID'
                ),
                new Field(
                    name: 'path',
                    type: 'string',
                    description: 'Category path (e.g., "1/2/3"). Supports wildcards:'
                                . ' "1/2/%" (starts with), "%/5" (ends with)'
                ),
                new Field(
                    name: 'level',
                    type: 'integer',
                    description: 'Category depth level (0 = root, 1 = default category, 2 = top-level)'
                ),
                new Field(
                    name: 'position',
                    type: 'integer',
                    description: 'Sort position within parent'
                ),
                new Field(
                    name: 'children_count',
                    type: 'integer',
                    description: 'Number of direct children'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    description: 'Filter categories created on or after/before this date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date',
                    description: 'Filter categories updated on or after/before this date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'name',
                    type: 'string',
                    column: false,
                    filter: true,
                    sortable: false
                ),
                new Field(
                    name: 'is_active',
                    type: 'int',
                    column: false,
                    filter: true,
                    sortable: false,
                    description: 'Filter by active status (1 = enabled, 0 = disabled)'
                ),
                new Field(
                    name: 'include_in_menu',
                    type: 'int',
                    column: false,
                    filter: true,
                    sortable: false,
                    description: 'Filter by menu inclusion (1 = included, 0 = excluded)'
                ),
            ],
            defaultLimit: 50,
            maxLimit: 200
        );
    }

    /**
     * @inheritDoc
     */
    protected function getExtraSchemaProperties(): array
    {
        return [
            'store_id' => [
                'type' => 'integer',
                'description' => 'Store view ID. Filters categories by the store\'s root category'
                    . ' and returns store-specific attribute values (name, is_active, etc.).'
                    . ' Without this parameter, global (default) values are returned.',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'Search for categories by ID, name, or path',
            'Filter categories by active status or menu inclusion',
            'Analyze category tree structure',
            'Filter by store view to get store-specific attribute values and store-scoped categories',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me all categories',
            'Get active top-level categories',
            'Find categories with name containing "shoes"',
            'List categories for store view 1',
            'Get categories at level 2',
        ];
    }
}
