<?php

declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\Data\OAuthClientInterface;
use Freento\Mcp\Api\McpServerInterface;
use Freento\Mcp\Exception\AccessDeniedException;
use Freento\Mcp\Exception\MethodNotFoundException;
use Freento\Mcp\Exception\ParseErrorException;
use Freento\Mcp\Exception\ToolNotFoundException;
use Freento\Mcp\Model\Protocol\JsonRpcParser;
use Freento\Mcp\Model\Protocol\ResponseBuilder;
use Psr\Log\LoggerInterface;

class McpServer implements McpServerInterface
{
    /**
     * @param JsonRpcParser $parser
     * @param ResponseBuilder $responseBuilder
     * @param Router $router
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly JsonRpcParser $parser,
        private readonly ResponseBuilder $responseBuilder,
        private readonly Router $router,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(string $jsonRpcRequest, OAuthClientInterface $client): array
    {
        $requestId = null;

        try {
            $request = $this->parser->parse($jsonRpcRequest);
            $requestId = $request['id'];

            $result = $this->router->dispatch($request['method'], $request['params'], $client);

            return $this->responseBuilder->success($requestId, $result);
        } catch (ParseErrorException $e) {
            return $this->responseBuilder->error($requestId, -32700, 'Parse error');
        } catch (MethodNotFoundException $e) {
            return $this->responseBuilder->error($requestId, -32601, 'Method not found');
        } catch (ToolNotFoundException $e) {
            return $this->responseBuilder->error($requestId, -32602, 'Unknown tool: ' . $e->getMessage());
        } catch (AccessDeniedException $e) {
            return $this->responseBuilder->error($requestId, -32003, 'Access denied: ' . $e->getToolName());
        } catch (\Throwable $e) {
            $this->logger->error('MCP Error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->responseBuilder->error($requestId, -32603, 'Internal error');
        }
    }
}
