<?php

declare(strict_types=1);

/**
 * Rollback for orders_timezone_test fixture
 */

use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var OrderCollection $orderCollection */
$orderCollection = $objectManager->create(OrderCollection::class);
$orderCollection->addFieldToFilter(
    'increment_id',
    ['in' => ['100000201', '100000202', '100000203', '100000204', '100000205', '100000206']]
);

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);

foreach ($orderCollection as $order) {
    $orderRepository->delete($order);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple_rollback.php');