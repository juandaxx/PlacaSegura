<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cobro extends Model
{
    protected $table = 'cobros';
    public $timestamps = false;
    protected $fillable = ['sede_id', 'tarifa_id', 'evento_ingreso_id', 'placa', 'dia_operativo', 'valor', 'estado'];
    protected function casts(): array { return ['dia_operativo' => 'date', 'valor' => 'decimal:2']; }

    public function sede(): BelongsTo { return $this->belongsTo(Sede::class, 'sede_id'); }
    public function tarifa(): BelongsTo { return $this->belongsTo(Tarifa::class, 'tarifa_id'); }
    public function eventoIngreso(): BelongsTo { return $this->belongsTo(EventoAcceso::class, 'evento_ingreso_id'); }
    public function pagos(): HasMany { return $this->hasMany(Pago::class, 'cobro_id'); }
}
