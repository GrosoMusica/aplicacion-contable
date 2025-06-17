<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;

    protected $fillable = [
        'estado', 'comprador_id', 'loteo', 'manzana', 'lote', 'mts_cuadrados'
    ];

    // Relación inversa con Comprador
    public function comprador()
    {
        return $this->belongsTo(Comprador::class, 'comprador_id');
    }
}