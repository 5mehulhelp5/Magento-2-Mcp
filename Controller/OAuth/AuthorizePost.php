<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\OAuth;

use Freento\Mcp\Service\OAuthService;
use Freento\Mcp\Service\OtpService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * OAuth 2.0 Authorization endpoint (POST)
 * Handles authorization using OTP (One-Time Password)
 */
class AuthorizePost implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param OAuthService $oauthService
     * @param OtpService $otpService
     * @param FormKeyValidator $formKeyValidator
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        private RequestInterface $request,
        private RedirectFactory $redirectFactory,
        private OAuthService $oauthService,
        private OtpService $otpService,
        private FormKeyValidator $formKeyValidator,
        private UrlInterface $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $clientId = $this->request->getParam('client_id');
        $redirectUri = $this->request->getParam('redirect_uri');
        $state = $this->request->getParam('state');
        $pin = $this->request->getParam('pin');
        $codeChallenge = $this->request->getParam('code_challenge');
        $codeChallengeMethod = $this->request->getParam('code_challenge_method');

        // Validate form key
        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->redirectWithError(
                'Invalid form key',
                $clientId,
                $redirectUri,
                $state,
                $codeChallenge,
                $codeChallengeMethod
            );
        }

        // Validate client
        $client = $this->oauthService->getClientByClientId($clientId);
        if ($client === null || !$client->isActive()) {
            return $this->errorRedirect($redirectUri, 'invalid_client', 'Unknown client', $state);
        }

        // Validate input
        if (empty($pin)) {
            return $this->redirectWithError(
                'OTP is required',
                $clientId,
                $redirectUri,
                $state,
                $codeChallenge,
                $codeChallengeMethod
            );
        }

        try {
            // Validate OTP and get client data
            $otpData = $this->otpService->validateOtp($pin, $clientId);

            if ($otpData === null) {
                return $this->redirectWithError(
                    'Invalid or expired OTP',
                    $clientId,
                    $redirectUri,
                    $state,
                    $codeChallenge,
                    $codeChallengeMethod
                );
            }

            $adminUserId = $otpData['admin_user_id'];

            // Revoke OTP after successful validation
            $this->otpService->revokeOtp($otpData['entity_id']);

            // Create authorization code
            $code = $this->oauthService->createAuthorizationCode(
                $clientId,
                $adminUserId,
                $state,
                $codeChallenge,
                $codeChallengeMethod
            );

            $this->logger->info('OAuth authorization granted via OTP', [
                'client_id' => $clientId,
                'admin_user_id' => $adminUserId,
            ]);

            // Redirect back with code
            $params = ['code' => $code];
            if ($state) {
                $params['state'] = $state;
            }

            $url = $redirectUri . (str_contains($redirectUri, '?') ? '&' : '?') . http_build_query($params);
            return $this->redirectFactory->create()->setUrl($url);
        } catch (\Exception $e) {
            $this->logger->error('OAuth authorization failed: ' . $e->getMessage());
            return $this->redirectWithError(
                'Authorization failed',
                $clientId,
                $redirectUri,
                $state,
                $codeChallenge,
                $codeChallengeMethod
            );
        }
    }

    /**
     * Redirect back to authorize page with error message
     *
     * @param string $error
     * @param string|null $clientId
     * @param string|null $redirectUri
     * @param string|null $state
     * @param string|null $codeChallenge
     * @param string|null $codeChallengeMethod
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectWithError(
        string $error,
        ?string $clientId,
        ?string $redirectUri,
        ?string $state,
        ?string $codeChallenge = null,
        ?string $codeChallengeMethod = null
    ) {
        $baseUrl = $this->urlBuilder->getUrl('freento_mcp/oauth/authorize');
        $params = array_filter([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'error' => $error,
        ]);

        $url = $baseUrl . '?' . http_build_query($params);

        return $this->redirectFactory->create()->setUrl($url);
    }

    /**
     * Error redirect
     *
     * @param string|null $redirectUri
     * @param string $error
     * @param string $description
     * @param string|null $state
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function errorRedirect(?string $redirectUri, string $error, string $description, ?string $state)
    {
        if (!$redirectUri) {
            $redirectUri = $this->urlBuilder->getUrl('');
        }

        $params = [
            'error' => $error,
            'error_description' => $description,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $url = $redirectUri . (str_contains($redirectUri, '?') ? '&' : '?') . http_build_query($params);

        return $this->redirectFactory->create()->setUrl($url);
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
