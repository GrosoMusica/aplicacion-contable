<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Financiacion extends Model
{
    use HasFactory;

    protected $table = 'financiaciones';

    protected $fillable = [
        'comprador_id', 'monto_a_financiar', 'cantidad_de_cuotas', 'fecha_de_vencimiento', 'monto_de_las_cuotas'
    ];

    protected $dates = ['fecha_de_vencimiento'];

    public function comprador()
    {
        return $this->belongsTo(Comprador::class, 'comprador_id');
    }

    public function acreedores()
    {
        return $this->belongsToMany(Acreedor::class, 'financiacion_acreedor')
                    ->withPivot('porcentaje');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'financiacion_id');
    }
}