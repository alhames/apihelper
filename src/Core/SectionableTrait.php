<?php

namespace ApiHelper\Core;

use ApiHelper\Exception\InvalidArgumentException;

/**
 * Class SectionableTrait.
 */
trait SectionableTrait
{
    /** @var array Section-objects */
    protected $sections = [];

    /**
     * @param string $section
     *
     * @throws InvalidArgumentException
     *
     * @return AbstractSection
     */
    public function __get($section)
    {
        if (!isset($this->sections[$section])) {
            $class = get_class($this);
            $pos = strrpos($class, '\\');
            $class = '\\'.substr($class, 0, $pos + 1).ucfirst($section).'Section';

            if (!class_exists($class)) {
                throw new InvalidArgumentException();
            }

            $this->sections[$section] = new $class($this);
        }

        return $this->sections[$section];
    }
}
