<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\ToolResultInterface;

class ToolResultFactory
{
    /**
     * Create tool result
     *
     * @param array $content
     * @return ToolResultInterface
     */
    public function create(array $content): ToolResultInterface
    {
        return new ToolResult($content);
    }

    /**
     * Create text tool result
     *
     * @param string $text
     * @return ToolResultInterface
     */
    public function createText(string $text): ToolResultInterface
    {
        return new ToolResult([
            ['type' => 'text', 'text' => $text]
        ]);
    }

    /**
     * Create error tool result
     *
     * @param string $message
     * @return ToolResultInterface
     */
    public function createError(string $message): ToolResultInterface
    {
        return new ToolResult(
            [['type' => 'text', 'text' => $message]],
            true
        );
    }
}
