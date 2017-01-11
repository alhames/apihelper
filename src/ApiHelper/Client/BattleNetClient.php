<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\UnknownResponseException;
use Psr\Http\Message\ResponseInterface;


/**
 * Class BattleNetClient.
 */
class BattleNetClient extends AbstractOAuth2Client
{
    const REGION_US = 'us';
    const REGION_EUROPE = 'eu';
    const REGION_KOREA = 'kr';
    const REGION_TAIWAN = 'tw';
    const REGION_CHINA = 'cn';
    const REGION_SEA = 'sea';

    /** @var array */
    protected static $regionLocales = [
        self::REGION_US => ['en_US', 'es_MX', 'pt_BR'],
        self::REGION_EUROPE => ['en_GB', 'es_ES', 'fr_FR', 'ru_RU', 'de_DE', 'pt_PT', 'it_IT'],
        self::REGION_KOREA => ['ko_KR'],
        self::REGION_TAIWAN => ['zh_TW'],
        self::REGION_CHINA => ['zh_CN'],
        self::REGION_SEA => ['en_US']
    ];

    /**
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        if (null !== $this->locale && !isset($options['locale'])) {
            $options['locale'] = $this->locale;
        }

        $options['apikey'] = $this->clientId;

        return parent::request($apiMethod, $options, $httpMethod);
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return isset($this->options['region']) ? $this->options['region'] : self::REGION_EUROPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        $region = $this->getRegion();
        $domain = self::REGION_CHINA === $region ? 'api.battlenet.com.cn' : $region.'.api.battle.net';

        return 'https://'.$domain.'/'.$method;
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
            throw new ApiException($response, $data['detail'], $data['code']);
        }

        throw new UnknownResponseException($response, $result['contents']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        $region = $this->getRegion();
        $domain = self::REGION_CHINA === $region ? 'www.battlenet.com.cn' : $region.'.battle.net';

        return 'https://'.$domain.'/oauth/authorize?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $region = $this->getRegion();
        $domain = self::REGION_CHINA === $region ? 'www.battlenet.com.cn' : $region.'.battle.net';

        return 'https://'.$domain.'/oauth/token';
    }
}
