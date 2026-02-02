<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

/**
 * Custom router for handling .well-known OAuth endpoints
 */
class Router implements RouterInterface
{
    /**
     * @param ActionFactory $actionFactory
     */
    public function __construct(
        private readonly ActionFactory $actionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function match(RequestInterface $request)
    {
        $pathInfo = trim($request->getPathInfo(), '/');

        // Handle .well-known/oauth-authorization-server
        if ($pathInfo === '.well-known/oauth-authorization-server') {
            $request->setModuleName('freento_mcp');
            $request->setControllerName('oauth');
            $request->setActionName('wellknown');
            $request->setPathInfo('/freento_mcp/oauth/wellknown');

            return $this->actionFactory->create(
                \Magento\Framework\App\Action\Forward::class
            );
        }

        return null;
    }
}
