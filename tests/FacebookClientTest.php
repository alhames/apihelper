<?php

namespace ApiHelper\Tests;

use ApiHelper\Client\FacebookClient;
use ApiHelper\Core\AbstractClient;
use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Tests\Core\AbstractOAuth2ClientTest;
use GuzzleHttp\Psr7\Response;

/**
 * Class FacebookClientTest.
 */
class FacebookClientTest extends AbstractOAuth2ClientTest
{
    /** @var array  */
    protected $defaultConfig = [
        'redirect_uri' => 'https://www.facebook.com/connect/login_success.html',
        'version' => '2.8',
        'options' => [],
    ];

    public function testAuthorizationUrl()
    {
        $class = $this->getClientClass();
        /** @var FacebookClient $client */
        $client = new $class($this->config);
        $url = 'https://www.facebook.com/v2.8/dialog/oauth?';
        $options = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
        ];

        if (!empty($this->config['redirect_uri'])) {
            $options['redirect_uri'] = $this->config['redirect_uri'];
        } elseif (!empty($this->defaultConfig['redirect_uri'])) {
            $options['redirect_uri'] = $this->defaultConfig['redirect_uri'];
        }

        $this->assertSame($url.http_build_query($options), $client->getAuthorizationUrl());

        $options['state'] = '12345';
        $this->assertSame($url.http_build_query($options), $client->getAuthorizationUrl(12345));
    }

    /**
     * @dataProvider requestProvider
     *
     * @param $apiMethod
     * @param $options
     * @param $httpMethod
     * @param $result
     */
    public function testRequest($apiMethod, $options, $httpMethod, $result)
    {
        $class = $this->getClientClass();
        /** @var AbstractClient $client */
        $client = new $class($this->config);
        $client->setHttpClient($this->guzzle);

        $this->assertSame($result, $client->request($apiMethod, $options, $httpMethod));
    }

    /**
     * {@inheritdoc}
     */
    protected function httpRequestMock($method, $uri, array $options = [])
    {
        $headers = ['Content-Type' => 'application/json'];
        foreach ($this->requestProvider() as $data) {
            if ($method === $data[2] && $uri === 'https://graph.facebook.com/v2.8/'.$data[0]) {
                $response = new Response(200, $headers, json_encode($data[3]));
                break;
            }
        }
        if (!isset($response)) {
            $response = new Response(404);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceName()
    {
        return 'facebook';
    }

    /**
     * @return array
     */
    public function requestProvider()
    {
        return [
            ['me', [], 'GET', ['name' => 'Pavel Logachev', 'id' => '1084986581532583']]
        ];
    }
}
