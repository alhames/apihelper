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

use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\InvalidArgumentException;
use ApiHelper\Exception\ServiceUnavailableException;
use ApiHelper\Exception\UnknownContentTypeException;
use ApiHelper\Exception\UnknownResponseException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class Client.
 */
abstract class AbstractClient implements ClientInterface, \Serializable, LoggerAwareInterface
{
    use LoggerAwareTrait;

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

    /** @var string|array */
    protected $proxy;

    /** @var int */
    protected $timeout;

    /** @var float Query per second */
    protected $qps;

    /** @var HttpClientInterface */
    protected $httpClient;

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
        $options = $this->prepareRequestOptions($options, $apiMethod);

        if (!empty($options)) {
            if ('GET' === $httpMethod) {
                $config[RequestOptions::QUERY] = $options;
            } elseif ('POST' === $httpMethod) {
                $config[RequestOptions::FORM_PARAMS] = $options;
            } else {
                throw new InvalidArgumentException(sprintf('HTTP method %s is not supported', $httpMethod));
            }
        }

        return $this->apiRequest($httpMethod, $this->getApiUrl($apiMethod), $config);
    }

    /**
     * @param string         $apiMethod
     * @param array          $options
     * @param \SplFileInfo[] $files
     * @param string         $httpMethod
     *
     * @return mixed
     */
    public function multipartRequest($apiMethod, array $options = [], array $files = [], $httpMethod = 'POST')
    {
        $config = [RequestOptions::MULTIPART => []];
        $options = $this->prepareRequestOptions($options, $apiMethod);

        foreach ($options as $key => $value) {
            $config[RequestOptions::MULTIPART][] = ['name' => $key, 'contents' => (string) $value];
        }

        foreach ($files as $key => $file) {
            $config[RequestOptions::MULTIPART][] = ['name' => $key, 'contents' => fopen($file->getRealPath(), 'r')];
        }

        return $this->apiRequest($httpMethod, $this->getApiUrl($apiMethod), $config);
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
     * @todo
     *
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
     * @param string $method
     * @param string $uri
     * @param array  $config
     *
     * @return mixed
     */
    protected function apiRequest($method, $uri, array $config = [])
    {
        $response = $this->httpRequest($method, $uri, $config);
        $responseStatusCode = $response->getStatusCode();

        // todo: log

        if (500 <= $responseStatusCode) {
            $e = new ServiceUnavailableException();
            $e->setResponse($response);
            throw $e;
        }

        $contentTypes = $response->getHeader('content-type');

        if (empty($contentTypes)) {
            $e = new UnknownContentTypeException();
            $e->setResponse($response);
            throw $e;
        }

        $contentType = explode(';', $contentTypes[0])[0];
        $contents = $response->getBody()->getContents();

        switch ($contentType) {
            case 'text/javascript':
            case 'application/json':
                $data = json_decode($contents, true);
                break;

            case 'text/xml':
            case 'application/xml':
            case 'application/atom+xml':
            case 'application/xhtml+xml':
            case 'text/html':
            case 'text/plain':
                $data = $contents;
                break;

            default:
                $e = new UnknownContentTypeException();
                $e->setResponse($response);
                $e->setContentType($contentType);
                throw $e;
        }

        $this->checkResponseError($responseStatusCode, $data, $response);

        if (200 !== $responseStatusCode) {
            throw new UnknownResponseException($response, $contents);
        }

        return $data;
    }

    /**
     * @todo
     *
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
     * @return array
     */
    protected function getRequiredOptions()
    {
        return [];
    }

    /**
     * @param array  $options
     * @param string $apiMethod
     *
     * @return array
     */
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
        return $options;
    }

    /**
     * @param ResponseInterface $response
     * @param string|array      $data
     * @param string|int|null   $code
     * @param string|null       $message
     *
     * @return ApiException
     */
    final protected function createApiException(ResponseInterface $response, $data, $code = null, $message = null)
    {
        $e = new ApiException(get_class($this).' #'.$code.': '.$message);
        $e->setResponse($response);
        $e->setData($data);
        $e->setErrorCode($code);
        $e->setErrorMessage($message);

        return $e;
    }

    /**
     * @param int               $statusCode
     * @param array|string      $data
     * @param ResponseInterface $response
     *
     * @throws ApiException
     */
    abstract protected function checkResponseError($statusCode, $data, ResponseInterface $response);

    /**
     * @param string $method
     *
     * @return string
     */
    abstract protected function getApiUrl($method);

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
