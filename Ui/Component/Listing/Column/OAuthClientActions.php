<?php

declare(strict_types=1);

namespace Freento\Mcp\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OAuthClientActions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(
                        'freento_mcp/oauthclient/edit',
                        ['entity_id' => $item['entity_id']]
                    ),
                    'label' => __('Edit'),
                ],
                'toggle_status' => [
                    'href' => $this->urlBuilder->getUrl(
                        'freento_mcp/oauthclient/toggleStatus',
                        ['entity_id' => $item['entity_id']]
                    ),
                    'label' => $item['is_active'] ? __('Deactivate') : __('Activate'),
                    'confirm' => [
                        'title' => $item['is_active'] ? __('Deactivate Client') : __('Activate Client'),
                        'message' => $item['is_active']
                            ? __('Are you sure you want to deactivate this client?')
                            : __('Are you sure you want to activate this client?')
                    ],
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(
                        'freento_mcp/oauthclient/delete',
                        ['entity_id' => $item['entity_id']]
                    ),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Client'),
                        'message' => __('Are you sure you want to delete this client? This action cannot be undone.')
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
