<?php

declare(strict_types=1);

namespace Freento\Mcp\Api;

use Freento\Mcp\Api\Data\OAuthClientInterface;

interface McpServerInterface
{
    /**
     * Handle MCP request
     *
     * @param string $jsonRpcRequest
     * @param OAuthClientInterface $client The authenticated OAuth client with role context
     * @return array JSON-RPC response
     */
    public function handle(string $jsonRpcRequest, OAuthClientInterface $client): array;
}
