<?php
namespace ext\CsvParser;
class CsvRow
{
    public
        $bankTransactionId,
        $bankAccountId,
        $accountId,
        $createdAt,
        $debit,
        $credit,
        $comment;

    function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }
}