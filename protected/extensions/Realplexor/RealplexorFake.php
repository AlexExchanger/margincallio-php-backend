<?php
namespace ext\Realplexor;

class RealplexorFake extends \CApplicationComponent
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