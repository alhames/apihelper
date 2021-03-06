<?php

/*
 * This file is part of the API Helper package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class YandexClient.
 *
 * @link https://tech.yandex.ru/passport/doc/dg/reference/request-docpage/
 */
class YandexClient extends AbstractOAuth2Client
{
    /**
     * {@inheritdoc}
     */
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
        if (!isset($options['format'])) {
            $options['format'] = 'json';
        }

        $options['oauth_token'] = $this->accessToken;

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (400 <= $statusCode && $statusCode < 500) {
            throw $this->createApiException($response, $data);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://login.yandex.ru/'.$method;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        return 'https://oauth.yandex.ru/authorize?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.yandex.ru/token';
    }
}
