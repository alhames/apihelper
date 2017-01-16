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
use ApiHelper\Exception\ServiceUnavailableException;
use ApiHelper\Exception\UnknownContentTypeException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class OAuth2Client.
 */
abstract class AbstractOAuth2Client extends AbstractClient implements OAuth2ClientInterface
{
    /** @var string */
    protected static $scopeDelimiter = ' ';

    /** @var string */
    protected $redirectUri;

    /** @var array */
    protected $scope;

    /** @var string */
    protected $accessToken;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        if (isset($config['redirect_uri'])) {
            $this->setRedirectUri($config['redirect_uri']);
        }

        if (isset($config['scope'])) {
            $this->setScope($config['scope']);
        }

        if (isset($config['access_token'])) {
            $this->setAccessToken($config['access_token']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl($state = null, array $params = [])
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

        return $this->getAuthorizeUrl($params);
    }

    /**
     * {@inheritdoc}
     */
    public function authorize($code, array $params = [])
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

        $response = $this->httpRequest('POST', $this->getTokenUrl(), ['form_params' => $params]);
        // todo: log

        return $this->handleTokenResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshAccessToken($refreshToken, array $params = [])
    {
        $params['grant_type'] = 'refresh_token';
        $params['refresh_token'] = $refreshToken;
        $params['client_id'] = $this->clientId;
        $params['client_secret'] = $this->clientSecret;

        $response = $this->httpRequest('POST', $this->getTokenUrl(), ['form_params' => $params]);
        // todo: log

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
        $this->accessToken = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    /**
     * @param array|string $scope
     *
     * @return static
     */
    public function setScope($scope)
    {
        $this->scope = is_array($scope) ? $scope : explode(static::$scopeDelimiter, $scope);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
        if (null !== $this->accessToken) {
            $options['access_token'] = $this->accessToken;
        }

        return $options;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function handleTokenResponse(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if (500 <= $statusCode) {
            $e = new ServiceUnavailableException();
            $e->setResponse($response);
            throw $e;
        }

        $contentTypes = $response->getHeader('content-type');
        if (empty($contentTypes) || (0 !== strpos($contentTypes[0], 'application/json') && 0 !== strpos($contentTypes[0], 'text/javascript'))) {
            $e = new UnknownContentTypeException();
            $e->setResponse($response);
            if (!empty($contentTypes)) {
                $e->setContentType($contentTypes[0]);
            }
            throw $e;
        }

        $data = json_decode($response->getBody()->getContents(), true);
        if (200 !== $statusCode) {
            $e = new RequestTokenException();
            $e->setResponse($response);

            if (isset($data['error'])) {
                $e->setError(is_array($data['error']) ? json_encode($data['error']) : $data['error']);
            }

            if (isset($data['error_description'])) {
                $e->setDescription($data['error_description']);
            }

            if (isset($data['error_uri'])) {
                $e->setUri($data['error_uri']);
            }

            throw $e;
        }

        if (isset($data['access_token'])) {
            $this->setAccessToken($data['access_token']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredOptions()
    {
        return ['client_id', 'client_secret'];
    }

    /**
     * @param array $query
     *
     * @return string
     */
    abstract protected function getAuthorizeUrl(array $query);

    /**
     * @return string
     */
    abstract protected function getTokenUrl();

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            parent::serialize(),
            $this->redirectUri,
            $this->scope,
            $this->accessToken,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $parentStr,
            $this->redirectUri,
            $this->scope,
            $this->accessToken
        ) = unserialize($serialized);

        parent::unserialize($parentStr);
    }
}
