<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\AclRole;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::acl_rules';

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
