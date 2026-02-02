<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\OAuth;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;

/**
 * OAuth 2.0 Authorization Server Metadata endpoint
 * RFC 8414: https://tools.ietf.org/html/rfc8414
 */
class WellKnown implements HttpGetActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private JsonFactory $jsonFactory,
        private UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $baseUrl = $this->urlBuilder->getBaseUrl();

        $metadata = [
            'issuer' => rtrim($baseUrl, '/'),
            'authorization_endpoint' => $this->urlBuilder->getUrl('freento_mcp/oauth/authorize'),
            'token_endpoint' => $this->urlBuilder->getUrl('freento_mcp/oauth/token'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'code_challenge_methods_supported' => ['S256'],
        ];

        return $this->jsonFactory->create()->setData($metadata);
    }
}
