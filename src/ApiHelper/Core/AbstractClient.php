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
use ApiHelper\Exception\ServiceUnavailableException;
use ApiHelper\Exception\UnknownContentTypeException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client.
 */
abstract class AbstractClient implements ClientInterface, \Serializable
{
    /** @var string|int */
    protected $clientId;

    /** @var string */
    protected $clientSecret;

    /** @var string */
    protected $version;

    /** @var string */
    protected $locale;

    /** @var array */
    protected $options = [];

    /** @var HttpClientInterface */
    protected $httpClient;

    /** @var string|array */
    protected $proxy;

    /** @var int */
    protected $timeout;

    /** @var float Query per second */
    protected $qps;

    /** @var float */
    protected $lastRequestTime;

    /** @var array */
    protected $history = [];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($this->getRequiredOptions() as $option) {
            if (!isset($config[$option])) {
                throw new InvalidArgumentException('Undefined option: '.$option.'.');
            }
        }

        if (isset($config['client_id'])) {
            $this->setClientId($config['client_id']);
        }

        if (isset($config['client_secret'])) {
            $this->setClientSecret($config['client_secret']);
        }

        if (isset($config['version'])) {
            $this->setVersion($config['version']);
        }

        if (isset($config['locale'])) {
            $this->setLocale($config['locale']);
        }

        if (isset($config['options'])) {
            $this->setOptions($config['options']);
        }

        if (isset($config['timeout'])) {
            $this->setTimeout((int) $config['timeout']);
        }

        if (isset($config['qps'])) {
            $this->setQps((float) $config['qps']);
        }

        if (isset($config['proxy'])) {
            $this->setProxy($config['proxy']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        $config = [];

        if (!empty($options)) {
            if ('GET' === $httpMethod) {
                $config[RequestOptions::QUERY] = $options;
            } elseif ('POST' === $httpMethod) {
                $config[RequestOptions::FORM_PARAMS] = $options;
            } else {
                throw new InvalidArgumentException(sprintf('HTTP method %s is not supported', $httpMethod));
            }
        }

        $response = $this->httpRequest($httpMethod, $this->getApiUrl($apiMethod), $config);

        return $this->handleResponse($response);
    }

    /**
     * @return string|int
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string|int $clientId
     *
     * @return static
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     *
     * @return static
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @link http://docs.guzzlephp.org/en/latest/request-options.html#proxy
     *
     * @param string|array $proxy
     *
     * @return static
     */
    public function setProxy($proxy = null)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * @param HttpClientInterface $httpClient
     *
     * @return static
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @param int $timeout
     *
     * @return static
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param int|float $queryPerSecond
     *
     * @return static
     */
    public function setQps($queryPerSecond)
    {
        $this->qps = $queryPerSecond;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param string $version
     *
     * @return static
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return static
     */
    public function setOptions(array $options = [])
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     */
    public function setOption($key, $value = null)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @return array
     */
    protected function getHttpHeaders()
    {
        return [
            'user-agent' => 'ApiHelper/'.static::VERSION,
            'accept' => 'application/json',
        ];
    }

    /**
     * @return HttpClientInterface
     */
    protected function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new HttpClient();
        }

        return $this->httpClient;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return ResponseInterface
     */
    protected function httpRequest($method, $uri, array $options = [])
    {
        if (null !== $this->proxy) {
            $options[RequestOptions::PROXY] = $this->proxy;
        }

        if (!isset($options[RequestOptions::HEADERS])) {
            $options[RequestOptions::HEADERS] = $this->getHttpHeaders();
        } else {
            $options[RequestOptions::HEADERS] = array_merge($this->getHttpHeaders(), $options[RequestOptions::HEADERS]);
        }

        if (!isset($options[RequestOptions::HTTP_ERRORS])) {
            $options[RequestOptions::HTTP_ERRORS] = false;
        }

        if (!isset($options[RequestOptions::ALLOW_REDIRECTS])) {
            $options[RequestOptions::ALLOW_REDIRECTS] = false;
        }

        if (!isset($options[RequestOptions::TIMEOUT]) && null !== $this->timeout) {
            $options[RequestOptions::TIMEOUT] = $this->timeout;
        }

        if (null !== $this->qps && null !== $this->lastRequestTime) {
            $lastInterval = floor((microtime(true) - $this->lastRequestTime) * 1000000);
            $timeout = ceil(1000000 / $this->qps);
            if ($lastInterval < $timeout) {
                usleep($timeout - $lastInterval);
            }
        }

        $response = $this->getHttpClient()->request($method, $uri, $options);
        $this->lastRequestTime = microtime(true);
        $this->history[] = [
            'method' => $method,
            'uri' => $uri,
            'time' => $this->lastRequestTime,
            'status' => $response->getStatusCode(),
        ];

        return $response;
    }

    /**
     * @return string
     */
    protected function getLastRequestUri()
    {
        if (empty($this->history)) {
            return;
        }

        return $this->history[count($this->history) - 1]['uri'];
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array [(int) status, (string) type, (string) contents]
     */
    protected function parseResponse(ResponseInterface $response)
    {
        $data = ['status' => (int) $response->getStatusCode()];

        if (500 <= $data['status'] && $data['status'] < 600) {
            throw new ServiceUnavailableException($response);
        }

        $contentTypes = $response->getHeader('content-type');

        if (empty($contentTypes)) {
            throw new UnknownContentTypeException($response);
        }

        $contentType = explode(';', $contentTypes[0])[0];

        switch ($contentType) {
            case 'text/javascript':
            case 'application/json':
                $data['type'] = 'json';
                break;

            case 'text/xml':
            case 'application/xml':
            case 'application/atom+xml':
            case 'application/xhtml+xml':
                $data['type'] = 'xml';
                break;

            case 'text/html':
                $data['type'] = 'html';
                break;

            case 'text/plain':
                $data['type'] = 'text';
                break;

            default:
                throw new UnknownContentTypeException($response, $contentType);
        }

        $data['contents'] = $response->getBody()->getContents();

        return $data;
    }

    /**
     * @return array
     */
    protected function getRequiredOptions()
    {
        return [];
    }

    /**
     * @param string $method
     *
     * @return string
     */
    abstract protected function getApiUrl($method);

    /**
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    abstract protected function handleResponse(ResponseInterface $response);

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->clientId,
            $this->clientSecret,
            $this->version,
            $this->locale,
            $this->timeout,
            $this->qps,
            $this->proxy,
            $this->options,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->clientId,
            $this->clientSecret,
            $this->version,
            $this->locale,
            $this->timeout,
            $this->qps,
            $this->proxy,
            $this->options
        ) = unserialize($serialized);
    }
}
