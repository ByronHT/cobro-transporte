<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';
    protected $fillable = [
        'nombre',
        'descripcion',
        'tarifa_base',
        'linea_numero',
        'ruta_ida_descripcion',
        'ruta_ida_waypoints',
        'ruta_vuelta_descripcion',
        'ruta_vuelta_waypoints',
        'tarifa_adulto',
        'tarifa_descuento',
        'activa'
    ];

    protected $casts = [
        'ruta_ida_waypoints' => 'array',
        'ruta_vuelta_waypoints' => 'array',
        'tarifa_base' => 'decimal:2',
        'tarifa_adulto' => 'decimal:2',
        'tarifa_descuento' => 'decimal:2',
        'activa' => 'boolean',
    ];

    public function buses()
    {
        return $this->hasMany(Bus::class, 'ruta_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'ruta_id');
    }
}
