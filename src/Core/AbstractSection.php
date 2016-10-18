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
