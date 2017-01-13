<?php

namespace ApiHelper\Tests\Core;

use ApiHelper\Core\AbstractClient;
use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Core\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PhpHelper\Str;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractClientTest.
 */
abstract class AbstractClientTest extends TestCase
{
    /** @var \GuzzleHttp\Client */
    protected $guzzle;

    /** @var array  */
    protected $config = [];

    /** @var array  */
    protected $defaultConfig = [
        'options' => [],
    ];

    /**
     * @return AbstractClient
     */
    public function testClient()
    {
        $class = $this->getClientClass();
        $client = new $class($this->config);

        $this->assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(AbstractClient::class, $client);
        $this->assertInstanceOf($this->getClientClass(), $client);

        foreach ($this->getProperties() as $property) {
            if (array_key_exists($property, $this->config)) {
                $value = $this->config[$property];
            } elseif (array_key_exists($property, $this->defaultConfig)) {
                $value = $this->defaultConfig[$property];
            } else {
                $value = null;
            }

            $this->assertAttributeEquals($value, Str::convertCase($property, Str::CASE_CAMEL_LOWER), $client);
        }

        return $client;
    }

    /**
     * @depends testClient
     *
     * @param AbstractClient $client
     */
    public function testClientId($client)
    {
        $clientId = isset($this->config['client_id']) ? $this->config['client_id'] : null;
        $newClientId = 'test';

        $this->assertAttributeEquals($clientId, 'clientId', $client);
        $this->assertSame($clientId, $client->getClientId());

        $this->assertSame($client, $client->setClientId($newClientId));
        $this->assertAttributeEquals($newClientId, 'clientId', $client);
        $this->assertSame($newClientId, $client->getClientId());

        $client->setClientId($clientId);
    }

    /**
     * @depends testClient
     *
     * @param AbstractClient $client
     */
    public function testClientSecret($client)
    {
        $clientSecret = isset($this->config['client_secret']) ? $this->config['client_secret'] : null;
        $newClientSecret = 'test';

        $this->assertAttributeEquals($clientSecret, 'clientSecret', $client);
        $this->assertSame($clientSecret, $client->getClientSecret());
        $this->assertSame($client, $client->setClientSecret($newClientSecret));
        $this->assertAttributeEquals($newClientSecret, 'clientSecret', $client);
        $this->assertSame($newClientSecret, $client->getClientSecret());

        $client->setClientSecret($clientSecret);
    }

    /**
     * @depends testClient
     *
     * @param AbstractClient $client
     */
    public function testSerialization($client)
    {
        $newClient = unserialize(serialize($client));
        $this->assertInstanceOf($this->getClientClass(), $newClient);
        // todo: check properties
    }

    /**
     *
     */
    protected function setUp()
    {
        $this->guzzle = $this->createMock('GuzzleHttp\Client');
        $this->guzzle->method('request')
            ->willReturnCallback([$this, 'httpRequestMock']);

        $prefix = Str::convertCase($this->getServiceName(), Str::CASE_SNAKE_UPPER).'_';

        foreach ($this->getProperties() as $property) {
            $var = getenv($prefix.Str::convertCase($property, Str::CASE_SNAKE_UPPER));
            if (false !== $var) {
                $this->config[$property] = $var;
            }
        }
    }

    /**
     * @return string
     */
    protected function getClientClass()
    {
        return 'ApiHelper\Client\\'.Str::convertCase($this->getServiceName(), Str::CASE_CAMEL_UPPER).'Client';
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return ['client_id', 'client_secret', 'version', 'locale', 'options', 'timeout', 'qps', 'proxy'];
    }

    /**
     * @param       $method
     * @param       $uri
     * @param array $options
     *
     * @return Response
     */
    abstract protected function httpRequestMock($method, $uri, array $options = []);

    /**
     * @return string
     */
    abstract protected function getServiceName();
}
