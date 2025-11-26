// Configuración de la API
export const API_BASE_URL = 'https://cobro-transporte-production-dac4.up.railway.app';

// Configuración de polling (en milisegundos)
export const POLLING_INTERVAL = 5000; // 5 segundos para actualizaciones en tiempo real
export const GPS_UPDATE_INTERVAL = 15000; // 15 segundos para GPS (manejado por useGPSTracking)

// Google Maps API Key
export const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || 'AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g';
