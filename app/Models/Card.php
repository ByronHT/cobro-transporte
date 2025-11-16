<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    /**
     * ARQUITECTURA DE BALANCE:
     * Card.balance es la FUENTE DE VERDAD para el saldo de pasajeros.
     * Ver docs/ARQUITECTURA_BALANCE.md para más detalles
     */

    protected $fillable = ['uid','balance','passenger_id','active'];

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    // Alias para facilitar el acceso
    public function user()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentEvents()
    {
        return $this->hasMany(PaymentEvent::class);
    }
}
