<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Helper;

/**
 * Converts Magento sales rule condition/action trees into human-readable text.
 *
 * Handles the recursive condition structure stored in salesrule.conditions_serialized
 * and salesrule.actions_serialized.
 */
class RuleConditionFormatter
{
    private const OPERATOR_MAP = [
        '==' => '=',
        '!=' => '!=',
        '>=' => '>=',
        '>' => '>',
        '<=' => '<=',
        '<' => '<',
        '()' => 'in',
        '!()' => 'not in',
        '{}' => 'contains',
        '!{}' => 'does not contain',
    ];

    /**
     * Convert deserialized condition tree to human-readable string
     *
     * @param array|null $conditionTree Deserialized condition tree from JSON
     * @return string Human-readable condition string, or "(none)" if empty
     */
    public function format(?array $conditionTree): string
    {
        if ($conditionTree === null || empty($conditionTree['conditions'])) {
            return '(none)';
        }

        $result = $this->formatNode($conditionTree);

        return $result !== '' ? $result : '(none)';
    }

    /**
     * Format a single node of the condition tree (combine or leaf)
     *
     * @param array $node Condition tree node
     * @param int $depth Current nesting depth
     * @return string Formatted node string
     */
    private function formatNode(array $node, int $depth = 0): string
    {
        if (!empty($node['conditions'])) {
            return $this->formatCombineNode($node, $depth);
        }

        return $this->formatLeafNode($node);
    }

    /**
     * Format a combine node with aggregator prefix and recursive children
     *
     * @param array $node Combine node with child conditions
     * @param int $depth Current nesting depth
     * @return string e.g. "ALL of: subtotal >= 200, qty > 1"
     */
    private function formatCombineNode(array $node, int $depth): string
    {
        $aggregator = strtoupper($node['aggregator'] ?? 'all');
        $value = (int)($node['value'] ?? 1);

        $prefix = $value === 0 ? "NOT {$aggregator}" : $aggregator;

        $children = [];
        foreach ($node['conditions'] as $child) {
            $formatted = $this->formatNode($child, $depth + 1);
            if ($formatted !== '') {
                $children[] = $formatted;
            }
        }

        if (empty($children)) {
            return '';
        }

        return $prefix . ' of: ' . implode(', ', $children);
    }

    /**
     * Format a leaf condition node as "attribute operator value"
     *
     * @param array $node Leaf condition node
     * @return string e.g. "category_ids = 6", or empty string if no attribute
     */
    private function formatLeafNode(array $node): string
    {
        $attribute = $node['attribute'] ?? '';
        if ($attribute === '') {
            return '';
        }

        $operator = $node['operator'] ?? '==';
        $value = $node['value'] ?? '';

        $operatorLabel = self::OPERATOR_MAP[$operator] ?? $operator;

        return "{$attribute} {$operatorLabel} {$value}";
    }
}
