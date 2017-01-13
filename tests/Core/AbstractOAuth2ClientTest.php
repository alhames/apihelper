<?php

namespace ApiHelper\Tests\Core;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Core\OAuth2ClientInterface;
use ApiHelper\Exception\InvalidArgumentException;

/**
 * Class AbstractOAuth2ClientTest.
 */
abstract class AbstractOAuth2ClientTest extends AbstractClientTest
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyConfigException()
    {
        $class = $this->getClientClass();
        new $class([]);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConfigWithoutClientIdException()
    {
        $class = $this->getClientClass();
        new $class(['client_secret' => $this->config['client_secret']]);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testConfigWithoutClientSecretException()
    {
        $class = $this->getClientClass();
        new $class(['client_id' => $this->config['client_id']]);
    }

    public function testOAuth2Client()
    {
        $class = $this->getClientClass();
        $client = new $class($this->config);

        $this->assertInstanceOf(OAuth2ClientInterface::class, $client);
        $this->assertInstanceOf(AbstractOAuth2Client::class, $client);
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        return array_merge(parent::getProperties(), ['redirect_uri', 'scope', 'access_token']);
    }
}
