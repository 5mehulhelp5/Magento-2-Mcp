<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Helper;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Helper for date/time operations with timezone support
 *
 * Centralizes all timezone conversion logic for MCP tools:
 * - Converting user input (local timezone) to UTC for database queries
 * - Converting database values (UTC) to local timezone for display
 * - Generating SQL expressions for timezone-aware date operations
 */
class DateTimeHelper
{
    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Convert local datetime to UTC for database query
     *
     * Dates in Magento database are stored in UTC.
     * User input is expected in store timezone.
     *
     * @param string $datetime Local datetime string (Y-m-d H:i:s)
     * @return string UTC datetime string
     */
    public function convertLocalToUtc(string $datetime): string
    {
        try {
            $configTimezone = $this->timezone->getConfigTimezone();
            $localDate = new \DateTime($datetime, new \DateTimeZone($configTimezone));
            $localDate->setTimezone(new \DateTimeZone('UTC'));
            return $localDate->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $datetime;
        }
    }

    /**
     * Convert UTC datetime to local timezone for display
     *
     * Dates in Magento database are stored in UTC.
     * Output is converted to store timezone for user display.
     *
     * @param string $datetime UTC datetime string from database
     * @return string Local datetime string
     */
    public function convertUtcToLocal(string $datetime): string
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            $utcDate = new \DateTime($datetime, new \DateTimeZone('UTC'));
            $configTimezone = $this->timezone->getConfigTimezone();
            $utcDate->setTimezone(new \DateTimeZone($configTimezone));
            return $utcDate->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $datetime;
        }
    }

    /**
     * Get current UTC offset for configured timezone
     *
     * @return string Offset in format '+03:00' or '-05:00'
     */
    public function getUtcOffset(): string
    {
        try {
            $configTimezone = $this->timezone->getConfigTimezone();
            $now = new \DateTime('now', new \DateTimeZone($configTimezone));
            return $now->format('P');
        } catch (\Exception $e) {
            return '+00:00';
        }
    }

    /**
     * Get configured timezone name
     *
     * @return string Timezone name (e.g., 'Europe/Paris')
     */
    public function getConfigTimezone(): string
    {
        return $this->timezone->getConfigTimezone();
    }
}
