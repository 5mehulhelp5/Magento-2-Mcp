<?php

declare(strict_types=1);

namespace Freento\Mcp\Test\Integration\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\OrderResource;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for OrderResource
 *
 * @magentoDbIsolation enabled
 */
class OrderResourceTest extends TestCase
{
    private ?OrderResource $orderResource = null;
    private ?Schema $schema = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->orderResource = $objectManager->get(OrderResource::class);
        $this->schema = $this->createSchema();
    }

    /**
     * Create schema for testing
     *
     * @return Schema
     */
    private function createSchema(): Schema
    {
        return new Schema(
            entity: 'order',
            table: 'sales_order',
            fields: [
                new Field(name: 'entity_id', sortable: false),
                new Field(name: 'increment_id', type: 'string'),
                new Field(name: 'status', type: 'string', allowGroupBy: true),
                new Field(name: 'state', sortable: false),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day']
                ),
                new Field(name: 'updated_at', type: 'date'),
                new Field(name: 'customer_id', type: 'integer', allowGroupBy: true),
                new Field(name: 'customer_email', type: 'string', allowGroupBy: true),
                new Field(name: 'customer_firstname', sortable: false),
                new Field(name: 'customer_lastname', sortable: false),
                new Field(
                    name: 'base_grand_total',
                    type: 'currency',
                    allowAggregate: true
                ),
                new Field(name: 'base_currency_code', type: 'string', sortable: false),
                new Field(name: 'order_currency_code', type: 'string', sortable: false),
                new Field(name: 'total_qty_ordered', type: 'numeric', sortable: false, allowAggregate: true),
                new Field(name: 'total_item_count', type: 'integer', sortable: false, allowAggregate: true),
                new Field(name: 'store_id', type: 'integer', allowGroupBy: true),
                new Field(name: 'shipping_description', type: 'string'),
                new Field(
                    name: 'payment_method',
                    type: 'string',
                    column: 'payment.method',
                    allowGroupBy: true
                ),
            ],
            defaultLimit: 50,
            maxLimit: 200
        );
    }

    // =========================================================================
    // Timezone conversion tests
    // =========================================================================

    /**
     * Test that date filter respects store timezone configuration
     *
     * Fixture has orders with UTC timestamps:
     * - 100000201: 2024-01-15 00:00:00 UTC = 2024-01-15 01:00:00 Paris
     * - 100000202: 2024-01-15 12:00:00 UTC = 2024-01-15 13:00:00 Paris
     * - 100000203: 2024-01-15 22:00:00 UTC = 2024-01-15 23:00:00 Paris
     * - 100000204: 2024-01-15 23:30:00 UTC = 2024-01-16 00:30:00 Paris (NEXT DAY!)
     * - 100000205: 2024-01-15 23:59:59 UTC = 2024-01-16 00:59:59 Paris (NEXT DAY!)
     * - 100000206: 2024-01-16 00:00:00 UTC = 2024-01-16 01:00:00 Paris
     *
     * When filtering for 2024-01-15 in Paris timezone:
     * - Orders 100000201, 100000202, 100000203 should be included (still Jan 15 in Paris)
     * - Orders 100000204, 100000205 should be EXCLUDED (already Jan 16 in Paris!)
     * - Order 100000206 should be EXCLUDED (Jan 16 in Paris)
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone Europe/Paris
     */
    public function testDateFilterRespectsStoreTimezone(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['gte' => '2024-01-15', 'lte' => '2024-01-15'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // These orders are on Jan 15 in Paris timezone
        $this->assertContains('100000201', $incrementIds, 'Order at 00:00 UTC (01:00 Paris) should be included');
        $this->assertContains('100000202', $incrementIds, 'Order at 12:00 UTC (13:00 Paris) should be included');
        $this->assertContains('100000203', $incrementIds, 'Order at 22:00 UTC (23:00 Paris) should be included');

        // These orders crossed to Jan 16 in Paris timezone
        $this->assertNotContains('100000204', $incrementIds, 'Order at 23:30 UTC (00:30 Paris next day) should be excluded');
        $this->assertNotContains('100000205', $incrementIds, 'Order at 23:59 UTC (00:59 Paris next day) should be excluded');
        $this->assertNotContains('100000206', $incrementIds, 'Order at 00:00 UTC Jan 16 (01:00 Paris) should be excluded');
    }

    /**
     * Test timezone conversion with UTC timezone (no conversion needed)
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testDateFilterWithUtcTimezone(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['gte' => '2024-01-15', 'lte' => '2024-01-15'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // In UTC timezone, all Jan 15 UTC orders should be included
        $this->assertContains('100000201', $incrementIds);
        $this->assertContains('100000202', $incrementIds);
        $this->assertContains('100000203', $incrementIds);
        $this->assertContains('100000204', $incrementIds);
        $this->assertContains('100000205', $incrementIds);

        // Jan 16 UTC should be excluded
        $this->assertNotContains('100000206', $incrementIds);
    }

    /**
     * Test timezone with negative offset (America/New_York UTC-5)
     *
     * In New York (UTC-5):
     * - 2024-01-15 00:00:00 UTC = 2024-01-14 19:00:00 NY (PREVIOUS DAY!)
     * - 2024-01-15 05:00:00 UTC = 2024-01-15 00:00:00 NY (start of Jan 15)
     * - 2024-01-15 12:00:00 UTC = 2024-01-15 07:00:00 NY
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone America/New_York
     */
    public function testDateFilterWithNegativeTimezoneOffset(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['gte' => '2024-01-15', 'lte' => '2024-01-15'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // Order at 00:00 UTC is still Jan 14 in New York - should be EXCLUDED
        $this->assertNotContains('100000201', $incrementIds, 'Order at 00:00 UTC (19:00 NY previous day) should be excluded');

        // Orders later in the day should be included (they are Jan 15 in NY)
        $this->assertContains('100000202', $incrementIds, 'Order at 12:00 UTC (07:00 NY) should be included');
        $this->assertContains('100000203', $incrementIds, 'Order at 22:00 UTC (17:00 NY) should be included');
        $this->assertContains('100000204', $incrementIds, 'Order at 23:30 UTC (18:30 NY) should be included');
        $this->assertContains('100000205', $incrementIds, 'Order at 23:59 UTC (18:59 NY) should be included');

        // Order at 00:00 UTC Jan 16 = 19:00 NY Jan 15 - should be INCLUDED
        $this->assertContains('100000206', $incrementIds, 'Order at 00:00 UTC Jan 16 (19:00 NY Jan 15) should be included');
    }

    // =========================================================================
    // Date boundary tests (gt/lt operators)
    // =========================================================================

    /**
     * Test GT operator excludes the boundary date entirely
     *
     * gt '2024-01-15' should return orders AFTER 2024-01-15 23:59:59 local time
     * (i.e., starting from 2024-01-16 00:00:00)
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testGtOperatorExcludesBoundaryDate(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['gt' => '2024-01-15'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // All Jan 15 orders should be EXCLUDED (even the one at 23:59:59)
        $this->assertNotContains('100000201', $incrementIds, 'Order at start of Jan 15 should be excluded');
        $this->assertNotContains('100000202', $incrementIds, 'Order at noon Jan 15 should be excluded');
        $this->assertNotContains('100000203', $incrementIds, 'Order at 22:00 Jan 15 should be excluded');
        $this->assertNotContains('100000204', $incrementIds, 'Order at 23:30 Jan 15 should be excluded');
        $this->assertNotContains('100000205', $incrementIds, 'Order at 23:59:59 Jan 15 should be excluded');

        // Only Jan 16 order should be included
        $this->assertContains('100000206', $incrementIds, 'Order on Jan 16 should be included');
    }

    /**
     * Test LT operator excludes the boundary date entirely
     *
     * lt '2024-01-16' should return orders BEFORE 2024-01-16 00:00:00 local time
     * (i.e., up to 2024-01-15 23:59:59)
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testLtOperatorExcludesBoundaryDate(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['lt' => '2024-01-16'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // All Jan 15 orders should be INCLUDED
        $this->assertContains('100000201', $incrementIds, 'Order at start of Jan 15 should be included');
        $this->assertContains('100000202', $incrementIds, 'Order at noon Jan 15 should be included');
        $this->assertContains('100000203', $incrementIds, 'Order at 22:00 Jan 15 should be included');
        $this->assertContains('100000204', $incrementIds, 'Order at 23:30 Jan 15 should be included');
        $this->assertContains('100000205', $incrementIds, 'Order at 23:59:59 Jan 15 should be included');

        // Jan 16 order should be EXCLUDED
        $this->assertNotContains('100000206', $incrementIds, 'Order on Jan 16 should be excluded');
    }

    /**
     * Test combined GT and LT for a single day - should return empty
     *
     * gt '2024-01-15' AND lt '2024-01-16' means:
     * - After 2024-01-15 23:59:59 AND before 2024-01-16 00:00:00
     * - This is an impossible range, should return no results
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testGtLtForSingleDayReturnsEmpty(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['gt' => '2024-01-15', 'lt' => '2024-01-16'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $this->assertEmpty($rows, 'gt Jan 15 AND lt Jan 16 should return no results');
    }

    /**
     * Test GTE includes the boundary date start
     *
     * gte '2024-01-15' should include orders from 2024-01-15 00:00:00
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testGteOperatorIncludesBoundaryDateStart(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['gte' => '2024-01-15'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // Order exactly at midnight should be INCLUDED
        $this->assertContains('100000201', $incrementIds, 'Order at exactly 00:00:00 Jan 15 should be included');

        // All other Jan 15+ orders should also be included
        $this->assertCount(6, $rows, 'All 6 orders should be included');
    }

    /**
     * Test LTE includes the boundary date end
     *
     * lte '2024-01-15' should include orders up to 2024-01-15 23:59:59
     *
     * @magentoDataFixture Freento_Mcp::Test/Integration/Model/ResourceModel/EntityTool/_files/orders_timezone_test.php
     * @magentoConfigFixture default_store general/locale/timezone UTC
     */
    public function testLteOperatorIncludesBoundaryDateEnd(): void
    {
        $result = $this->orderResource->getList(
            $this->schema,
            filters: [
                'created_at' => ['lte' => '2024-01-15'],
                'customer_email' => 'tz_test@example.com'
            ]
        );

        $rows = $result->getRows();
        $incrementIds = array_column($rows, 'increment_id');

        // Order at 23:59:59 should be INCLUDED
        $this->assertContains('100000205', $incrementIds, 'Order at 23:59:59 Jan 15 should be included');

        // All Jan 15 orders should be included
        $this->assertCount(5, $rows, 'All 5 Jan 15 orders should be included');

        // Jan 16 order should be excluded
        $this->assertNotContains('100000206', $incrementIds, 'Order on Jan 16 should be excluded');
    }
}
