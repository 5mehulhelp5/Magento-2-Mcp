<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\AclRole;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Api\Data\AclRoleInterface;
use Freento\Mcp\Model\AclRoleFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::acl_rules';

    /**
     * @param Context $context
     * @param AclRoleRepositoryInterface $roleRepository
     * @param AclRoleFactory $roleFactory
     */
    public function __construct(
        Context $context,
        private readonly AclRoleRepositoryInterface $roleRepository,
        private readonly AclRoleFactory $roleFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$postData) {
            return $resultRedirect->setPath('*/*/');
        }

        // UI Component form sends data nested under 'data' key
        $data = $postData['data'] ?? $postData;

        try {
            $role = $this->saveRole($data);
            $this->messageManager->addSuccessMessage(__('ACL Role has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['role_id' => $role->getRoleId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the role.'));
        }

        $roleId = isset($data['role_id']) && $data['role_id'] !== '' ? (int)$data['role_id'] : null;

        return $resultRedirect->setPath('*/*/edit', ['role_id' => $roleId]);
    }

    /**
     * Save role for data
     *
     * @param array $data
     * @return AclRoleInterface
     * @throws LocalizedException
     */
    private function saveRole(array $data): AclRoleInterface
    {
        if (isset($data['role_id']) && $data['role_id'] !== '') {
            $role = $this->roleRepository->getById((int)$data['role_id']);
        } else {
            $role = $this->roleFactory->create();
        }

        $role->setName($data['name'] ?? '');
        $role->setAccessType($data['access_type'] ?? 'specified');

        $this->roleRepository->save($role);

        $tools = [];
        if ($role->getAccessType() === 'specified' && isset($data['tools'])) {
            $tools = is_array($data['tools']) ? $data['tools'] : [];
        }

        $this->roleRepository->saveRoleTools($role->getRoleId(), $tools);
        return $role;
    }
}
