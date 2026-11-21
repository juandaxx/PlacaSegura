<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventoAcceso extends Model
{
    protected $table = 'eventos_acceso';
    public $timestamps = false;
    protected $fillable = ['carril_id', 'tipo_vehiculo_id', 'usuario_id', 'placa', 'tipo', 'confianza', 'lectura_manual', 'fecha_hora'];
    protected function casts(): array
    {
        return ['confianza' => 'decimal:2', 'lectura_manual' => 'boolean', 'fecha_hora' => 'datetime'];
    }

    public function carril(): BelongsTo { return $this->belongsTo(Carril::class, 'carril_id'); }
    public function tipoVehiculo(): BelongsTo { return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'usuario_id'); }
    public function cobro(): HasOne { return $this->hasOne(Cobro::class, 'evento_ingreso_id'); }
}
