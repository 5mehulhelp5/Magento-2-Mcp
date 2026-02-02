<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ProductResource extends AbstractResource
{
    private const EAV_ATTRIBUTES = [
        'name', 'price', 'cost', 'special_price', 'special_from_date', 'special_to_date', 'status', 'visibility'
    ];

    /** @var array<string, Attribute>|null */
    private ?array $attributes = null;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     * @param AttributeCollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly AttributeCollectionFactory $attributeCollectionFactory
    ) {
        parent::__construct($resourceConnection, $conditionApplier, $listResultFactory, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $attributes = $this->getAttributes();
        foreach (self::EAV_ATTRIBUTES as $attributeCode) {
            $field = $schema->getField($attributeCode);
            if ($field === null || !isset($attributes[$attributeCode])) {
                continue;
            }

            $attribute = $attributes[$attributeCode];
            $backendType = $attribute->getBackendType();

            if ($backendType === 'static') {
                continue;
            }

            $eavTable = $this->resourceConnection->getTableName("catalog_product_entity_$backendType");
            $alias = $this->getEavAttrTableAlias($attributeCode);

            $joinCondition = "main_table.entity_id = $alias.entity_id"
                . " AND $alias.attribute_id = {$attribute->getAttributeId()}"
                . " AND $alias.store_id = 0";

            $columns = $addJoinedFieldsToSelect ? [$attributeCode => "$alias.value"] : [];
            $select->joinLeft(
                [$alias => $eavTable],
                $joinCondition,
                $columns
            );
        }

        $select->distinct(true);
    }

    /**
     * Get eav attribute table alias
     *
     * @param string $attributeCode
     * @return string
     */
    private function getEavAttrTableAlias(string $attributeCode): string
    {
        return 'eav_' . $attributeCode;
    }

    /**
     * @inheritDoc
     * @throws \Zend_Db_Select_Exception
     */
    protected function applyFilters(Select $select, Schema $schema, array $arguments): array
    {
        $appliedFilters = parent::applyFilters($select, $schema, $arguments);
        $fromTables = $select->getPart('from');
        if (!$fromTables || count($fromTables) <= 1) {
            return $appliedFilters;
        }

        foreach ($arguments as $fieldName => $filterValue) {
            if (!in_array($fieldName, self::EAV_ATTRIBUTES)) {
                continue;
            }

            $field = $schema->getField($fieldName);
            if (!$field->isFilterable() || $field->getColumn() !== false) {
                continue;
            }

            $tableAlias = $this->getEavAttrTableAlias($fieldName);
            // Check if table is joined
            if (!isset($fromTables[$tableAlias])) {
                continue;
            }

            $filterCondition = $this->conditionApplier->buildCondition(
                $tableAlias . '.value',
                $filterValue,
                $field->getType()
            );

            if ($filterCondition) {
                $select->where($filterCondition);
                $appliedFilters[] = $this->getAppliedFilterResultString($fieldName, $filterValue);
            }
        }

        return $appliedFilters;
    }

    /**
     * Get applied filter as string to add to result array
     *
     * @param string $field
     * @param \Stringable|array $value
     * @return string
     */
    private function getAppliedFilterResultString(string $field, $value): string
    {
        $resultString = $field . ': ';
        if (is_array($value)) {
            $resultString .= json_encode($value);
        } else {
            $resultString .= $value;
        }

        return $resultString;
    }

    /**
     * Get attributes to join
     *
     * @return array<string, Attribute>
     */
    private function getAttributes(): array
    {
        if ($this->attributes === null) {
            $this->attributes = [];

            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('attribute_code', ['in' => self::EAV_ATTRIBUTES]);

            foreach ($collection as $attribute) {
                $this->attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }

        return $this->attributes;
    }
}
