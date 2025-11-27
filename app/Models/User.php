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

    protected $fillable = [
        'name',
        'email',
        'nit',
        'password',
        'role',
        'active',
        'balance',  // Solo usado para drivers (ganancias)
        'login_code',
        'ci',
        'birth_date',
        'user_type',
        'school_name',
        'university_name',
        'university_year',
        'university_end_year',
        'total_earnings'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
        'birth_date' => 'date',
        'balance' => 'decimal:2',
        'total_earnings' => 'decimal:2',
    ];

    public function cards()
    {
        return $this->hasMany(Card::class, 'passenger_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class, 'driver_id');
    }

    public function refundRequestsAsDriver()
    {
        return $this->hasMany(RefundRequest::class, 'driver_id');
    }

    public function refundRequestsAsPassenger()
    {
        return $this->hasMany(RefundRequest::class, 'passenger_id');
    }

    public function refundVerifications()
    {
        return $this->hasMany(RefundVerification::class);
    }

    public function calculateFare($tarifaBase, $tarifaAdulto, $tarifaDescuento)
    {
        switch ($this->user_type) {
            case 'adult':
                return $tarifaAdulto;
            case 'senior':
            case 'minor':
            case 'student_school':
            case 'student_university':
                return $tarifaDescuento;
            default:
                return $tarifaBase;
        }
    }

    public function hasDiscount()
    {
        return in_array($this->user_type, ['senior', 'minor', 'student_school', 'student_university']);
    }
}
