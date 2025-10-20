<?php
// Helpers globales para toda la app

if (! function_exists('money_py')) {
    function money_py(int|float|string|null $value, bool $withSymbol = true): string
    {
        if ($value === null || $value === '') return $withSymbol ? 'Gs. 0' : '0';
        $n = (int) preg_replace('/\D+/', '', (string) $value);
        $formatted = number_format($n, 0, ',', '.');
        return $withSymbol ? 'Gs. ' . $formatted : $formatted;
    }
}
