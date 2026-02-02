<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResult;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Abstract Resource Model for entity list tools.
 *
 * Handles database operations for list tools:
 * - Query building (SELECT, WHERE, ORDER BY, LIMIT)
 * - Filter application using ConditionApplier
 * - Pagination
 *
 * ## Creating a Resource
 *
 * 1. Extend this class
 * 2. Override extension points as needed
 *
 * ```php
 * class OrderResource extends AbstractResource
 * {
 *     // Override to add JOINs
 *     protected function applyRequiredJoins(Select $select, Schema $schema): void
 *     {
 *         $select->joinLeft(
 *             ['payment' => $this->resourceConnection->getTableName('sales_order_payment')],
 *             'main_table.entity_id = payment.parent_id',
 *             []
 *         );
 *     }
 *
 *     // Override to add custom filters (e.g., 'ids' array filter)
 *     protected function applyFilters(Select $select, Schema $schema, array $arguments): array
 *     {
 *         $appliedFilters = parent::applyFilters($select, $schema, $arguments);
 *
 *         // Add custom 'ids' filter
 *         if (!empty($arguments['ids'])) {
 *             $select->where('main_table.entity_id IN (?)', $arguments['ids']);
 *             $appliedFilters[] = 'ids: [' . implode(', ', $arguments['ids']) . ']';
 *         }
 *
 *         return $appliedFilters;
 *     }
 *
 *     // Override to post-process rows
 *     protected function fetchAll(Select $select, Schema $schema, array $arguments): array
 *     {
 *         $rows = parent::fetchAll($select, $schema, $arguments);
 *         // Add computed data, transform values, etc.
 *         return $rows;
 *     }
 * }
 * ```
 *
 * ## Extension Points
 *
 * - applyRequiredJoins(): Add JOINs needed for fields from other tables
 * - applyFilters(): Add custom filters not in schema (call parent first!)
 * - fetchAll(): Post-process rows (call parent first!)
 */
abstract class AbstractResource
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        protected readonly ResourceConnection $resourceConnection,
        protected readonly ConditionApplier $conditionApplier,
        protected readonly ListResultFactory $listResultFactory,
        protected readonly DateTimeHelper $dateTimeHelper
    ) {
    }

    /**
     * Get list of entities with optional filtering, sorting, pagination, and aggregation
     *
     * Main entry point called by AbstractTool::execute().
     * Builds and executes SELECT query based on schema and arguments.
     *
     * When aggregation parameters are provided, returns aggregated data instead of entity list.
     *
     * @param Schema $schema Entity schema definition
     * @param array $filters Filter arguments from MCP request
     * @param int $limit Maximum rows to return (0 = use schema default)
     * @param int $offset Pagination offset
     * @param string $sortBy Field to sort by (empty = use schema default)
     * @param string $sortDir Sort direction (ASC or DESC)
     * @param string $aggregateFunction Aggregate function: sum, count, avg, min, max (empty = no aggregation)
     * @param string $aggregateField Field to aggregate (required for sum/avg/min/max)
     * @param string $groupBy Group by field or period: field name, 'month', 'day'
     * @return ListResult Rows and applied filter descriptions
     */
    public function getList(
        Schema $schema,
        array $filters = [],
        int $limit = 0,
        int $offset = 0,
        string $sortBy = '',
        string $sortDir = 'DESC',
        string $aggregateFunction = '',
        string $aggregateField = '',
        string $groupBy = ''
    ): ListResult {
        // Create base SELECT with FROM and JOINs
        $select = $this->createSelect($schema);

        // Get columns (regular or aggregated)
        $select->columns($this->getColumns($schema, $aggregateFunction, $aggregateField, $groupBy));

        // Apply GROUP BY if aggregating with grouping
        if ($aggregateFunction !== '' && $groupBy !== '') {
            $select->group($this->getGroupByExpression($schema, $groupBy));
        }

        // Hook for subclasses to add JOINs
        $this->applyRequiredJoins($select, $schema, !$aggregateField);

        // Apply WHERE conditions from filters
        $appliedFilters = $this->applyFilters($select, $schema, $filters);

        // Apply ORDER BY
        $this->applySorting($select, $schema, $sortBy, $sortDir, $aggregateFunction !== '', $groupBy);

        // Apply LIMIT and OFFSET
        $select->limit($schema->normalizeLimit($limit), max(0, $offset));

        // Fetch and optionally post-process rows
        $rows = $this->fetchAll($select, $schema, $filters);

        return $this->listResultFactory->create([
            'rows' => $rows,
            'appliedFilters' => $appliedFilters
        ]);
    }

    /**
     * Get SELECT columns - regular fields or aggregate expressions
     *
     * @param Schema $schema Entity schema
     * @param string $aggregateFunction Aggregate function (empty = regular columns)
     * @param string $aggregateField Field to aggregate
     * @param string $groupBy Group by field or period
     * @return array Column definitions
     */
    protected function getColumns(
        Schema $schema,
        string $aggregateFunction,
        string $aggregateField,
        string $groupBy
    ): array {
        // Regular mode - return schema columns
        if ($aggregateFunction === '') {
            return $schema->getSelectColumns();
        }

        // Aggregate mode
        $tableAlias = $schema->getTableAlias();
        $function = strtoupper($aggregateFunction);

        // Build aggregate expression
        if ($function === 'COUNT') {
            $aggregateExpr = 'COUNT(*)';
        } else {
            $fieldDef = $schema->getField($aggregateField);
            $column = $fieldDef ? $fieldDef->getSelectColumn($tableAlias) : "{$tableAlias}.{$aggregateField}";
            $aggregateExpr = "{$function}({$column})";
        }

        $columns = ['value' => new \Zend_Db_Expr($aggregateExpr)];

        // Add group key column if grouping
        if ($groupBy !== '') {
            $columns['group_key'] = $this->getGroupByExpression($schema, $groupBy);
        }

        return $columns;
    }

    /**
     * Get GROUP BY expression for field or time period
     *
     * For time-based grouping (month, day), dates are converted from UTC
     * to store timezone using CONVERT_TZ before grouping.
     *
     * @param Schema $schema Entity schema
     * @param string $groupBy Field name or period (month, day)
     * @return \Zend_Db_Expr|string
     */
    protected function getGroupByExpression(Schema $schema, string $groupBy)
    {
        $tableAlias = $schema->getTableAlias();

        // Time-based grouping with timezone conversion
        if ($groupBy === 'month') {
            $convertedDate = $this->getSqlConvertTzExpr("{$tableAlias}.created_at");
            return new \Zend_Db_Expr("DATE_FORMAT({$convertedDate}, '%Y-%m')");
        }
        if ($groupBy === 'day') {
            $convertedDate = $this->getSqlConvertTzExpr("{$tableAlias}.created_at");
            return new \Zend_Db_Expr("DATE({$convertedDate})");
        }

        // Field-based grouping
        $fieldDef = $schema->getField($groupBy);
        if ($fieldDef) {
            return $fieldDef->getSelectColumn($tableAlias);
        }

        return "{$tableAlias}.{$groupBy}";
    }

    /**
     * Get SQL expression for converting UTC date column to store timezone
     *
     * Uses MySQL CONVERT_TZ function.
     * Note: Uses offset format (+03:00) which works without mysql timezone tables,
     * but doesn't account for DST changes.
     *
     * @param string $dateColumn Column name (e.g., 'main_table.created_at')
     * @return string SQL expression with CONVERT_TZ
     */
    protected function getSqlConvertTzExpr(string $dateColumn): string
    {
        $offset = $this->dateTimeHelper->getUtcOffset();
        return "CONVERT_TZ({$dateColumn}, '+00:00', '{$offset}')";
    }

    /**
     * Create base SELECT with FROM clause and required JOINs
     *
     * @param Schema $schema Entity schema
     * @return Select Database select object
     */
    protected function createSelect(Schema $schema): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName($schema->getTable());

        $select = $connection->select()->from([$schema->getTableAlias() => $table], []);

        return $select;
    }

    /**
     * Fetch rows from database
     *
     * Override to post-process rows (add computed fields, transform values, etc.)
     * Always call parent first to get the base rows.
     *
     * @param Select $select Complete SELECT query
     * @param Schema $schema Entity schema
     * @param array $arguments Original arguments (for conditional processing)
     * @return array Database rows
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        return $this->resourceConnection->getConnection()->fetchAll($select);
    }

    /**
     * Apply filters from arguments to SELECT query
     *
     * Iterates through filterable fields in schema and applies any matching
     * filters from arguments using ConditionApplier.
     *
     * Override to add custom filters (call parent first!):
     * ```php
     * protected function applyFilters(Select $select, Schema $schema, array $arguments): array
     * {
     *     $appliedFilters = parent::applyFilters($select, $schema, $arguments);
     *
     *     if (!empty($arguments['ids'])) {
     *         $select->where('main_table.entity_id IN (?)', $arguments['ids']);
     *         $appliedFilters[] = 'ids: [' . implode(', ', $arguments['ids']) . ']';
     *     }
     *
     *     return $appliedFilters;
     * }
     * ```
     *
     * @param Select $select Database select to modify
     * @param Schema $schema Entity schema with field definitions
     * @param array $arguments Filter arguments from MCP request
     * @return string[] Human-readable descriptions of applied filters
     */
    protected function applyFilters(Select $select, Schema $schema, array $arguments): array
    {
        $appliedFilters = [];

        foreach ($schema->getFilterableFields() as $field) {
            // Filter can not be applied on field without db column
            if ($field->getColumn() === false) {
                continue;
            }

            $name = $field->getName();

            // Skip if filter not provided or empty
            if (!isset($arguments[$name]) || $arguments[$name] === '') {
                continue;
            }

            $column = $field->getFilterColumn($schema->getTableAlias());
            $type = $field->getType();
            $value = $arguments[$name];

            // Apply filter based on field type
            if ($type === 'date') {
                $this->conditionApplier->applyDate($select, $column, $value);
            } elseif ($type === 'string') {
                $this->conditionApplier->applyString($select, $column, $value);
            } else {
                $this->conditionApplier->apply($select, $column, $value);
            }

            $appliedFilters[] = $name . ': ' . (is_array($value) ? json_encode($value) : $value);
        }

        return $appliedFilters;
    }

    /**
     * Hook: Apply required JOINs for this entity
     *
     * Override to add JOINs needed for fields from other tables:
     * ```php
     * protected function applyRequiredJoins(Select $select, Schema $schema): void
     * {
     *     $select->joinLeft(
     *         ['payment' => $this->resourceConnection->getTableName('sales_order_payment')],
     *         'main_table.entity_id = payment.parent_id',
     *         []  // No columns - they're defined in schema
     *     );
     * }
     * ```
     *
     * @param Select $select Database select to modify
     * @param Schema $schema Entity schema
     * @param bool $addJoinedFieldsToSelect
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        // Default: no joins needed
    }

    /**
     * Apply ORDER BY clause to SELECT
     *
     * In regular mode: validates sort field against schema's sortable fields.
     * In aggregate mode: sorts by 'value' or 'group_key'.
     *
     * @param Select $select Database select to modify
     * @param Schema $schema Entity schema
     * @param string $sortBy Requested sort field
     * @param string $sortDir Requested sort direction
     * @param bool $isAggregate Whether in aggregation mode
     * @param string $groupBy Group by field (for aggregate mode)
     */
    protected function applySorting(
        Select $select,
        Schema $schema,
        string $sortBy,
        string $sortDir,
        bool $isAggregate = false,
        string $groupBy = ''
    ): void {
        // Validate sort direction
        $sortDir = strtoupper($sortDir);
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'DESC';
        }

        // Aggregate mode sorting
        if ($isAggregate) {
            // Can sort by 'value' (aggregate result) or 'group_key'
            if ($groupBy !== '' && $sortBy === $groupBy) {
                $select->order("group_key {$sortDir}");
            } else {
                // Default: sort by aggregate value
                $select->order("value {$sortDir}");
            }
            return;
        }

        // Regular mode sorting
        $sortFields = $schema->getSortableFieldNames();

        // Validate sort field
        if (!in_array($sortBy, $sortFields)) {
            $sortBy = $sortFields[0] ?? 'created_at';
        }

        $select->order("{$schema->getTableAlias()}.{$sortBy} {$sortDir}");
    }
}
