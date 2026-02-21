<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/** 
 * Tareas Programadas para el Inventario
 */
// Se ejecuta cada día a medianoche
Schedule::command('app:product-process-warehouse')->daily();

// Se ejecuta el primer día de cada mes
Schedule::command('app:command-product-stock-inicial')->monthly();
