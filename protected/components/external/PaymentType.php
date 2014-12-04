<?php

interface PaymentType {
    public function transferTo();
    public function transferFrom();
}