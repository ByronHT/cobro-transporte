<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'card_uid',
        'card_id',
        'passenger_id',
        'event_type',
        'amount',
        'required_amount',
        'message'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'required_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relaciones
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    // Scopes
    public function scopeForTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    public function scopeSuccess($query)
    {
        return $query->where('event_type', 'success');
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('event_type', ['insufficient_balance', 'invalid_card', 'inactive_card', 'error']);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
