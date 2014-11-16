<?php

class PhoneValidator extends CValidator
{
    public $message = 'номер вида +79993335544';

    protected function validateAttribute($object, $attribute)
    {
        $pattern = "/^[+?]\d{4,20}$/";
        if (!preg_match($pattern, $object->$attribute)) {
            $this->addError($object, $attribute, $this->message);
        }
    }
}