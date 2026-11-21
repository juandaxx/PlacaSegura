<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarifa extends Model
{
    protected $table = 'tarifas';
    public $timestamps = false;
    protected $fillable = ['sede_id', 'tipo_vehiculo_id', 'franja_id', 'valor'];
    protected function casts(): array { return ['valor' => 'decimal:2']; }

    public function sede(): BelongsTo { return $this->belongsTo(Sede::class, 'sede_id'); }
    public function tipoVehiculo(): BelongsTo { return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id'); }
    public function franja(): BelongsTo { return $this->belongsTo(Franja::class, 'franja_id'); }
    public function cobros(): HasMany { return $this->hasMany(Cobro::class, 'tarifa_id'); }
}
