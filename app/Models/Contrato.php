<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $table = 'contratos';
    
    protected $fillable = [
        'id_comprador',
        'ruta_contrato',
        'cuenta_rentas'
    ];

    public function comprador()
    {
        return $this->belongsTo(Comprador::class, 'id_comprador');
    }
} 