<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cupo extends Model
{
    protected $table = 'cupos';
    public $timestamps = false;
    protected $fillable = ['sede_id', 'tipo_vehiculo_id', 'total'];
    protected function casts(): array { return ['total' => 'integer']; }

    public function sede(): BelongsTo { return $this->belongsTo(Sede::class, 'sede_id'); }
    public function tipoVehiculo(): BelongsTo { return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id'); }
}
