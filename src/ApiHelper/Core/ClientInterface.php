<?php

namespace ApiHelper\Core;

/**
 * Interface ClientInterface.
 */
interface ClientInterface
{
    const VERSION = '0.2-alpha';

    /**
     * @param string $apiMethod
     * @param array  $options
     * @param string $httpMethod
     *
     * @return mixed
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET');
}
