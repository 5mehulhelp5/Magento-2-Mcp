<?php

declare(strict_types=1);

namespace Freento\Mcp\Service;

use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;

class OtpService
{
    private const OTP_EXPIRATION_HOURS = 24;

    /**
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     * @param TokenGenerator $tokenGenerator
     */
    public function __construct(
        private ClientFactory $clientFactory,
        private ClientResource $clientResource,
        private TokenGenerator $tokenGenerator
    ) {
    }

    /**
     * Generate new OTP for OAuth client (clears existing token)
     *
     * @param int $clientEntityId
     * @param int $adminUserId
     * @param int $expirationHours
     * @return string Plain OTP (show to user once)
     */
    public function generateOtp(
        int $clientEntityId,
        int $adminUserId,
        int $expirationHours = self::OTP_EXPIRATION_HOURS
    ): string {
        // Clear existing token when generating new OTP
        $this->clientResource->clearToken($clientEntityId);

        // Generate unique OTP
        $otp = $this->generateUniqueOtp();
        $otpHash = $this->tokenGenerator->hash($otp);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expirationHours * 3600));

        $this->clientResource->updateOtpWithAdmin($clientEntityId, $otpHash, $expiresAt, $adminUserId);

        return $otp;
    }

    /**
     * Generate unique OTP that doesn't exist in database
     *
     * @param int $maxAttempts
     * @return string
     * @throws \RuntimeException
     */
    private function generateUniqueOtp(int $maxAttempts = 10): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $otp = $this->tokenGenerator->generate();
            $otpHash = $this->tokenGenerator->hash($otp);

            if (!$this->clientResource->otpHashExists($otpHash)) {
                return $otp;
            }
        }

        throw new \RuntimeException('Failed to generate unique OTP after ' . $maxAttempts . ' attempts');
    }

    /**
     * Validate OTP and return client data if valid
     *
     * @param string $otp
     * @param string $clientId
     * @return array|null ['entity_id' => int, 'admin_user_id' => int] or null if invalid
     */
    public function validateOtp(string $otp, string $clientId): ?array
    {
        // Clean up expired OTPs
        $this->clientResource->deleteExpiredOtps();

        $otpHash = $this->tokenGenerator->hash(trim($otp));
        $data = $this->clientResource->getByOtpHash($otpHash);

        if ($data === null) {
            return null;
        }

        $client = $this->clientFactory->create();
        $client->setData($data);

        // Verify OTP belongs to the requesting client
        if ($client->getClientId() !== $clientId) {
            return null;
        }

        if ($client->isOtpExpired()) {
            $this->clientResource->updateOtp((int)$client->getEntityId(), null, null);
            return null;
        }

        return [
            'entity_id' => (int)$client->getEntityId(),
            'admin_user_id' => (int)$client->getAdminUserId(),
        ];
    }

    /**
     * Get current OTP info for client (without revealing OTP)
     *
     * @param int $clientEntityId
     * @return array|null ['expires_at' => string, 'is_expired' => bool]
     */
    public function getOtpInfo(int $clientEntityId): ?array
    {
        $data = $this->clientResource->getByEntityId($clientEntityId);

        if ($data === null || empty($data['otp_hash'])) {
            return null;
        }

        $expiresAt = $data['otp_expires_at'];
        $isExpired = strtotime($expiresAt) < time();

        return [
            'expires_at' => $expiresAt,
            'is_expired' => $isExpired,
        ];
    }

    /**
     * Revoke OTP for client
     *
     * @param int $clientEntityId
     * @return void
     */
    public function revokeOtp(int $clientEntityId): void
    {
        $this->clientResource->updateOtp($clientEntityId, null, null);
    }
}
