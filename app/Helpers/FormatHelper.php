<?php

namespace App\Helpers;

class FormatHelper 
{
    public static function numberWithSuperscript($number, $decimals = 2) 
    {
        // Formatea el número con la cantidad especificada de decimales
        $formatted = number_format($number, $decimals, '.', ',');
        
        // Separa la parte entera y decimal
        $parts = explode('.', $formatted);
        
        // Si hay parte decimal, la formatea como superíndice
        if (isset($parts[1])) {
            return $parts[0] . '<sup>' . $parts[1] . '</sup>';
        }
        
        return $formatted;
    }
} 