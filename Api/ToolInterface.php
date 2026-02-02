<?php

declare(strict_types=1);

namespace Freento\Mcp\Api;

interface ToolInterface
{
    /**
     * Unique tool name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Description for LLM
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * JSON Schema for input parameters
     *
     * @return array
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool
     *
     * @param array $arguments
     * @return ToolResultInterface
     */
    public function execute(array $arguments): ToolResultInterface;
}
