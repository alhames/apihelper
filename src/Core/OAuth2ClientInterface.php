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
    public function getAuthUrl($state = null, array $params = []);

    /**
     * @link http://tools.ietf.org/html/rfc6749#section-4.1.3
     *
     * @param string $code
     * @param array  $params
     *
     * @return array
     */
    public function requestAccessToken($code, array $params = []);

    /**
     * @link http://tools.ietf.org/html/rfc6749#section-6
     *
     * @param string $refreshToken
     * @param array  $params
     *
     * @return array
     */
    public function refreshAccessToken($refreshToken = null, array $params = []);

    /**
     * @return string
     */
    public function getAccessToken();

    /**
     * @param string $token
     *
     * @return self
     */
    public function setAccessToken($token = null);

    /**
     * @return string
     */
    public function getRefreshToken();

    /**
     * @param string $token
     *
     * @return self
     */
    public function setRefreshToken($token = null);

    /**
     * @return int
     */
    public function getTokenExpiresAt();

    /**
     * @return int|string
     */
    public function getAccountId();

    /**
     * @param int|string $accountId
     *
     * @return self
     */
    public function setAccountId($accountId);
}
