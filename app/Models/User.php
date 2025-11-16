<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * ARQUITECTURA DE BALANCE:
     * - Passengers: balance siempre 0.00 (fuente de verdad: Card.balance)
     * - Drivers: balance = ganancias acumuladas
     * - Admins: balance siempre 0.00 (no usado)
     * Ver docs/ARQUITECTURA_BALANCE.md para más detalles
     */

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'name',
        'email',
        'nit',
        'password',
        'role',
        'active',
        'balance'  // Solo usado para drivers (ganancias)
    ];

    // Campos ocultos al serializar
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Tipos de campos
    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
    ];

    // Relación con tarjetas
    public function cards()
    {
        return $this->hasMany(Card::class, 'passenger_id');
    }

    // Relación con viajes como chofer
    public function trips()
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    // Relación con solicitudes de devolución como chofer
    public function refundRequestsAsDriver()
    {
        return $this->hasMany(RefundRequest::class, 'driver_id');
    }

    // Relación con solicitudes de devolución como pasajero
    public function refundRequestsAsPassenger()
    {
        return $this->hasMany(RefundRequest::class, 'passenger_id');
    }

    // Relación con verificaciones de devolución
    public function refundVerifications()
    {
        return $this->hasMany(RefundVerification::class);
    }
}
