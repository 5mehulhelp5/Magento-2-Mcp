<?php
declare(strict_types=1);

namespace Freento\Mcp\Api;

interface ToolResultInterface
{
    /**
     * Get content
     *
     * @return array
     */
    public function getContent(): array;

    /**
     * Is error
     *
     * @return bool
     */
    public function isError(): bool;

    /**
     * To array
     *
     * @return array
     */
    public function toArray(): array;
}
