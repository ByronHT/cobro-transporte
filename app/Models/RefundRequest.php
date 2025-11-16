<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'trip_id',
        'driver_id',
        'passenger_id',
        'card_id',
        'amount',
        'reason',
        'card_uid',
        'status',
        'verification_token',
        'verified_at',
        'completed_at',
        'expires_at',
        'is_reversed',
        'reversal_reason',
        'reversed_at',
        'reversed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'reversed_at' => 'datetime',
        'is_reversed' => 'boolean',
    ];

    // Relaciones
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function verification()
    {
        return $this->hasOne(RefundVerification::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                     ->where('status', 'pending');
    }

    // Métodos auxiliares
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isVerified()
    {
        return $this->status === 'verified';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    // Generar token único de verificación
    public static function generateVerificationToken()
    {
        return Str::random(64);
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        // Generar token automáticamente al crear
        static::creating(function ($refundRequest) {
            if (!$refundRequest->verification_token) {
                $refundRequest->verification_token = self::generateVerificationToken();
            }

            // Establecer expiración en 24 horas si no está definida
            if (!$refundRequest->expires_at) {
                $refundRequest->expires_at = now()->addHours(24);
            }
        });
    }
}
