<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\OAuth;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Client extends AbstractDb
{
    private const TABLE_NAME = 'freento_mcp_oauth_client';
    private const ID_FIELD = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD);
    }

    /**
     * Get by client id
     *
     * @param string $clientId
     * @return array|null
     * @throws LocalizedException
     */
    public function getByClientId(string $clientId): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('client_id = ?', $clientId);

        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Get by entity id
     *
     * @param int $entityId
     * @return array|null
     * @throws LocalizedException
     */
    public function getByEntityId(int $entityId): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('entity_id = ?', $entityId);

        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Get by token hash
     *
     * @param string $tokenHash
     * @return array|null
     * @throws LocalizedException
     */
    public function getByTokenHash(string $tokenHash): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('token_hash = ?', $tokenHash)
            ->where('is_active = ?', 1);

        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Update token
     *
     * @param int $entityId
     * @param string $tokenHash
     * @param int $adminUserId
     * @return void
     * @throws LocalizedException
     */
    public function updateToken(int $entityId, string $tokenHash, int $adminUserId): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'token_hash' => $tokenHash,
                'admin_user_id' => $adminUserId
            ],
            ['entity_id = ?' => $entityId]
        );
    }

    /**
     * Get by otp hash
     *
     * @param string $otpHash
     * @return array|null
     * @throws LocalizedException
     */
    public function getByOtpHash(string $otpHash): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('otp_hash = ?', $otpHash)
            ->where('is_active = ?', 1);

        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Otp hash exists
     *
     * @param string $otpHash
     * @return bool
     * @throws LocalizedException
     */
    public function otpHashExists(string $otpHash): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['entity_id'])
            ->where('otp_hash = ?', $otpHash)
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * Update otp
     *
     * @param int $entityId
     * @param string|null $otpHash
     * @param string|null $expiresAt
     * @return void
     * @throws LocalizedException
     */
    public function updateOtp(int $entityId, ?string $otpHash, ?string $expiresAt): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'otp_hash' => $otpHash,
                'otp_expires_at' => $expiresAt
            ],
            ['entity_id = ?' => $entityId]
        );
    }

    /**
     * Update otp with admin
     *
     * @param int $entityId
     * @param string $otpHash
     * @param string $expiresAt
     * @param int $adminUserId
     * @return void
     * @throws LocalizedException
     */
    public function updateOtpWithAdmin(int $entityId, string $otpHash, string $expiresAt, int $adminUserId): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'otp_hash' => $otpHash,
                'otp_expires_at' => $expiresAt,
                'admin_user_id' => $adminUserId
            ],
            ['entity_id = ?' => $entityId]
        );
    }

    /**
     * Clear token
     *
     * @param int $entityId
     * @return void
     * @throws LocalizedException
     */
    public function clearToken(int $entityId): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'token_hash' => null,
                'admin_user_id' => null
            ],
            ['entity_id = ?' => $entityId]
        );
    }

    /**
     * Delete expired otps
     *
     * @return void
     * @throws LocalizedException
     */
    public function deleteExpiredOtps(): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'otp_hash' => null,
                'otp_expires_at' => null
            ],
            ['otp_expires_at < ?' => date('Y-m-d H:i:s')]
        );
    }
}
