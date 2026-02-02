<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\ToolInterface;

class ToolRegistry
{
    /**
     * @param ToolInterface[] $tools
     */
    public function __construct(private array $tools = [])
    {
    }

    /**
     * Get tool by name
     *
     * @param string $name
     * @return ToolInterface|null
     */
    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all
     *
     * @return ToolInterface[]
     */
    public function getAll(): array
    {
        return $this->tools;
    }
}
