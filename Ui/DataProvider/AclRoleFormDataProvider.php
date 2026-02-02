<?php
declare(strict_types=1);

namespace Freento\Mcp\Ui\DataProvider;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Model\ResourceModel\AclRole\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class AclRoleFormDataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param AclRoleRepositoryInterface $roleRepository
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly AclRoleRepositoryInterface $roleRepository,
        private readonly RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        $roleId = $this->request->getParam('role_id');

        if ($roleId) {
            $items = $this->collection->getItems();
            foreach ($items as $role) {
                $roleData = $role->getData();
                $roleData['tools'] = $this->roleRepository->getRoleTools((int)$role->getRoleId());
                $this->loadedData[$role->getRoleId()] = $roleData;
            }
        }

        return $this->loadedData;
    }
}
