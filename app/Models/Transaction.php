<?php
namespace App\Models;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['type','amount','card_id','driver_id','passenger_id','ruta_id','bus_id','trip_id','description'];

    public function card(){ return $this->belongsTo(Card::class); }
    public function driver(){ return $this->belongsTo(User::class, 'driver_id'); }
    public function passenger(){ return $this->belongsTo(User::class, 'passenger_id'); }
    public function ruta(){ return $this->belongsTo(Ruta::class); }
    public function bus(){ return $this->belongsTo(Bus::class); }
    public function trip(){ return $this->belongsTo(Trip::class); }
}
