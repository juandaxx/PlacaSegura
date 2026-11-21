<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carril extends Model
{
    protected $table = 'carriles';
    public $timestamps = false;
    protected $fillable = ['sede_id', 'nombre', 'tipo'];

    public function sede(): BelongsTo { return $this->belongsTo(Sede::class, 'sede_id'); }
    public function eventosAcceso(): HasMany { return $this->hasMany(EventoAcceso::class, 'carril_id'); }
}
