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
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\UnknownResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GoogleClient.
 */
class GoogleClient extends AbstractOAuth2Client
{
    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl($state = null, array $params = [])
    {
        if (!isset($params['access_type']) && isset($this->options['access_type'])) {
            $params['access_type'] = $this->options['access_type'];
        }
//        $params['prompt'] = 'none'; // none|consent|select_account
//        $params['login_hint'] = '';

        return parent::getAuthorizationUrl($state, $params);
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
    protected function handleResponse(ResponseInterface $response)
    {
        $result = $this->parseResponse($response);

        if ('json' !== $result['type']) {
            throw new UnknownResponseException($response, $result['contents']);
        }

        $data = json_decode($result['contents'], true);

        if (200 === $result['status']) {
            return $data;
        }

        if (400 <= $result['status'] && $result['status'] < 500) {
            throw new ApiException($response, $data['error']['message'], $data['error']['code']);
        }

        throw new UnknownResponseException($response, $result['contents']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
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
