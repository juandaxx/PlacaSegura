<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoVehiculo extends Model
{
    protected $table = 'tipos_vehiculo';
    public $timestamps = false;
    protected $fillable = ['nombre', 'patron_placa'];

    public function tarifas(): HasMany { return $this->hasMany(Tarifa::class, 'tipo_vehiculo_id'); }
    public function cupos(): HasMany { return $this->hasMany(Cupo::class, 'tipo_vehiculo_id'); }
    public function eventosAcceso(): HasMany { return $this->hasMany(EventoAcceso::class, 'tipo_vehiculo_id'); }
}
