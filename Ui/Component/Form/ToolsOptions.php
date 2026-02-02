<?php

declare(strict_types=1);

namespace Freento\Mcp\Ui\Component\Form;

use Freento\Mcp\Model\ToolRegistry;
use Magento\Framework\Data\OptionSourceInterface;

class ToolsOptions implements OptionSourceInterface
{
    /**
     * @param ToolRegistry $toolRegistry
     */
    public function __construct(private readonly ToolRegistry $toolRegistry)
    {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->toolRegistry->getAll() as $tool) {
            $description = $tool->getDescription();
            // Get first line and clean control characters
            $firstLine = strtok($description, "\n");
            $firstLine = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $firstLine));
            if (strlen($firstLine) > 100) {
                $firstLine = substr($firstLine, 0, 100) . '...';
            }

            $options[] = [
                'value' => $tool->getName(),
                'label' => $tool->getName(),
                'module' => $this->getModuleName($tool),
                'description' => $firstLine
            ];
        }

        // Sort by module, then by tool name
        usort($options, function ($a, $b) {
            $cmp = strcmp($a['module'], $b['module']);
            return $cmp !== 0 ? $cmp : strcmp($a['value'], $b['value']);
        });

        return $options;
    }

    /**
     * Get module name
     *
     * @param object $tool
     * @return string
     */
    private function getModuleName(object $tool): string
    {
        $parts = explode('\\', get_class($tool));

        return isset($parts[0], $parts[1]) ? $parts[0] . '_' . $parts[1] : 'Other';
    }
}
