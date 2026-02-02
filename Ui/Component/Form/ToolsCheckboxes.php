<?php
declare(strict_types=1);

namespace Freento\Mcp\Ui\Component\Form;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Container;

class ToolsCheckboxes extends Container
{
    /**
     * @param ContextInterface $context
     * @param ToolsOptions $toolsOptions
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        private readonly ToolsOptions $toolsOptions,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $config = $this->getData('config');
        $config['options'] = $this->toolsOptions->toOptionArray();
        $this->setData('config', $config);

        parent::prepare();
    }
}
