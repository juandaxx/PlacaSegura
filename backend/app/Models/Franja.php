<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Franja extends Model
{
    protected $table = 'franjas';
    public $timestamps = false;
    protected $fillable = ['nombre', 'hora_inicio', 'hora_fin'];

    public function tarifas(): HasMany { return $this->hasMany(Tarifa::class, 'franja_id'); }
}
