<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\OAuthClient;

use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Freento\Mcp\Service\TokenGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class GenerateToken extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_Mcp::oauth_clients';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     * @param TokenGenerator $tokenGenerator
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        private JsonFactory $jsonFactory,
        private ClientFactory $clientFactory,
        private ClientResource $clientResource,
        private TokenGenerator $tokenGenerator,
        private AuthSession $authSession
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

        $client = $this->clientFactory->create();
        $this->clientResource->load($client, $entityId);

        if (!$client->getId()) {
            return $result->setData([
                'success' => false,
                'message' => __('Client not found.')
            ]);
        }

        $adminUser = $this->authSession->getUser();
        if (!$adminUser || !$adminUser->getId()) {
            return $result->setData([
                'success' => false,
                'message' => __('Admin session expired.')
            ]);
        }

        try {
            // Generate new token
            $accessToken = $this->tokenGenerator->generate();
            $tokenHash = $this->tokenGenerator->hash($accessToken);

            // Clear OTP and update token
            $this->clientResource->updateToken($entityId, $tokenHash, (int)$adminUser->getId());
            $this->clientResource->updateOtp($entityId, null, null);

            return $result->setData([
                'success' => true,
                'token' => $accessToken,
                'message' => __('Token generated successfully.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Failed to generate token: %1', $e->getMessage())
            ]);
        }
    }
}
