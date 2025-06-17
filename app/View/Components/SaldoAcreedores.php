<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Carbon\Carbon;

class SaldoAcreedores extends Component
{
    public $acreedores;
    public $months;

    /**
     * Create a new component instance.
     *
     * @param  array  $acreedores
     * @return void
     */
    public function __construct($acreedores)
    {
        $this->acreedores = $acreedores;
        
        // Generar los últimos 6 meses
        $currentDate = Carbon::now();
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $date = clone $currentDate;
            $date->subMonths($i);
            $months[] = $date;
        }
        // Revertir para mostrar de más antiguo a más reciente
        $this->months = array_reverse($months);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.saldo-acreedores');
    }
} 