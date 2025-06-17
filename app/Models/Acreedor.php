<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acreedor extends Model
{
    use HasFactory;

    protected $table = 'acreedores';

    protected $fillable = ['nombre', 'monto_adeudado', 'porcentaje', 'saldo'];

    public function compradores()
    {
        return $this->hasManyThrough(
            Comprador::class,
            Financiacion::class,
            'id', // Clave local en financiacion_acreedor
            'id', // Clave local en compradores
            'id', // Clave remota en acreedores
            'comprador_id' // Clave remota en financiaciones
        );
    }

    public function financiaciones()
    {
        return $this->belongsToMany(Financiacion::class, 'financiacion_acreedor')
                    ->withPivot('porcentaje');
    }

    /**
     * Incrementa el saldo del acreedor
     * @param float $monto Monto a incrementar (positivo)
     * @return void
     */
    public function incrementarSaldo($monto)
    {
        if ($monto <= 0) {
            return;
        }
        
        $this->saldo += $monto;
        $this->save();
    }

    /**
     * Decrementa el saldo del acreedor (liquidación)
     * @param float $monto Monto a decrementar (positivo)
     * @return bool True si se pudo decrementar, False si el saldo es insuficiente
     */
    public function decrementarSaldo($monto)
    {
        if ($monto <= 0) {
            return true;
        }
        
        // Verificar que haya saldo suficiente
        if ($this->saldo < $monto) {
            return false;
        }
        
        $this->saldo -= $monto;
        $this->save();
        
        return true;
    }
}