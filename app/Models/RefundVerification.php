<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_request_id',
        'action',
        'user_id',
        'ip_address',
        'user_agent',
        'comments',
    ];

    // Relaciones
    public function refundRequest()
    {
        return $this->belongsTo(RefundRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('action', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('action', 'rejected');
    }

    // Métodos auxiliares
    public function isApproved()
    {
        return $this->action === 'approved';
    }

    public function isRejected()
    {
        return $this->action === 'rejected';
    }
}
