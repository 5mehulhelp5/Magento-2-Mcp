<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\OAuthClient;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_Mcp::oauth_clients';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Freento_Mcp::oauth_clients');
        $resultPage->getConfig()->getTitle()->prepend(__('AI MCP Clients'));

        return $resultPage;
    }
}
