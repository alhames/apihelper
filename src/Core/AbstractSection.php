<?php

namespace ApiHelper\Core;

/**
 * Class AbstractSection.
 */
abstract class AbstractSection
{
    /** @var AbstractClient */
    protected $client;

    /**
     * @param AbstractClient $client
     */
    public function __construct(AbstractClient $client)
    {
        $this->client = $client;
    }
}
