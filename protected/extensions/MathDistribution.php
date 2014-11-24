<?php

class MathDistribution
{
    public static function logNormal($amountFrom, $amountTo, $amountStep)
    {
        //функция распределения
        //function = 1/x*exp(-(ln(x) - 1/2)^2) from 0.01 to 5
        $function = function ($x) {
            return 1 / $x * exp(-1 * pow(log($x, M_E) - 1 / 2, 2));
        };

        /**
         * не понадобилось
         *
         * |10(dValueTo)        |1000(valueTo)
         * |                    |
         * |                    |
         * |2(dValue)           |200(value)
         * |                    |
         * |0.01(dValueFrom)    |0.1(valueFrom)
         */
        $normalize = function ($dValue, $dValueFrom, $dValueTo, $valueFrom, $valueTo) {
            if ($dValue == $dValueFrom) {
                return $valueFrom;
            }
            return $valueFrom + ($valueTo - $valueFrom) / (($dValueTo - $dValueFrom) / ($dValue - $dValueFrom));
        };

        $amountMatrix = [];
        $currentAmount = null;
        $sumAmountMatrix = 0;
        do {
            if (is_null($currentAmount)) {
                if ($amountFrom < $amountStep) {
                    $currentAmount = $amountStep;
                } else {
                    $currentAmount = floor($amountFrom / $amountStep) * $amountStep;
                }
            }

            $damount = $currentAmount * 5 / $amountTo;
            $famount = $function($damount);
            $sumAmountMatrix += $famount;
            $amountMatrix [] = [
                'amount' => $currentAmount,
                'damount' => $damount,
                'famount' => $famount,
            ];

            $currentAmount += $amountStep;
        } while ($currentAmount <= $amountTo);

        $sumPercent = 0;
        foreach ($amountMatrix as $k => $v) {
            //$percent = round($amountMatrix[$k]['famount'] / $sumAmountMatrix * 100);
            $percent = ($amountMatrix[$k]['famount'] / $sumAmountMatrix);
            $amountMatrix[$k]['percent'] = $percent;
            $sumPercent += $percent;
        }

        return $amountMatrix;
    }
} 