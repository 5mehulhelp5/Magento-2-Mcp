<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\ToolResultInterface;

class ToolResult implements ToolResultInterface
{
    /**
     * @param array $content
     * @param bool $isError
     */
    public function __construct(private readonly array $content, private readonly bool $isError = false)
    {
    }

    /**
     * @inheritDoc
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @inheritDoc
     */
    public function isError(): bool
    {
        return $this->isError;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $result = ['content' => $this->content];
        if ($this->isError) {
            $result['isError'] = true;
        }
        return $result;
    }
}
