<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';
    public $timestamps = false;
    protected $fillable = ['cobro_id', 'usuario_id', 'medio', 'referencia', 'valor', 'fecha_hora', 'pase_hasta'];
    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'fecha_hora' => 'datetime', 'pase_hasta' => 'datetime'];
    }

    public function cobro(): BelongsTo { return $this->belongsTo(Cobro::class, 'cobro_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'usuario_id'); }
}
