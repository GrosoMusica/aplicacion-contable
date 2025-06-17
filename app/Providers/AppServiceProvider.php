<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade; // Añade esta línea para importar la clase Blade

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ... existing code ...
        
        // Registrar componente de vista panorámica semestral
        Blade::component('panoramica-semestral-acreedores', 'components.panoramica-semestral-acreedores');

        Blade::directive('supnum', function ($expression) {
            return "<?php echo \App\Helpers\FormatHelper::numberWithSuperscript($expression); ?>";
        });
    }
}
