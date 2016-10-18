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

use GuzzleHttp\ClientInterface as HttpClientInterface;

/**
 * Interface ClientInterface.
 */
interface ClientInterface
{
    const VERSION = '0.1';

    /**
     * @return string|int
     */
    public function getClientId();

    /**
     * @param int|string $clientId
     *
     * @return static
     */
    public function setClientId($clientId);

    /**
     * @link http://docs.guzzlephp.org/en/latest/request-options.html#proxy
     *
     * @param string|array $proxy
     *
     * @return static
     */
    public function setProxy($proxy = null);

    /**
     * @param HttpClientInterface $httpClient
     *
     * @return static
     */
    public function setHttpClient(HttpClientInterface $httpClient);

    /**
     * @param int $timeout
     *
     * @return static
     */
    public function setTimeout($timeout);

    /**
     * @param int|float $queryPerSecond
     *
     * @return static
     */
    public function setQps($queryPerSecond);

    /**
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale);

    /**
     * @param string $version
     *
     * @return static
     */
    public function setVersion($version);

    /**
     * @param array $options
     *
     * @return static
     */
    public function setOptions(array $options = null);

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     */
    public function setOption($key, $value = null);

    /**
     * @return array
     */
    public function getHistory();
}
