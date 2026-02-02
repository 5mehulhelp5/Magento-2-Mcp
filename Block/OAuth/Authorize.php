<?php

declare(strict_types=1);

namespace Freento\Mcp\Block\OAuth;

use Freento\Mcp\Service\OAuthService;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Authorize extends Template
{
    /**
     * @param Context $context
     * @param OAuthService $oauthService
     * @param RequestInterface $request
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly OAuthService $oauthService,
        private readonly RequestInterface $request,
        private readonly FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get client id
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->request->getParam('client_id');
    }

    /**
     * Get client name
     *
     * @return string|null
     */
    public function getClientName(): ?string
    {
        $clientId = $this->getClientId();
        if (!$clientId) {
            return null;
        }

        $client = $this->oauthService->getClientByClientId($clientId);
        return $client?->getName();
    }

    /**
     * Get redirect uri
     *
     * @return string|null
     */
    public function getRedirectUri(): ?string
    {
        return $this->request->getParam('redirect_uri');
    }

    /**
     * Get state
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->request->getParam('state');
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->request->getParam('error');
    }

    /**
     * Get code challenge
     *
     * @return string|null
     */
    public function getCodeChallenge(): ?string
    {
        return $this->request->getParam('code_challenge');
    }

    /**
     * Get code challenge method
     *
     * @return string|null
     */
    public function getCodeChallengeMethod(): ?string
    {
        return $this->request->getParam('code_challenge_method');
    }

    /**
     * Get form action
     *
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->getUrl('freento_mcp/oauth/authorizePost');
    }

    /**
     * Get form key
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
