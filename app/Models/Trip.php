<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = ['fecha','ruta_id','bus_id','driver_id','inicio','fin','reporte','photo_path','status'];

    // Removido 'locations' de appends - causar error 500 al serializar
    // Si necesitas locations, cárgalas explícitamente con ->with('locations')

    protected $casts = [
        'fecha' => 'date',
        'inicio' => 'datetime',
        'fin' => 'datetime'
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('fin');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('fin');
    }

    // Calcular tarifa total del viaje
    public function getTotalFareAttribute()
    {
        return $this->transactions()->where('type', 'fare')->sum('amount');
    }

    // Contar pasajeros
    public function getTotalPassengersAttribute()
    {
        return $this->transactions()->where('type', 'fare')->count();
    }
}
