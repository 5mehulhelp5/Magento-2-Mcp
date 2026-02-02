<?php
declare(strict_types=1);

namespace Freento\Mcp\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ToolNotFoundException extends LocalizedException
{
    /**
     * @param string $toolName
     */
    public function __construct(private readonly string $toolName)
    {
        parent::__construct(new Phrase('Tool not found: %1', [$toolName]));
    }

    /**
     * Get tool name
     *
     * @return string
     */
    public function getToolName(): string
    {
        return $this->toolName;
    }
}
