<?php

namespace ApiHelper\Core;

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
    /** @var string URI for API requests */
    protected static $apiUri;

    /** @var string */
    protected $clientId;

    /** @var string */
    protected $version;

    /** @var string */
    protected $locale;

    /** @var array */
    protected $options;

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
    protected $history;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['client_id'])) {
            $this->setClientId($config['client_id']);
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
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setProxy($proxy = null)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setQps($queryPerSecond)
    {
        $this->qps = $queryPerSecond;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options = null)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($key, $value = null)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
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
        if (null === $this->history) {
            return;
        }

        return $this->history[count($this->history) - 1]['uri'];
    }

    /**
     * @param string $method
     * @param array  $params
     * @param string $type
     *
     * @return mixed
     */
    public function apiRequest($method, array $params = [], $type = 'GET')
    {
        $options = [];

        if (!empty($params)) {
            if ('POST' === $type) {
                $options[RequestOptions::FORM_PARAMS] = $params;
            } else {
                $options[RequestOptions::QUERY] = $params;
            }
        }

        $response = $this->httpRequest($type, $this->getApiUri($method), $options);

        return $this->handleResponse($response);
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
     * @param string $method
     *
     * @return string
     */
    protected function getApiUri($method)
    {
        return static::$apiUri.$method;
    }

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
            $this->version,
            $this->locale,
            $this->timeout,
            $this->qps,
            $this->proxy,
            $this->options
        ) = unserialize($serialized);
    }
}
