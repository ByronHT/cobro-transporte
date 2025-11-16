<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';
    protected $fillable = ['nombre', 'descripcion', 'tarifa_base'];

    public function buses()
    {
        return $this->hasMany(Bus::class, 'ruta_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'ruta_id');
    }
}
