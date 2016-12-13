<?php

namespace ApiHelper\Tests\Client;

use ApiHelper\Client\FacebookClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class FaceBookClientTest.
 */
class FaceBookClientTest extends TestCase
{
    /** @var \GuzzleHttp\Client */
    protected $guzzle;

    /** @var FacebookClient */
    protected $client;

    /** @var array  */
    protected $config = [];

    protected function setUp()
    {
        $this->guzzle = $this->createMock('GuzzleHttp\Client');
        $this->guzzle->method('request')
            ->willReturnCallback([$this, 'httpRequestMock']);

        $this->config['client_id'] = getenv('FACEBOOK_CLIENT_ID') ?: 'fb_id';
        $this->config['client_secret'] = getenv('FACEBOOK_CLIENT_SECRET') ?: 'fb_secret';

        $this->client = new FacebookClient($this->config);
        $this->client->setHttpClient($this->guzzle);
    }

    /**
     * @expectedException \ApiHelper\Exception\InvalidArgumentException
     */
    public function testEmptyConfigException()
    {
        new FacebookClient([]);
    }

    /**
     * @expectedException \ApiHelper\Exception\InvalidArgumentException
     */
    public function testConfigWithoutClientIdException()
    {
        new FacebookClient(['client_secret' => $this->config['client_secret']]);
    }

    /**
     * @expectedException \ApiHelper\Exception\InvalidArgumentException
     */
    public function testConfigWithoutClientSecretException()
    {
        new FacebookClient(['client_id' => $this->config['client_id']]);
    }

    public function testClient()
    {
        $this->assertInstanceOf(FacebookClient::class, $this->client);

        $redirectUri = 'https://www.facebook.com/connect/login_success.html';
        $authorizationUrl = 'https://www.facebook.com/v2.8/dialog/oauth?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $redirectUri,
        ]);

        $this->assertAttributeEquals($this->config['client_id'], 'clientId', $this->client);
        $this->assertAttributeEquals($this->config['client_secret'], 'clientSecret', $this->client);
        $this->assertAttributeEquals($redirectUri, 'redirectUri', $this->client);
        $this->assertAttributeEquals('2.8', 'version', $this->client);

        $this->assertSame($this->config['client_id'], $this->client->getClientId());
        $this->assertNull($this->client->getAccessToken());
        $this->assertSame($authorizationUrl, $this->client->getAuthorizationUrl());
    }

    public function testSerialization()
    {

    }

    /**
     * @param       $method
     * @param       $uri
     * @param array $options
     *
     * @return Response
     */
    protected function httpRequestMock($method, $uri, array $options = [])
    {
        $headers = ['Content-Type' => 'application/json'];
        $r = new Response(200, $headers, json_encode($uri));

        return $r;
    }
}
