<?php

namespace App\Helpers;

class NumberHelper
{
    public static function supNum($num) 
    {
        $sup = ['⁰', '¹', '²', '³', '⁴', '⁵', '⁶', '⁷', '⁸', '⁹'];
        $numStr = (string)$num;
        $result = '';
        for ($i = 0; $i < strlen($numStr); $i++) {
            $result .= $sup[$numStr[$i]];
        }
        return $result;
    }
} 