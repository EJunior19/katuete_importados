<?php

return [
    // PIN de acceso (lee de .env, por defecto 1234)
    'pin' => env('FINANCE_PIN', '1234'),

    // TTL en minutos
    'ttl' => (int) env('FINANCE_PIN_TTL', 15),
];
