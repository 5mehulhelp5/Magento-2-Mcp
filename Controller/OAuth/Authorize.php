<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\OAuth;

use Freento\Mcp\Service\OAuthService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * OAuth 2.0 Authorization endpoint (GET)
 * Displays login form for admin user to authorize the application
 */
class Authorize implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param PageFactory $pageFactory
     * @param RedirectFactory $redirectFactory
     * @param OAuthService $oauthService
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private RequestInterface $request,
        private PageFactory $pageFactory,
        private RedirectFactory $redirectFactory,
        private OAuthService $oauthService,
        private UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $clientId = $this->request->getParam('client_id');
        $redirectUri = $this->request->getParam('redirect_uri');
        $responseType = $this->request->getParam('response_type');
        $state = $this->request->getParam('state');

        // Validate required parameters
        if (!$clientId || !$redirectUri || $responseType !== 'code') {
            return $this->errorRedirect($redirectUri, 'invalid_request', 'Missing required parameters', $state);
        }

        $codeChallenge = $this->request->getParam('code_challenge');
        $codeChallengeMethod = $this->request->getParam('code_challenge_method', 'plain');

        // Validate PKCE code_challenge_method if provided
        if ($codeChallenge && !in_array($codeChallengeMethod, ['plain', 'S256'], true)) {
            return $this->errorRedirect($redirectUri, 'invalid_request', 'Invalid code_challenge_method', $state);
        }

        // Validate client
        $client = $this->oauthService->getClientByClientId($clientId);
        if ($client === null || !$client->isActive()) {
            return $this->errorRedirect($redirectUri, 'invalid_client', 'Unknown client', $state);
        }

        // Show authorization/login page
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Authorize Application'));

        return $page;
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
}
