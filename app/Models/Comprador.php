<?php
// app/Models/Comprador.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprador extends Model
{
    use HasFactory;

    // Especificar el nombre de la tabla
    protected $table = 'compradores';

    protected $fillable = [
        'nombre', 'direccion', 'telefono', 'email', 'dni', 'lote_comprado_id', 'financiacion_id', 'judicializado'
    ];

    // Relación con Lote
    public function lote()
    {
        return $this->hasOne(Lote::class, 'comprador_id', 'id');
    }

    // Relación con Financiacion
    public function financiacion()
    {
        return $this->hasOne(Financiacion::class, 'comprador_id', 'id');
    }
}