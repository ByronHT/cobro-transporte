import { useEffect, useRef, useState } from 'react';
import axios from 'axios';

/**
 * Hook personalizado para tracking GPS optimizado del chofer
 *
 * Características:
 * - Throttling inteligente (30s quieto, 15s en movimiento)
 * - Detección de movimiento significativo (>50m)
 * - Solo envía cuando hay viaje activo
 * - Manejo automático de errores y reconexión
 * - Capacitor GPS (móvil) con fallback a navigator.geolocation
 *
 * @param {Object} options - Opciones de configuración
 * @param {number} options.busId - ID del bus
 * @param {boolean} options.isTripActive - Si el viaje está activo
 * @param {string} options.token - Token de autenticación
 * @param {string} options.apiBaseUrl - URL base de la API
 * @returns {Object} Estado del tracking GPS
 */
export function useGPSTracking({ busId, isTripActive, token, apiBaseUrl }) {
    const [isTracking, setIsTracking] = useState(false);
    const [lastLocation, setLastLocation] = useState(null);
    const [error, setError] = useState(null);
    const [locationCount, setLocationCount] = useState(0);

    const watchIdRef = useRef(null);
    const lastSentLocationRef = useRef(null);
    const lastSentTimeRef = useRef(0);
    const isMovingRef = useRef(false);

    // Configuración de intervalos
    const INTERVAL_STATIONARY = 30000; // 30 segundos cuando está quieto
    const INTERVAL_MOVING = 15000;      // 15 segundos cuando se mueve
    const MIN_DISTANCE_METERS = 50;     // Mínimo 50 metros para considerar movimiento

    /**
     * Calcula distancia entre dos puntos GPS usando Haversine
     */
    const calculateDistance = (lat1, lon1, lat2, lon2) => {
        const R = 6371e3; // Radio de la Tierra en metros
        const φ1 = lat1 * Math.PI / 180;
        const φ2 = lat2 * Math.PI / 180;
        const Δφ = (lat2 - lat1) * Math.PI / 180;
        const Δλ = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c; // Distancia en metros
    };

    /**
     * Determina si debe enviar la ubicación según throttling inteligente
     */
    const shouldSendLocation = (currentLocation) => {
        const now = Date.now();
        const timeSinceLastSent = now - lastSentTimeRef.current;

        // Primera ubicación siempre se envía
        if (!lastSentLocationRef.current) {
            return true;
        }

        // Calcular distancia desde última ubicación enviada
        const distance = calculateDistance(
            lastSentLocationRef.current.lat,
            lastSentLocationRef.current.lng,
            currentLocation.lat,
            currentLocation.lng
        );

        // Si se movió significativamente (>50m), considerar "en movimiento"
        if (distance > MIN_DISTANCE_METERS) {
            isMovingRef.current = true;
        } else {
            isMovingRef.current = false;
        }

        // Aplicar throttling según estado de movimiento
        const requiredInterval = isMovingRef.current ? INTERVAL_MOVING : INTERVAL_STATIONARY;

        // Enviar si pasó suficiente tiempo O si se movió mucho (>200m)
        return timeSinceLastSent >= requiredInterval || distance > 200;
    };

    /**
     * Envía ubicación al servidor
     */
    const sendLocationToServer = async (location) => {
        if (!busId || !isTripActive) {
            console.log('⚠️ [GPS] No se envía ubicación: busId o viaje inactivo');
            return;
        }

        try {
            const payload = {
                bus_id: busId,
                latitude: location.lat,
                longitude: location.lng,
                speed: location.speed || null,
                heading: location.heading || null,
                accuracy: location.accuracy || null
            };

            await axios.post(
                `${apiBaseUrl}/api/driver/update-location`,
                payload,
                {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    timeout: 10000 // 10 segundos timeout
                }
            );

            // Actualizar referencias después de envío exitoso
            lastSentLocationRef.current = location;
            lastSentTimeRef.current = Date.now();
            setLocationCount(prev => prev + 1);
            setError(null);

            console.log(`📍 [GPS] Ubicación enviada (#${locationCount + 1}) - En movimiento: ${isMovingRef.current}`);

        } catch (err) {
            console.error('❌ [GPS] Error enviando ubicación:', err);
            setError(err.message);

            // No hacer nada más - el watchPosition seguirá intentando
        }
    };

    /**
     * Maneja nueva posición GPS
     */
    const handlePosition = (position) => {
        const location = {
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            speed: position.coords.speed || null,
            heading: position.coords.heading || null,
            accuracy: position.coords.accuracy || null,
            timestamp: position.timestamp
        };

        setLastLocation(location);

        // Verificar si debe enviar según throttling inteligente
        if (shouldSendLocation(location)) {
            sendLocationToServer(location);
        } else {
            console.log(`⏭️ [GPS] Ubicación omitida (throttling) - Distancia < ${MIN_DISTANCE_METERS}m`);
        }
    };

    /**
     * Maneja error de GPS
     */
    const handleError = (error) => {
        console.error('❌ [GPS] Error obteniendo ubicación:', error);
        setError(`Error GPS: ${error.message}`);
    };

    /**
     * Inicia el tracking GPS
     */
    useEffect(() => {
        // Solo iniciar tracking si hay viaje activo y busId
        if (!isTripActive || !busId) {
            console.log('⚠️ [GPS] Tracking no iniciado: viaje inactivo o sin busId');
            setIsTracking(false);
            return;
        }

        console.log('🚀 [GPS] Iniciando tracking para bus:', busId);
        setIsTracking(true);
        setError(null);

        // Opciones de geolocalización optimizadas
        const options = {
            enableHighAccuracy: true,    // Usar GPS de alta precisión
            timeout: 30000,               // 30 segundos timeout
            maximumAge: 0                 // No usar caché
        };

        // Verificar si Capacitor Geolocation está disponible (móvil)
        const hasCapacitor = window.Capacitor && window.Capacitor.Plugins?.Geolocation;

        if (hasCapacitor) {
            // Usar Capacitor Geolocation (mejor para apps móviles)
            console.log('📱 [GPS] Usando Capacitor Geolocation');

            const startCapacitorWatch = async () => {
                try {
                    const { Geolocation } = window.Capacitor.Plugins;

                    watchIdRef.current = await Geolocation.watchPosition(options, (position, err) => {
                        if (err) {
                            handleError(err);
                        } else if (position) {
                            handlePosition(position);
                        }
                    });

                    console.log('✅ [GPS] Watch iniciado con ID:', watchIdRef.current);
                } catch (err) {
                    console.error('❌ [GPS] Error iniciando Capacitor watch:', err);
                    setError(err.message);
                }
            };

            startCapacitorWatch();

        } else if (navigator.geolocation) {
            // Fallback a navigator.geolocation (navegadores web)
            console.log('🌐 [GPS] Usando navigator.geolocation (fallback)');

            watchIdRef.current = navigator.geolocation.watchPosition(
                handlePosition,
                handleError,
                options
            );

            console.log('✅ [GPS] Watch iniciado con ID:', watchIdRef.current);

        } else {
            console.error('❌ [GPS] Geolocalización no soportada');
            setError('Geolocalización no soportada en este dispositivo');
            setIsTracking(false);
            return;
        }

        // Cleanup al desmontar o cuando cambia isTripActive
        return () => {
            if (watchIdRef.current) {
                console.log('🛑 [GPS] Deteniendo tracking...');

                if (hasCapacitor) {
                    const { Geolocation } = window.Capacitor.Plugins;
                    Geolocation.clearWatch({ id: watchIdRef.current });
                } else {
                    navigator.geolocation.clearWatch(watchIdRef.current);
                }

                watchIdRef.current = null;
                setIsTracking(false);
                console.log('✅ [GPS] Tracking detenido');
            }
        };

    }, [isTripActive, busId, token, apiBaseUrl]);

    return {
        isTracking,
        lastLocation,
        error,
        locationCount,
        isMoving: isMovingRef.current
    };
}
