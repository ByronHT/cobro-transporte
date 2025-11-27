<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'ruta_id',
        'bus_id',
        'driver_id',
        'turno_id',
        'tipo_viaje',
        'inicio',
        'fin',
        'hora_salida_programada',
        'hora_salida_real',
        'hora_llegada_programada',
        'hora_llegada_real',
        'reporte',
        'photo_path',
        'status',
        'finalizado_en_parada',
        'cambio_bus',
        'nuevo_bus_id',
        'recorrido_gps',
        'total_recaudado'
    ];


    protected $casts = [
        'fecha' => 'date',
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'hora_salida_programada' => 'datetime',
        'hora_salida_real' => 'datetime',
        'hora_llegada_programada' => 'datetime',
        'hora_llegada_real' => 'datetime',
        'recorrido_gps' => 'array',
        'total_recaudado' => 'decimal:2',
        'finalizado_en_parada' => 'boolean',
        'cambio_bus' => 'boolean'
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class,'ruta_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class,'driver_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function paymentEvents()
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function locations()
    {
        return $this->hasMany(BusLocation::class);
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function nuevoBus()
    {
        return $this->belongsTo(Bus::class, 'nuevo_bus_id');
    }

    public function waypoints()
    {
        return $this->hasMany(TripWaypoint::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('fin');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('fin');
    }

    public function getTotalFareAttribute()
    {
        return $this->transactions()->where('type', 'fare')->sum('amount');
    }

    public function getTotalPassengersAttribute()
    {
        return $this->transactions()->where('type', 'fare')->count();
    }
}
