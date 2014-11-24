<?php
namespace ext\redis;

class RedisFake extends \CApplicationComponent
{
    public
        $host,
        $port,
        $realplexor;

    public function init()
    {

    }

    public function __call($func, $arguments)
    {
        return true;
    }
}