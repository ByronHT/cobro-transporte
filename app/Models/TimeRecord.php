<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'turno_id',
        'trip_ida_id',
        'trip_vuelta_id',
        'inicio_ida',
        'fin_ida',
        'inicio_vuelta',
        'fin_vuelta_estimado',
        'fin_vuelta_real',
        'estado',
        'tiempo_retraso_minutos',
        'es_ultimo_viaje',
    ];

    protected $casts = [
        'inicio_ida' => 'datetime',
        'fin_ida' => 'datetime',
        'inicio_vuelta' => 'datetime',
        'fin_vuelta_estimado' => 'datetime',
        'fin_vuelta_real' => 'datetime',
        'es_ultimo_viaje' => 'boolean',
    ];

    // Relaciones
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function tripIda()
    {
        return $this->belongsTo(Trip::class, 'trip_ida_id');
    }

    public function tripVuelta()
    {
        return $this->belongsTo(Trip::class, 'trip_vuelta_id');
    }
}
