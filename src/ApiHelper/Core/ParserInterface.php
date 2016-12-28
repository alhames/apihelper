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

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ParserInterface.
 */
interface ParserInterface extends ClientInterface
{
    /**
     * Путь для сохранения кук между сессиями.
     *
     * @param string $path
     *
     * @return static
     */
    public function setCookieSavePath($path);

    /**
     * @param string $name
     * @param string $value
     * @param int    $expires
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return static
     */
    public function setCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null);

    /**
     * @param string $uri
     * @param array  $params
     * @param string $referrer
     *
     * @return ResponseInterface
     */
    public function get($uri, array $params = [], $referrer = null);

    /**
     * @param string $uri
     * @param array  $params
     * @param string $referrer
     *
     * @return ResponseInterface
     */
    public function post($uri, array $params = [], $referrer = null);

    /**
     * @param ResponseInterface $response
     * @param string            $body
     *
     * @return bool
     */
    public function isAuthenticated(ResponseInterface $response, $body = null);

    /**
     * @param array  $credentials
     * @param string $uri
     *
     * @return ResponseInterface
     */
    public function authenticate(array $credentials, $uri = '/');
}
