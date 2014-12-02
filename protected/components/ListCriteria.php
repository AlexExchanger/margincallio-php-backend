<?php

class ListCriteria
{
    public static function dateCriteria(CDbCriteria $criteria, $dateFrom, $dateTo, $field = 'createdAt')
    {
        if (!empty($dateFrom)) {
            $dateStart = strtotime(date("Y-m-d 00:00:00", strtotime($dateFrom)));
            $dateEnd = empty($dateTo) ? TIME : strtotime(date("Y-m-d  23:59:59", strtotime($dateTo)));
            $criteria->addBetweenCondition($field, $dateStart, $dateEnd);
        } elseif (!empty($dateTo)) {
            $dateEnd = strtotime(date("Y-m-d 23:59:59", strtotime($dateTo)));
            $dateStart = empty($dateFrom) ? 0 : strtotime(date("Y-m-d 00:00:00", strtotime($dateFrom)));
            $criteria->addBetweenCondition($field, $dateStart, $dateEnd);
        }
    }
    
    public static function timestampCriteria(CDbCriteria $criteria, $from, $to, $field = '"createdAt"') {
        if (!empty($from)) {
            $end = empty($to) ? TIME : $to;
            $criteria->addBetweenCondition($field, $from, $end);
        } elseif (!empty($to)) {
            $start = empty($from) ? 0 : $from;
            $criteria->addBetweenCondition($field, $start, $to);
        }
    }

    public static function amountCriteria(CDbCriteria $criteria, $amountFrom, $amountTo, $field = 'amount')
    {
        if (!empty($amountFrom)) {
            $amountStart = $amountFrom;
            $amountEnd = empty($amountTo) ? PHP_INT_MAX : $amountTo;
            $criteria->addBetweenCondition($field, $amountStart, $amountEnd);
        } elseif (!empty($amountTo)) {
            $amountEnd = $amountTo;
            $amountStart = empty($amountFrom) ? 0 : $amountFrom;
            $criteria->addBetweenCondition($field, $amountStart, $amountEnd);
        }
    }

    public static function sortCriteria(CDbCriteria $criteria, $sort, array $fields)
    {
        if (!empty($sort)) {
            $sort = explode(':', $sort);
            if (is_array($sort)) {
                if (in_array($sort[0], $fields, true)) {
                    $criteria->order = $sort[0] . ' ' . ((isset($sort[1]) && $sort[1] == 'desc') ? 'desc' : 'asc');
                }
            }
        }

    }
}