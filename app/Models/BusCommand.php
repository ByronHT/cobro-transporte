<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id',
        'command',
        'status',
        'requested_by',
        'error_message',
        'executed_at'
    ];

    protected $casts = [
        'executed_at' => 'datetime'
    ];

    // Relaciones
    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForBus($query, $busId)
    {
        return $query->where('bus_id', $busId);
    }
}
