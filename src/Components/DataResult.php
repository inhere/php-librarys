<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 17:27
 */

namespace Inhere\Library\Components;

/**
 * Class DeferredResult
 * @package Inhere\Library\Components
 */
class DataResult
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * DeferredResult constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function get()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}