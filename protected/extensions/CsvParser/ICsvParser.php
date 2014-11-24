<?php
namespace ext\CsvParser;

interface ICsvParser
{
    public function getRow(array $data);
}