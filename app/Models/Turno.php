<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'bus_inicial_id',
        'fecha',
        'hora_inicio',
        'hora_fin_programada',
        'hora_fin_real',
        'status',
        'total_viajes_ida',
        'total_viajes_vuelta',
        'total_recaudado'
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_fin_real' => 'datetime',
        'total_recaudado' => 'decimal:2'
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function busInicial()
    {
        return $this->belongsTo(Bus::class, 'bus_inicial_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function isActive()
    {
        return $this->status === 'activo';
    }

    public function finalizar()
    {
        $this->status = 'finalizado';
        $this->hora_fin_real = now();

        $this->total_viajes_ida = $this->trips()->where('tipo_viaje', 'ida')->count();
        $this->total_viajes_vuelta = $this->trips()->where('tipo_viaje', 'vuelta')->count();
        $this->total_recaudado = $this->trips()->sum('total_recaudado');

        $this->save();

        $this->driver->total_earnings += $this->total_recaudado;
        $this->driver->save();

        return $this;
    }
}
