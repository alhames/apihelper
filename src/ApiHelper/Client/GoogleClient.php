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
 * Class GoogleClient.
 */
class GoogleClient extends AbstractOAuth2Client
{
    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (400 <= $statusCode && $statusCode < 500) {
            throw $this->createApiException($response, $data, $data['error']['code'], $data['error']['message']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://www.googleapis.com/'.$method;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        if (!isset($query['access_type']) && isset($this->options['access_type'])) {
            $query['access_type'] = $this->options['access_type'];
        }

        if (!isset($query['prompt']) && isset($this->options['prompt'])) {
            $query['prompt'] = $this->options['prompt'];
        }

        if (!isset($query['login_hint']) && isset($this->options['login_hint'])) {
            $query['login_hint'] = $this->options['login_hint'];
        }

        if (!isset($query['include_granted_scopes']) && isset($this->options['include_granted_scopes'])) {
            $query['include_granted_scopes'] = $this->options['include_granted_scopes'];
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }
}
