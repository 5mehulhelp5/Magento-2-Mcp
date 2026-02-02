<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\OAuthClient;

use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Freento\Mcp\Service\TokenGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_Mcp::oauth_clients';

    /**
     * @param Context $context
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     * @param TokenGenerator $tokenGenerator
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        private readonly ClientFactory $clientFactory,
        private readonly ClientResource $clientResource,
        private readonly TokenGenerator $tokenGenerator,
        private readonly EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $entityId = isset($data['entity_id']) && $data['entity_id'] !== '' ? (int)$data['entity_id'] : null;

        try {
            $client = $this->clientFactory->create();
            $isNew = !$entityId;

            if ($entityId) {
                $this->clientResource->load($client, $entityId);
                if (!$client->getId()) {
                    $this->messageManager->addErrorMessage(__('This client no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $this->saveClient($client, $data, $isNew);

            $message = $isNew ? __('Client has been created.') : __('Client has been saved.');
            $this->messageManager->addSuccessMessage($message);

            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $client->getId()]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save client: %1', $e->getMessage()));
        }

        return $entityId
            ? $resultRedirect->setPath('*/*/edit', ['entity_id' => $entityId])
            : $resultRedirect->setPath('*/*/');
    }

    /**
     * Save client
     *
     * @param \Freento\Mcp\Model\OAuth\Client $client
     * @param array $data
     * @param bool $isNew
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveClient($client, array $data, bool $isNew): void
    {
        if (isset($data['name'])) {
            $client->setName(trim($data['name']));
        }

        $roleId = isset($data['role_id']) && $data['role_id'] !== '' ? (int)$data['role_id'] : null;
        $client->setRoleId($roleId);

        if (isset($data['is_active'])) {
            $client->setIsActive((bool)$data['is_active']);
        }

        if ($isNew) {
            $client->setClientId($this->tokenGenerator->generateToken(32));
            $client->setClientSecret($this->encryptor->encrypt($this->tokenGenerator->generateToken(64)));
            $client->setIsActive(true);
        }

        $this->clientResource->save($client);
    }
}
