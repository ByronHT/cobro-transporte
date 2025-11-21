/**
 * Utilidades para cálculo de Tiempo Estimado de Llegada (ETA)
 */

/**
 * Calcula la distancia en línea recta entre dos puntos GPS (Haversine)
 * @param {number} lat1 - Latitud punto 1
 * @param {number} lon1 - Longitud punto 1
 * @param {number} lat2 - Latitud punto 2
 * @param {number} lon2 - Longitud punto 2
 * @returns {number} Distancia en kilómetros
 */
export function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radio de la Tierra en km
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function toRad(degrees) {
    return degrees * Math.PI / 180;
}

/**
 * Calcula el tiempo estimado de llegada basado en distancia y velocidad
 *
 * @param {number} distanceKm - Distancia en kilómetros
 * @param {number|null} currentSpeed - Velocidad actual del bus en km/h
 * @returns {Object} { minutes, text } - Minutos estimados y texto formateado
 */
export function calculateETA(distanceKm, currentSpeed = null) {
    // Velocidades promedio según tipo de zona
    const CITY_AVG_SPEED = 25; // km/h en ciudad
    const MIN_SPEED = 15; // km/h velocidad mínima asumida
    const MAX_REASONABLE_SPEED = 60; // km/h velocidad máxima razonable

    // Determinar velocidad a usar
    let speed = CITY_AVG_SPEED;

    if (currentSpeed !== null && currentSpeed > 0) {
        // Usar velocidad actual si está disponible y es razonable
        if (currentSpeed < MIN_SPEED) {
            // Si va muy lento, asumir que está en tráfico pesado
            speed = MIN_SPEED;
        } else if (currentSpeed > MAX_REASONABLE_SPEED) {
            // Si va muy rápido, limitar a velocidad máxima razonable
            speed = MAX_REASONABLE_SPEED;
        } else {
            speed = currentSpeed;
        }
    }

    // Calcular tiempo en minutos
    const timeInHours = distanceKm / speed;
    const timeInMinutes = Math.round(timeInHours * 60);

    // Agregar buffer por paradas y semáforos (10% del tiempo)
    const bufferedMinutes = Math.ceil(timeInMinutes * 1.1);

    // Formatear texto
    let text;
    if (bufferedMinutes < 1) {
        text = 'Menos de 1 minuto';
    } else if (bufferedMinutes === 1) {
        text = '1 minuto';
    } else if (bufferedMinutes < 60) {
        text = `${bufferedMinutes} minutos`;
    } else {
        const hours = Math.floor(bufferedMinutes / 60);
        const mins = bufferedMinutes % 60;
        if (mins === 0) {
            text = `${hours} ${hours === 1 ? 'hora' : 'horas'}`;
        } else {
            text = `${hours}h ${mins}min`;
        }
    }

    return {
        minutes: bufferedMinutes,
        text,
        speed: speed,
        distanceKm: distanceKm.toFixed(2)
    };
}

/**
 * Formatea la distancia en un formato legible
 * @param {number} distanceKm - Distancia en kilómetros
 * @returns {string} Texto formateado (ej: "1.2 km" o "350 m")
 */
export function formatDistance(distanceKm) {
    if (distanceKm < 1) {
        const meters = Math.round(distanceKm * 1000);
        return `${meters} m`;
    } else {
        return `${distanceKm.toFixed(1)} km`;
    }
}

/**
 * Obtiene un color basado en el tiempo de llegada
 * @param {number} minutes - Minutos estimados
 * @returns {string} Código de color hex
 */
export function getETAColor(minutes) {
    if (minutes < 5) return '#10b981'; // Verde - muy cerca
    if (minutes < 15) return '#3b82f6'; // Azul - cerca
    if (minutes < 30) return '#f59e0b'; // Amarillo - moderado
    return '#ef4444'; // Rojo - lejos
}

/**
 * Obtiene emoji según tiempo de llegada
 * @param {number} minutes - Minutos estimados
 * @returns {string} Emoji
 */
export function getETAEmoji(minutes) {
    if (minutes < 5) return '🟢';
    if (minutes < 15) return '🔵';
    if (minutes < 30) return '🟡';
    return '🔴';
}
