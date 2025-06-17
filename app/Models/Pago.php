<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cuota_id',
        'acreedor_id',
        'monto_pagado',
        'comprobante',
        'sin_comprobante',
        'pago_divisa',
        'monto_usd',
        'tipo_cambio',
        'fecha_de_pago',
        'saldo_pendiente'
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'monto_usd' => 'decimal:2',
        'tipo_cambio' => 'decimal:2',
        'sin_comprobante' => 'boolean',
        'pago_divisa' => 'boolean',
        'saldo_pendiente' => 'decimal:2',
    ];

    // Relación con Cuota
    public function cuota()
    {
        return $this->belongsTo(Cuota::class);
    }

    // Relación con Acreedor (si tienes un modelo Acreedor)
    public function acreedor()
    {
        return $this->belongsTo(Acreedor::class);
    }
    
    // Método auxiliar para obtener la fecha de pago (usa created_at)
    public function getFechaDePagoAttribute()
    {
        return $this->created_at;
    }
} 