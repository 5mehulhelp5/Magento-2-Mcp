<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\AclRole;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action implements HttpPostActionInterface, HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::acl_rules';

    /**
     * @param Context $context
     * @param AclRoleRepositoryInterface $roleRepository
     */
    public function __construct(
        Context $context,
        private readonly AclRoleRepositoryInterface $roleRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $roleId = (int)$this->getRequest()->getParam('role_id');

        if (!$roleId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a role to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->roleRepository->deleteById($roleId);
            $this->messageManager->addSuccessMessage(__('ACL Role has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the role.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
