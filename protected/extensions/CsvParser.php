<?php

class CsvParser
{
    /**
     * @var \ext\CsvParser\ICsvParser
     */
    private $parser;

    public function __construct($parser)
    {
        switch ($parser) {
            case 'bank.norvik':
                $this->parser = new \ext\CsvParser\BankNorvik();
                break;
            default :
                throw new ModelException(_('Incorrect parser'));
        }
    }

    protected function _parseCsv($text)
    {
        $data = [];
        foreach (preg_split('/\r*\n+|\r+/', $text) as $line) {
            $data[] = str_getcsv($line, ';', '"');
        }
        return $data;
    }

    /**
     * @param $text
     * @return \ext\CsvParser\CsvRow[]
     */
    public function parse($text)
    {
        $data = [];
        foreach ($this->_parseCsv($text) as $row) {
            $parsed = $this->parser->getRow($row);
            if ($parsed) {
                $data[] = $parsed;
            }
        }
        return $data;
    }
}