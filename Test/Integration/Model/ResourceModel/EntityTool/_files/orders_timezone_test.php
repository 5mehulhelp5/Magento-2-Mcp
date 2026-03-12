<?php

declare(strict_types=1);

/**
 * Fixture: Orders for timezone and date boundary testing
 *
 * Orders are created with specific UTC timestamps to test:
 * 1. Timezone conversion (UTC to local and vice versa)
 * 2. Date boundary conditions (gt/lt operators)
 *
 * Timestamps are in UTC. When store timezone is Europe/Paris (UTC+1 winter):
 * - 2024-01-15 22:00:00 UTC = 2024-01-15 23:00:00 Paris (same day)
 * - 2024-01-15 23:30:00 UTC = 2024-01-16 00:30:00 Paris (next day!)
 * - 2024-01-16 00:00:00 UTC = 2024-01-16 01:00:00 Paris (same day)
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');

$addressData = [
    'region' => 'CA',
    'region_id' => '12',
    'postcode' => '11111',
    'lastname' => 'lastname',
    'firstname' => 'firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'admin@example.com',
    'telephone' => '11111111',
    'country_id' => 'US'
];

// All times are in UTC (as stored in database)
$orders = [
    // Order at start of day UTC - for boundary testing
    [
        'increment_id' => '100000201',
        'state' => Order::STATE_NEW,
        'status' => 'pending',
        'grand_total' => 100.00,
        'base_grand_total' => 100.00,
        'subtotal' => 100.00,
        'store_id' => 1,
        'website_id' => 1,
        'customer_email' => 'tz_test@example.com',
        'created_at' => '2024-01-15 00:00:00', // Exactly at midnight UTC
        'payment_method' => 'checkmo',
    ],
    // Order in middle of day UTC
    [
        'increment_id' => '100000202',
        'state' => Order::STATE_PROCESSING,
        'status' => 'processing',
        'grand_total' => 200.00,
        'base_grand_total' => 200.00,
        'subtotal' => 200.00,
        'store_id' => 1,
        'website_id' => 1,
        'customer_email' => 'tz_test@example.com',
        'created_at' => '2024-01-15 12:00:00', // Noon UTC
        'payment_method' => 'checkmo',
    ],
    // Order late evening UTC - crosses date boundary in Paris timezone
    // 22:00 UTC = 23:00 Paris (still Jan 15)
    [
        'increment_id' => '100000203',
        'state' => Order::STATE_PROCESSING,
        'status' => 'processing',
        'grand_total' => 150.00,
        'base_grand_total' => 150.00,
        'subtotal' => 150.00,
        'store_id' => 1,
        'website_id' => 1,
        'customer_email' => 'tz_test@example.com',
        'created_at' => '2024-01-15 22:00:00', // 23:00 Paris
        'payment_method' => 'checkmo',
    ],
    // Order at 23:30 UTC - IS NEXT DAY in Paris!
    // 23:30 UTC = 00:30 Paris (Jan 16!)
    [
        'increment_id' => '100000204',
        'state' => Order::STATE_PROCESSING,
        'status' => 'processing',
        'grand_total' => 250.00,
        'base_grand_total' => 250.00,
        'subtotal' => 250.00,
        'store_id' => 1,
        'website_id' => 1,
        'customer_email' => 'tz_test@example.com',
        'created_at' => '2024-01-15 23:30:00', // 00:30 Paris next day!
        'payment_method' => 'cashondelivery',
    ],
    // Order at end of day UTC
    [
        'increment_id' => '100000205',
        'state' => Order::STATE_PROCESSING,
        'status' => 'processing',
        'grand_total' => 300.00,
        'base_grand_total' => 300.00,
        'subtotal' => 300.00,
        'store_id' => 1,
        'website_id' => 1,
        'customer_email' => 'tz_test@example.com',
        'created_at' => '2024-01-15 23:59:59', // 00:59:59 Paris next day!
        'payment_method' => 'cashondelivery',
    ],
    // Order at start of next day UTC
    [
        'increment_id' => '100000206',
        'state' => Order::STATE_COMPLETE,
        'status' => 'complete',
        'grand_total' => 50.00,
        'base_grand_total' => 50.00,
        'subtotal' => 50.00,
        'store_id' => 1,
        'website_id' => 1,
        'customer_email' => 'tz_test@example.com',
        'created_at' => '2024-01-16 00:00:00', // 01:00 Paris
        'payment_method' => 'checkmo',
    ],
];

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);

foreach ($orders as $orderData) {
    /** @var Order $order */
    $order = $objectManager->create(Order::class);

    /** @var OrderAddress $billingAddress */
    $billingAddress = $objectManager->create(OrderAddress::class, ['data' => $addressData]);
    $billingAddress->setAddressType('billing');

    $shippingAddress = clone $billingAddress;
    $shippingAddress->setId(null)->setAddressType('shipping');

    /** @var Payment $payment */
    $payment = $objectManager->create(Payment::class);
    $payment->setMethod($orderData['payment_method']);

    /** @var OrderItem $orderItem */
    $orderItem = $objectManager->create(OrderItem::class);
    $orderItem->setProductId($product->getId())
        ->setQtyOrdered(1)
        ->setBasePrice($orderData['subtotal'])
        ->setPrice($orderData['subtotal'])
        ->setRowTotal($orderData['subtotal'])
        ->setProductType('simple')
        ->setName($product->getName())
        ->setSku($product->getSku());

    $order->setIncrementId($orderData['increment_id'])
        ->setState($orderData['state'])
        ->setStatus($orderData['status'])
        ->setGrandTotal($orderData['grand_total'])
        ->setBaseGrandTotal($orderData['base_grand_total'])
        ->setSubtotal($orderData['subtotal'])
        ->setStoreId($orderData['store_id'])
        ->setWebsiteId($orderData['website_id'])
        ->setCustomerEmail($orderData['customer_email'])
        ->setCustomerIsGuest(true)
        ->setCreatedAt($orderData['created_at'])
        ->addItem($orderItem)
        ->setBillingAddress($billingAddress)
        ->setShippingAddress($shippingAddress)
        ->setPayment($payment);

    $orderRepository->save($order);
}