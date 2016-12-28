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

/**
 * Interface ClientInterface.
 */
interface ClientInterface
{
    const VERSION = '0.2.1';

    /**
     * @param string $apiMethod
     * @param array  $options
     * @param string $httpMethod
     *
     * @return mixed
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET');
}
