<?php
namespace ext\CsvParser;

abstract class CsvParserBank implements ICsvParser
{
    protected function _parseAccountId($comment)
    {
        //ищем user accountPublicId
        if (preg_match('~(USD|BTC)\-U[0-9A-Fa-f]+\-A[0-9A-Fa-f]+~us', $comment, $m)) {
            return \StringGenerator::validateAccountPublicId($m[0]) ? $m[0] : null;
        }

        //ищем gateway accountPublicId
        if (preg_match('~(USD|BTC)\-G[0-9A-Fa-f]+\-A[0-9A-Fa-f]+~us', $comment, $m)) {
            return \StringGenerator::validateGatewayPublicId($m[0]) ? $m[0] : null;
        }

        return null;
    }
}