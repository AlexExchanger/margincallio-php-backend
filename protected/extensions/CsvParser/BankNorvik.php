<?php
namespace ext\CsvParser;

use \ArrayHelper as A;

class BankNorvik extends CsvParserBank
{
    public function getRow(array $data)
    {

        $arrayDate = date_parse_from_format('d/m/Y', A::getFromArray($data, 3));
        $date = mktime(0, 0, 0, $arrayDate['month'], $arrayDate['day'], $arrayDate['year']);

        $money = (float)A::getFromArray($data, 4);
        $debit = $money > 0 ? $money : 0;
        $credit = $money < 0 ? $money : 0;

        $comment = A::getFromArray($data, 8);
        $accountId = $this->_parseAccountId($comment);

        $bankTransactionId = null;
        if (preg_match('~^([^\s]+)~u', $comment, $m)) {
            $bankTransactionId = $m[1];
        }

        $bankAccountId = A::getFromArray($data, 2);

        if (!$bankTransactionId) {
            return null;
        }

        return new CsvRow([
            'bankTransactionId' => $bankTransactionId,
            'bankAccountId' => $bankAccountId,
            'accountId' => $accountId,
            'createdAt' => $date,
            'debit' => $debit,
            'credit' => $credit,
            'comment' => $comment
        ]);
    }
}