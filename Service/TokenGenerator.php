<?php
declare(strict_types=1);

namespace Freento\Mcp\Service;

class TokenGenerator
{
    /**
     * Generate a random token (64 hex characters)
     *
     * @return string
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a random token with specified length
     *
     * @param int $length
     * @return string
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes((int)ceil($length / 2)));
    }

    /**
     * Hash a token for storage
     *
     * @param string $token
     * @return string
     */
    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
