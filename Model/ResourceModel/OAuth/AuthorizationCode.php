<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\OAuth;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AuthorizationCode extends AbstractDb
{
    private const TABLE_NAME = 'freento_mcp_oauth_code';
    private const ID_FIELD = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD);
    }

    /**
     * Get by code hash
     *
     * @param string $codeHash
     * @return array|null
     * @throws LocalizedException
     */
    public function getByCodeHash(string $codeHash): ?array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('code_hash = ?', $codeHash);

        $result = $connection->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Delete by code hash
     *
     * @param string $codeHash
     * @return void
     * @throws LocalizedException
     */
    public function deleteByCodeHash(string $codeHash): void
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['code_hash = ?' => $codeHash]
        );
    }

    /**
     * Delete expired
     *
     * @return void
     * @throws LocalizedException
     */
    public function deleteExpired(): void
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['expires_at < ?' => date('Y-m-d H:i:s')]
        );
    }
}
