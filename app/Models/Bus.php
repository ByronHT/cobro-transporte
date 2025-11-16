<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    use HasFactory;

    protected $fillable = ['plate','code','brand','model','ruta_id'];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function commands()
    {
        return $this->hasMany(BusCommand::class);
    }

    public function locations()
    {
        return $this->hasMany(BusLocation::class);
    }

    // Obtener el viaje activo del bus (sin fecha fin)
    public function activeTrip()
    {
        return $this->hasOne(Trip::class)->whereNull('fin')->latest();
    }

    // Obtener el chofer actual a través del viaje activo
    public function currentDriver()
    {
        $activeTrip = $this->activeTrip()->first();
        return $activeTrip ? $activeTrip->driver : null;
    }
}
