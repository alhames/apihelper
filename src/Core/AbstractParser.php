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

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AbstractParser.
 */
abstract class AbstractParser extends AbstractClient implements ParserInterface
{
    /** @var array */
    protected static $httpAcceptLanguage = ['ru-RU', 'ru;q=0.8', 'en-US;q=0.6', 'en;q=0.4'];

    /** @var array */
    protected static $httpAcceptEncoding = ['gzip', 'deflate', 'sdch'];

    /** @var array */
    protected static $httpAccept = ['text/html', 'application/xhtml+xml', 'application/xml;q=0.9', 'image/webp', '*/*;q=0.8'];

    /** @var string */
    protected static $httpUserAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/%s Safari/537.36';

    /** @var string */
    protected $browserVersion = '54.0.2840.59';

    /** @var CookieJarInterface  */
    protected $cookieJar;

    /** @var string */
    protected $cookieSavePath;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        if (isset($config['cookie_save_path'])) {
            $this->setCookieSavePath($config['cookie_save_path']);
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function setCookieSavePath($path)
    {
        $this->cookieSavePath = $path;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null)
    {
        $cookie = ['Name' => $name];

        if (null !== $value) {
            $cookie['Value'] = $value;
        }

        if (null !== $expire) {
            $cookie['Expires'] = (int) $expire;
        }

        if (null !== $path) {
            $cookie['Path'] = $path;
        }

        if (null !== $domain) {
            $cookie['Domain'] = $domain;
        }

        if (null !== $secure) {
            $cookie['Secure'] = (bool) $secure;
        }

        $this->getCookieJar()->setCookie(new SetCookie($cookie));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($uri, array $params = [], $referrer = null)
    {
        $options = [];

        if (!empty($params)) {
            $options[RequestOptions::QUERY] = $params;
        }

        if (!empty($referrer)) {
            $options[RequestOptions::HEADERS]['Referer'] = $referrer;
        }

        $response = $this->httpRequest('GET', $this->getApiUri($uri), $options);

        return $this->handleResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function post($uri, array $params = [], $referrer = null)
    {
        $options = [];

        if (!empty($params)) {
            $options[RequestOptions::FORM_PARAMS] = $params;
        }

        if (!empty($referrer)) {
            $options[RequestOptions::HEADERS]['Referer'] = $referrer;
        }

        $response = $this->httpRequest('POST', $this->getApiUri($uri), $options);

        return $this->handleResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHttpHeaders()
    {
        return [
            'user-agent' => sprintf(static::$httpUserAgent, $this->browserVersion),
            'accept' => implode(',', static::$httpAccept),
            'accept-encoding' => implode(', ', static::$httpAcceptEncoding),
            'accept-language' => implode(',', static::$httpAcceptLanguage),
            'cache-control' => 'max-age=0',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function httpRequest($method, $uri, array $options = [])
    {
        if (!isset($options[RequestOptions::COOKIES])) {
            $options[RequestOptions::COOKIES] = $this->getCookieJar();
        }

        $options[RequestOptions::VERIFY] = false; // todo

        return parent::httpRequest($method, $uri, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleResponse(ResponseInterface $response)
    {
        if ($response->hasHeader('Set-Cookie')) {
            foreach ($response->getHeader('Set-Cookie') as $cookieString) {
                $this->setCookieFromString($cookieString);
            }

            $this->saveCookie();
        }

        if (!in_array($response->getStatusCode(), [301, 302, 303], true)) { // todo
            return $response;
        }

        return $this->get($response->getHeader('Location')[0], [], $this->getLastRequestUri());
    }

    /**
     * @param string $string
     *
     * @return static
     */
    protected function setCookieFromString($string)
    {
        $cookieParams = explode('; ', $string);
        $cookieValue = array_shift($cookieParams);
        $sep = strpos($cookieValue, '=');
        $cookie = [
            'Name' => substr($cookieValue, 0, $sep),
            'Value' => substr($cookieValue, $sep + 1),
        ];

        foreach ($cookieParams as $cookieParam) {
            if ('secure' === strtolower($cookieParam)) {
                $cookie['Secure'] = true;
                continue;
            }

            if ('httponly' === strtolower($cookieParam)) {
                $cookie['HttpOnly'] = true;
                continue;
            }

            $sep = strpos($cookieParam, '=');
            $key = substr($cookieParam, 0, $sep);
            $key = strtolower($key);

            if (in_array($key, ['domain', 'path', 'max-age', 'expires'], true)) {
                $key = 'max-age' === $key ? 'Max-Age' : ucfirst($key);
                $cookie[$key] = substr($cookieParam, $sep + 1);
            }
        }

        $this->getCookieJar()->setCookie(new SetCookie($cookie));

        return $this;
    }

    /**
     * @return CookieJarInterface
     */
    protected function getCookieJar()
    {
        if (null !== $this->cookieJar) {
            return $this->cookieJar;
        }

        if (null !== $this->cookieSavePath && is_file($this->cookieSavePath)) {
            $serialized = file_get_contents($this->cookieSavePath);
            if (!empty($serialized)) {
                $this->cookieJar = unserialize($serialized);
            }
        }

        if (null === $this->cookieJar) {
            $this->cookieJar = new CookieJar();
        }

        return $this->cookieJar;
    }

    /**
     * @return bool
     */
    protected function saveCookie()
    {
        if (null === $this->cookieSavePath) {
            return false;
        }

        $dir = dirname($this->cookieSavePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return (bool) file_put_contents($this->cookieSavePath, serialize($this->getCookieJar()));
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            parent::serialize(),
            $this->browserVersion,
            $this->cookieJar,
            $this->cookieSavePath,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $parentStr,
            $this->browserVersion,
            $this->cookieJar,
            $this->cookieSavePath
        ) = unserialize($serialized);

        parent::unserialize($parentStr);
    }
}
