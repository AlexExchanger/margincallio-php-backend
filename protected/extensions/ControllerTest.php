<?php

abstract class ControllerTest extends CDbTestCase
{
    protected function runAction($action, $printBuff = false)
    {
        $this->controller->testMode = true;
        $buff = null;
        try {
            $this->controller->$action();
        }
        catch (TestException $e) {
            $buff = $e->getMessage();
            $buff = json_decode($buff, true);
        }
        if (is_null($buff)) {
            $this->fail('Action did not drop TestException');
        }
        if ($printBuff) {
            print_r($buff);
        }
        return $buff;
    }

    protected function setData(array $data)
    {
        foreach ($data as $k => $v) {
            $_POST[$k] = $v;
        }
    }
}