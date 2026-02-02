<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\OAuthClient;

use Freento\Mcp\Service\OAuthService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class GetSecret extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_Mcp::oauth_clients';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param OAuthService $oauthService
     */
    public function __construct(
        Context $context,
        private JsonFactory $jsonFactory,
        private OAuthService $oauthService
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $entityId = (int)$this->getRequest()->getParam('entity_id');
        if (!$entityId) {
            return $result->setData([
                'success' => false,
                'message' => __('Client ID is required.')
            ]);
        }

        try {
            $secret = $this->oauthService->getDecryptedSecret($entityId);

            if ($secret === null) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Client not found or has no secret.')
                ]);
            }

            return $result->setData([
                'success' => true,
                'secret' => $secret
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Failed to get secret: %1', $e->getMessage())
            ]);
        }
    }
}
