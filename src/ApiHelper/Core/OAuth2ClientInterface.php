<?php

namespace ApiHelper\Core;

/**
 * Interface OAuth2ClientInterface.
 */
interface OAuth2ClientInterface extends ClientInterface
{
    /**
     * @link http://tools.ietf.org/html/rfc6749#section-4.1.1
     *
     * @param string $state
     * @param array  $params
     *
     * @return string
     */
    public function getAuthorizationUrl($state = null, array $params = []);

    /**
     * @link http://tools.ietf.org/html/rfc6749#section-4.1.3
     *
     * @param string $code
     * @param array  $params
     *
     * @return array
     */
    public function authorize($code, array $params = []);

    /**
     * @link http://tools.ietf.org/html/rfc6749#section-6
     *
     * @param string $refreshToken
     * @param array  $params
     *
     * @return array
     */
    public function refreshAccessToken($refreshToken, array $params = []);

    /**
     * @return string
     */
    public function getAccessToken();

    /**
     * @param string $token
     *
     * @return static
     */
    public function setAccessToken($token = null);
}
