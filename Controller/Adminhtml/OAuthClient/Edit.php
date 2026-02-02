<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\OAuthClient;

use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_Mcp::oauth_clients';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ClientFactory $clientFactory,
        private readonly ClientResource $clientResource
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $entityId = (int)$this->getRequest()->getParam('entity_id');

        $client = $this->clientFactory->create();
        if ($entityId) {
            $this->clientResource->load($client, $entityId);
            if (!$client->getId()) {
                $this->messageManager->addErrorMessage(__('This client no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Freento_Mcp::oauth_clients');
        $resultPage->getConfig()->getTitle()->prepend(
            $client->getId() ? __('Edit Client: %1', $client->getName()) : __('New Client')
        );

        return $resultPage;
    }
}
