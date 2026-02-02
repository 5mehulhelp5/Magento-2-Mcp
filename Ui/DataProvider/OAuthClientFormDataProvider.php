<?php

declare(strict_types=1);

namespace Freento\Mcp\Ui\DataProvider;

use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;

class OAuthClientFormDataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    private array $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     * @param RequestInterface $request
     * @param UserCollectionFactory $userCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private ClientFactory $clientFactory,
        private ClientResource $clientResource,
        private RequestInterface $request,
        private UserCollectionFactory $userCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $entityId = (int)$this->request->getParam('entity_id');
        if (!$entityId) {
            return [];
        }

        $client = $this->clientFactory->create();
        $this->clientResource->load($client, $entityId);

        if ($client->getId()) {
            $data = $client->getData();
            $data['created_by'] = $this->getCreatedByLabel($client->getAdminUserId());
            $this->loadedData[$client->getId()] = $data;
        }

        return $this->loadedData;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter): void
    {
        // Not needed for form data provider
    }

    /**
     * Get create by label
     *
     * @param int|null $adminUserId
     * @return string
     */
    private function getCreatedByLabel(?int $adminUserId): string
    {
        if (!$adminUserId) {
            return '';
        }

        $collection = $this->userCollectionFactory->create();
        $collection->addFieldToFilter('user_id', $adminUserId);
        $user = $collection->getFirstItem();

        if (!$user->getId()) {
            return '';
        }

        return sprintf('%s (%s)', $user->getUserName(), $user->getEmail());
    }
}
