/**
 * Utilidad para detectar el ambiente de ejecución
 * Determina si la app está corriendo en navegador web o app nativa (Capacitor)
 */

import { Capacitor } from '@capacitor/core';

/**
 * Detecta si la aplicación está corriendo en una plataforma nativa
 * @returns {boolean} true si es app nativa (Android/iOS), false si es web
 */
export const isNativePlatform = () => {
    return Capacitor.isNativePlatform();
};

/**
 * Detecta si la aplicación está corriendo en un navegador web
 * @returns {boolean} true si es navegador web, false si es app nativa
 */
export const isWebPlatform = () => {
    return !Capacitor.isNativePlatform();
};

/**
 * Obtiene el nombre de la plataforma actual
 * @returns {string} 'web', 'android', 'ios'
 */
export const getPlatformName = () => {
    return Capacitor.getPlatform(); // 'web', 'android', 'ios'
};

/**
 * Detecta si está corriendo en Android
 * @returns {boolean}
 */
export const isAndroid = () => {
    return Capacitor.getPlatform() === 'android';
};

/**
 * Detecta si está corriendo en iOS
 * @returns {boolean}
 */
export const isIOS = () => {
    return Capacitor.getPlatform() === 'ios';
};

/**
 * Configuración del mapa según la plataforma
 * @returns {object} Configuración específica de la plataforma
 */
export const getMapConfig = () => {
    const platform = getPlatformName();

    return {
        platform,
        isNative: isNativePlatform(),
        isWeb: isWebPlatform(),
        // Google Maps usará diferentes implementaciones según la plataforma
        useGoogleMapsSDK: isNativePlatform(), // SDK nativo para Android/iOS
        useGoogleMapsJS: isWebPlatform(),      // JavaScript API para web
    };
};

export default {
    isNativePlatform,
    isWebPlatform,
    getPlatformName,
    isAndroid,
    isIOS,
    getMapConfig
};
