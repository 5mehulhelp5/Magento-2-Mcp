<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\Data\OAuthClientInterface;
use Freento\Mcp\Exception\AccessDeniedException;
use Freento\Mcp\Exception\MethodNotFoundException;
use Freento\Mcp\Exception\ToolNotFoundException;
use Freento\Mcp\Service\AclValidator;

class Router
{
    /**
     * @param ToolRegistry $toolRegistry
     * @param AclValidator $aclValidator
     * @param string $serverInstructions
     */
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
        private readonly AclValidator $aclValidator,
        private readonly string $serverInstructions = ''
    ) {
    }

    /**
     * Dispatch specific action
     *
     * @param string $method
     * @param array $params
     * @param OAuthClientInterface $client
     * @return array
     * @throws MethodNotFoundException
     * @throws ToolNotFoundException
     * @throws AccessDeniedException
     */
    public function dispatch(string $method, array $params, OAuthClientInterface $client): array
    {
        return match ($method) {
            'initialize' => $this->handleInitialize(),
            'notifications/initialized' => [], // Notification - no response needed
            'tools/list' => $this->handleToolsList($client),
            'tools/call' => $this->handleToolsCall($params, $client),
            default => throw new MethodNotFoundException($method)
        };
    }

    /**
     * Handle initialize
     *
     * @return array
     */
    private function handleInitialize(): array
    {
        $result = [
            'protocolVersion' => '2024-11-05',
            'serverInfo' => [
                'name' => 'freento-magento-mcp',
                'version' => '1.0.0'
            ],
            'capabilities' => [
                // empty class is used because the syntax requires {}, whereas an empty array would be serialized to []
                'tools' => new \stdClass()
            ]
        ];

        if ($this->serverInstructions) {
            $result['instructions'] = $this->serverInstructions;
        }

        return $result;
    }

    /**
     * Handle tolls list
     *
     * @param OAuthClientInterface $client
     * @return array
     */
    private function handleToolsList(OAuthClientInterface $client): array
    {
        $allowedTools = $this->aclValidator->getAllowedTools($client);
        $tools = [];

        foreach ($this->toolRegistry->getAll() as $tool) {
            // If allowedTools is null, client has access to all tools
            // Otherwise, filter by allowed tool names
            if ($allowedTools === null || in_array($tool->getName(), $allowedTools, true)) {
                $tools[] = [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'inputSchema' => $tool->getInputSchema()
                ];
            }
        }

        return ['tools' => $tools];
    }

    /**
     * Handle tool call
     *
     * @param array $params
     * @param OAuthClientInterface $client
     * @return array
     * @throws ToolNotFoundException
     * @throws AccessDeniedException
     */
    private function handleToolsCall(array $params, OAuthClientInterface $client): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $tool = $this->toolRegistry->get($toolName);

        if (!$tool) {
            throw new ToolNotFoundException($toolName);
        }

        // Check ACL permission for the tool
        if (!$this->aclValidator->canUseTool($client, $toolName)) {
            throw new AccessDeniedException($toolName);
        }

        return $tool->execute($arguments)->toArray();
    }
}
