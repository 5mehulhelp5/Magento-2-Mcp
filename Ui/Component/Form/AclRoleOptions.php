<?php

declare(strict_types=1);

namespace Freento\Mcp\Ui\Component\Form;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Magento\Framework\Data\OptionSourceInterface;

class AclRoleOptions implements OptionSourceInterface
{
    /**
     * @var array|null
     */
    private ?array $options = null;

    /**
     * @param AclRoleRepositoryInterface $aclRoleRepository
     */
    public function __construct(
        private AclRoleRepositoryInterface $aclRoleRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $this->options = [
            ['value' => '', 'label' => __('-- No Role (No Access) --')]
        ];

        foreach ($this->aclRoleRepository->getList() as $role) {
            $this->options[] = [
                'value' => $role->getRoleId(),
                'label' => $role->getName()
            ];
        }

        return $this->options;
    }
}
