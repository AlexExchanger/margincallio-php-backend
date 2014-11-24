<?php
namespace ext\Realplexor;

class Realplexor extends \CApplicationComponent
{
    public
        $host,
        $port,
        $realplexor;

    public function init()
    {
        require_once __DIR__ . '/Dklab_Realplexor.php';
        $this->realplexor = new Dklab_Realplexor($this->host, $this->port);
    }

    public function __call($func, $arguments)
    {
        return call_user_func_array([$this->realplexor, $func], $arguments);
    }
}