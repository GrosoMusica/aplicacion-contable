<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cuota extends Model
{
    use HasFactory;

    protected $table = 'cuotas';

    protected $fillable = [
        'financiacion_id',
        'monto',
        'fecha_de_vencimiento',
        'estado',
        'numero_de_cuota'
    ];

    // Asegúrate de que 'fecha_de_vencimiento' esté en el array $dates
    protected $dates = ['fecha_de_vencimiento'];

    /**
     * Relación con Financiacion
     */
    public function financiacion()
    {
        return $this->belongsTo(Financiacion::class, 'financiacion_id');
    }

    /**
     * Relación con Pagos
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'cuota_id'); // Asegúrate de que 'cuota_id' sea el nombre correcto
    }

    public function getEstadoColorAttribute()
    {
        if ($this->estado == 'pagada') {
            return 'text-success'; // Verde
        } elseif ($this->fecha_de_vencimiento < Carbon::now()) {
            return 'text-danger'; // Rojo
        } else {
            return 'text-warning'; // Amarillo
        }
    }
} 