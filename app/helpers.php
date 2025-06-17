<?php

if (!function_exists('supNum')) {
    function supNum($number, $decimals = 2) {
        $formatted = number_format($number, $decimals, '.', ',');
        $parts = explode('.', $formatted);
        if (isset($parts[1])) {
            return $parts[0] . '<sup class="decimal-sup">' . $parts[1] . '</sup>';
        }
        return $formatted;
    }
} 