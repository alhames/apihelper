<?php

/*
 * This file is part of the API Helper package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelper\Core;

use ApiHelper\Exception\InvalidArgumentException;
use ApiHelper\Exception\RequestTokenException;
use ApiHelper\Exception\UnknownResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class OAuth2Client.
 */
abstract class AbstractOAuth2Client extends AbstractClient implements OAuth2ClientInterface
{
    /** @var string */
    protected static $authorizeUri;

    /** @var string */
    protected static $tokenUri;

    /** @var string */
    protected static $scopeDelimiter = ' ';

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $redirectUri;

    /** @var array */
    protected $scope;

    /** @var string */
    protected $display;

    /** @var string */
    protected $accessToken;

    /** @var string */
    protected $refreshToken;

    /** @var int */
    protected $tokenExpiresAt;

    /** @var int|string */
    protected $accountId;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config)
    {
        if (!isset($config['client_id'])) {
            throw new InvalidArgumentException('Undefined options: client_id.');
        }

        if (!isset($config['client_secret'])) {
            throw new InvalidArgumentException('Undefined options: client_secret.');
        }

        parent::__construct($config);

        $this->clientSecret = $config['client_secret'];

        if (isset($config['redirect_uri'])) {
            $this->redirectUri = $config['redirect_uri'];
        }

        if (isset($config['scope'])) {
            $this->scope = is_array($config['scope'])
                ? $config['scope']
                : explode(static::$scopeDelimiter, $config['scope']);
        }

        if (isset($config['access_token'])) {
            $this->setAccessToken($config['access_token']);
        }

        if (isset($config['refresh_token'])) {
            $this->setRefreshToken($config['refresh_token']);
        }

        if (isset($config['display'])) {
            $this->display = $config['display'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthUrl($state = null, array $params = [])
    {
        $params['response_type'] = 'code';
        $params['client_id'] = $this->clientId;

        if (null !== $this->redirectUri) {
            $params['redirect_uri'] = $this->redirectUri;
        }

        if (!empty($this->scope)) {
            $params['scope'] = implode(static::$scopeDelimiter, $this->scope);
        }

        if (null !== $state) {
            $params['state'] = $state;
        }

        if (null !== $this->display) {
            $params['display'] = $this->display;
        }

        return $this->getAuthorizeUri($params);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAccessToken($code, array $params = [])
    {
        if (empty($code) || !is_string($code)) {
            throw new InvalidArgumentException('Code for access token request is invalid or empty.');
        }

        $params['grant_type'] = 'authorization_code';
        $params['code'] = $code;
        $params['client_id'] = $this->clientId;
        $params['client_secret'] = $this->clientSecret;

        if (null !== $this->redirectUri) {
            $params['redirect_uri'] = $this->redirectUri;
        }

        $response = $this->httpRequest('POST', $this->getTokenUri(), ['form_params' => $params]);

        return $this->handleTokenResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshAccessToken($refreshToken = null, array $params = [])
    {
        if (null !== $refreshToken) {
            $this->setRefreshToken($refreshToken);
        }

        $params['grant_type'] = 'refresh_token';
        $params['refresh_token'] = $this->refreshToken;
        $params['client_id'] = $this->clientId;
        $params['client_secret'] = $this->clientSecret;

        $response = $this->httpRequest('POST', $this->getTokenUri(), ['form_params' => $params]);

        return $this->handleTokenResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken($token = null)
    {
        if ($this->accessToken !== $token) {
            $this->accessToken = $token;
            $this->accountId = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function setRefreshToken($token = null)
    {
        $this->refreshToken = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenExpiresAt()
    {
        return $this->tokenExpiresAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param int|string $accountId
     *
     * @return self
     */
    public function setAccountId($accountId)
    {
        if (null === $this->accountId) {
            $this->accountId = $accountId;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function apiRequest($method, array $params = [], $type = 'GET')
    {
        if (null !== $this->accessToken) {
            $params['access_token'] = $this->accessToken;
        }

        return parent::apiRequest($method, $params, $type);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    protected function getAuthorizeUri(array $params)
    {
        return static::$authorizeUri.'?'.http_build_query($params);
    }

    /**
     * @return string
     */
    protected function getTokenUri()
    {
        return static::$tokenUri;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function handleTokenResponse(ResponseInterface $response)
    {
        $result = $this->parseResponse($response);

        if ('json' !== $result['type']) {
            throw new UnknownResponseException($response, $result['contents']);
        }

        $data = json_decode($result['contents'], true);

        if (200 !== $result['status']) {
            $error = isset($data['error']) ? $data['error'] : null;
            throw new RequestTokenException(
                $response,
                is_array($error) ? json_encode($error) : $error,
                isset($data['error_description']) ? $data['error_description'] : null,
                isset($data['error_uri']) ? $data['error_uri'] : null
            );
        }

        if (isset($data['access_token'])) {
            $this->setAccessToken($data['access_token']);
        }

        if (isset($data['refresh_token'])) {
            $this->setRefreshToken($data['refresh_token']);
        }

        if (!empty($data['expires_in'])) {
            $this->tokenExpiresAt = time() + $data['expires_in'];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            parent::serialize(),
            $this->clientSecret,
            $this->redirectUri,
            $this->scope,
            $this->display,
            $this->accessToken,
            $this->refreshToken,
            $this->tokenExpiresAt,
            $this->accountId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $parentStr,
            $this->clientSecret,
            $this->redirectUri,
            $this->scope,
            $this->accessToken,
            $this->refreshToken,
            $this->display,
            $this->tokenExpiresAt,
            $this->accountId
            ) = unserialize($serialized);

        parent::unserialize($parentStr);
    }
}
