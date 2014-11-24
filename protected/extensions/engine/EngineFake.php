<?php
namespace ext\engine;

class EngineFake extends \CApplicationComponent
{
    public
        $host,
        $port;

    public function init()
    {

    }

    public function __call($func, $arguments)
    {
        return ['success' => true];
    }
}