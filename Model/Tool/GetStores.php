<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Api\ToolInterface;
use Freento\Mcp\Api\ToolResultInterface;
use Freento\Mcp\Model\ToolResultFactory;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Get stores tool
 *
 * Returns the full Magento store hierarchy: Websites > Store Groups > Store Views.
 */
class GetStores implements ToolInterface
{
    /** @var array<int, GroupInterface[]>|null */
    private ?array $groupsByWebsite = null;

    /** @var array<int, StoreInterface[]>|null */
    private ?array $storesByGroup = null;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ToolResultFactory $resultFactory
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ToolResultFactory $resultFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'get_stores';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Get the Magento store hierarchy: Websites, Store Groups, and Store Views.

Use this tool when you need to:
- List all websites, store groups, and store views
- Understand the multi-store configuration
- Check base URLs for each store view
- Find store view codes or IDs
- Identify default stores and store views

Returns a hierarchical list showing Websites > Store Groups > Store Views
with details such as codes, IDs, base URLs, and status.

Example prompts:
- "What stores are configured?"
- "Show me the store hierarchy"
- "List all websites and store views"
- "What are the base URLs for each store?"
- "How many store views do we have?"';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'examples' => [new \stdClass()]
        ];
    }

    /**
     * @inheritDoc
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $websites = $this->storeManager->getWebsites(true);

        $lines = [];
        $lines[] = sprintf(
            'Store hierarchy: %d website(s), %d store group(s), %d store view(s)',
            count($websites),
            count($this->storeManager->getGroups(true)),
            count($this->storeManager->getStores(true))
        );
        $lines[] = "";

        foreach ($websites as $website) {
            array_push($lines, ...$this->getWebsiteLines($website));
        }

        return $this->resultFactory->createText(implode("\n", $lines));
    }

    /**
     * Get formatted lines for a website with its store groups and store views
     *
     * @param WebsiteInterface $website
     * @return string[]
     */
    private function getWebsiteLines(WebsiteInterface $website): array
    {
        $websiteId = (int)$website->getId();
        $defaultGroupId = (int)$website->getDefaultGroupId();
        $lines = [
            "Website: {$website->getName()} (ID: {$websiteId}, code: {$website->getCode()})",
            "  Default Group ID: {$defaultGroupId}",
        ];

        $websiteGroups = $this->getGroupsByWebsite()[$websiteId] ?? [];
        if (empty($websiteGroups)) {
            $lines[] = "  (no store groups)";
            $lines[] = "";
            return $lines;
        }

        foreach ($websiteGroups as $group) {
            array_push($lines, ...$this->getGroupLines($group, $defaultGroupId));
        }
        $lines[] = "";

        return $lines;
    }

    /**
     * Get formatted lines for a store group with its store views
     *
     * @param GroupInterface $group
     * @param int $defaultGroupId
     * @return string[]
     */
    private function getGroupLines(GroupInterface $group, int $defaultGroupId): array
    {
        $groupId = (int)$group->getId();
        $defaultLabel = $groupId === $defaultGroupId ? ' [default]' : '';
        $lines = [
            "  Store Group: {$group->getName()} (ID: {$groupId}){$defaultLabel}",
            "    Root Category ID: {$group->getRootCategoryId()}",
            "    Default Store View ID: {$group->getDefaultStoreId()}",
        ];

        $groupStores = $this->getStoresByGroup()[$groupId] ?? [];
        if (empty($groupStores)) {
            $lines[] = "    (no store views)";
            return $lines;
        }

        foreach ($groupStores as $store) {
            array_push($lines, ...$this->getStoreLines($store, (int)$group->getDefaultStoreId()));
        }

        return $lines;
    }

    /**
     * Get store groups indexed by website ID
     *
     * @return array<int, GroupInterface[]>
     */
    private function getGroupsByWebsite(): array
    {
        if ($this->groupsByWebsite === null) {
            $this->groupsByWebsite = [];
            foreach ($this->storeManager->getGroups(true) as $group) {
                $this->groupsByWebsite[$group->getWebsiteId()][] = $group;
            }
        }

        return $this->groupsByWebsite;
    }

    /**
     * Get store views indexed by store group ID
     *
     * @return array<int, StoreInterface[]>
     */
    private function getStoresByGroup(): array
    {
        if ($this->storesByGroup === null) {
            $this->storesByGroup = [];
            foreach ($this->storeManager->getStores(true) as $store) {
                $this->storesByGroup[$store->getStoreGroupId()][] = $store;
            }
        }

        return $this->storesByGroup;
    }

    /**
     * Get formatted lines for a store view
     *
     * @param StoreInterface $store
     * @param int $defaultStoreId
     * @return string[]
     */
    private function getStoreLines(StoreInterface $store, int $defaultStoreId): array
    {
        $storeId = (int)$store->getId();
        $defaultLabel = $storeId === $defaultStoreId ? ' [default]' : '';
        $status = $store->getIsActive() ? 'Active' : 'Inactive';

        return [
            sprintf(
                "    Store View: %s (ID: %d, code: %s)%s",
                $store->getName(),
                $storeId,
                $store->getCode(),
                $defaultLabel
            ),
            "      Status: {$status}",
            "      Base URL: {$store->getBaseUrl()}",
        ];
    }
}
