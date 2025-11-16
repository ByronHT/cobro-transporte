<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_id',
        'driver_id',
        'trip_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'accuracy',
        'is_active',
        'recorded_at'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'accuracy' => 'decimal:2',
        'is_active' => 'boolean',
        'recorded_at' => 'datetime'
    ];

    // Relaciones
    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecent($query, $minutes = 5)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeForBus($query, $busId)
    {
        return $query->where('bus_id', $busId);
    }

    /**
     * Obtener la última ubicación de un bus
     */
    public static function getLatestForBus($busId)
    {
        return self::where('bus_id', $busId)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Calcular distancia en kilómetros entre dos puntos GPS (Fórmula de Haversine)
     * @param float $lat1 Latitud punto 1
     * @param float $lon1 Longitud punto 1
     * @param float $lat2 Latitud punto 2
     * @param float $lon2 Longitud punto 2
     * @return float Distancia en kilómetros
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la Tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Buscar buses dentro de un radio específico
     * @param float $latitude Latitud del punto central
     * @param float $longitude Longitud del punto central
     * @param float $radiusKm Radio de búsqueda en kilómetros (default: 2km)
     * @param int $minutesAgo Considerar ubicaciones de los últimos X minutos (default: 5)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findNearby($latitude, $longitude, $radiusKm = 2, $minutesAgo = 5)
    {
        // Aproximación de grados por kilómetro (más eficiente para filtro inicial)
        $latDegrees = $radiusKm / 111; // 1 grado ≈ 111 km
        $lonDegrees = $radiusKm / (111 * cos(deg2rad($latitude)));

        // Obtener ubicaciones recientes en un cuadrado aproximado (filtro rápido)
        $locations = self::where('is_active', true)
            ->where('recorded_at', '>=', now()->subMinutes($minutesAgo))
            ->whereBetween('latitude', [$latitude - $latDegrees, $latitude + $latDegrees])
            ->whereBetween('longitude', [$longitude - $lonDegrees, $longitude + $lonDegrees])
            ->with(['bus.ruta', 'driver', 'trip'])
            ->get();

        // Filtrar con cálculo preciso de distancia (Haversine)
        return $locations->filter(function ($location) use ($latitude, $longitude, $radiusKm) {
            $distance = self::calculateDistance(
                $latitude,
                $longitude,
                $location->latitude,
                $location->longitude
            );

            // Agregar distancia calculada al objeto
            $location->distance_km = round($distance, 2);

            return $distance <= $radiusKm;
        })->sortBy('distance_km')->values();
    }
}
