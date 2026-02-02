<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Protocol;

class ResponseBuilder
{
    /**
     * Get success result
     *
     * @param string|int $id
     * @param array $result
     * @return array
     */
    public function success($id, array $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ];
    }

    /**
     * Get error result
     *
     * @param string|int $id
     * @param int $code
     * @param string $message
     * @return array
     */
    public function error($id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
}
