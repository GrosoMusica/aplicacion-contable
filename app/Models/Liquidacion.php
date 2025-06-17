<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    use HasFactory;
    
    protected $table = 'liquidaciones';
    
    protected $fillable = [
        'acreedor_id',
        'monto',
        'fecha',
        'comprobante',
        'usuario_id'
    ];
    
    protected $dates = [
        'fecha'
    ];
    
    public function acreedor()
    {
        return $this->belongsTo(Acreedor::class);
    }
    
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
} 