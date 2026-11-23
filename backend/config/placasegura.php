<?php

return [
    'hora_inicio_dia_operativo' => env('PLACA_SEGURA_HORA_INICIO_DIA_OPERATIVO', '06:00'),
    'umbral_confianza' => (float) env('PLACA_SEGURA_UMBRAL_CONFIANZA', 70),
    'umbral_alerta_capacidad' => (float) env('PLACA_SEGURA_UMBRAL_ALERTA_CAPACIDAD', 90),
];
