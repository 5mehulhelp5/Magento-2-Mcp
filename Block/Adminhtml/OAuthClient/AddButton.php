<?php

declare(strict_types=1);

namespace Freento\Mcp\Block\Adminhtml\OAuthClient;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class AddButton implements ButtonProviderInterface
{
    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Add New Client'),
            'class' => 'primary',
            'url' => $this->getAddUrl(),
            'sort_order' => 10
        ];
    }

    /**
     * Get action url
     *
     * @return string
     */
    private function getAddUrl(): string
    {
        return $this->urlBuilder->getUrl('freento_mcp/oauthclient/edit');
    }
}
