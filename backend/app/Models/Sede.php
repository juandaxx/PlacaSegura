<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    protected $table = 'sedes';
    public $timestamps = false;
    protected $fillable = ['nombre', 'direccion'];

    public function carriles(): HasMany { return $this->hasMany(Carril::class, 'sede_id'); }
    public function tarifas(): HasMany { return $this->hasMany(Tarifa::class, 'sede_id'); }
    public function cupos(): HasMany { return $this->hasMany(Cupo::class, 'sede_id'); }
    public function cobros(): HasMany { return $this->hasMany(Cobro::class, 'sede_id'); }
}
