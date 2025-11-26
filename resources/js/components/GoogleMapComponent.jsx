import React, { useEffect, useRef, useState } from 'react';
import { Loader } from '@googlemaps/js-api-loader';
import { GoogleMap } from '@capacitor/google-maps';
import { isWebPlatform } from '../utils/platformDetector';
import { GOOGLE_MAPS_API_KEY } from '../config';

/**
 * Componente universal de Google Maps
 * Detecta automáticamente si está en Web o App nativa y usa la implementación correcta
 *
 * Props:
 * - center: { lat, lng } - Centro inicial del mapa
 * - zoom: number - Nivel de zoom (default: 13)
 * - markers: array - Array de marcadores [{ lat, lng, title, icon }]
 * - onMarkerClick: function - Callback cuando se hace click en un marcador
 * - userLocation: { lat, lng } - Ubicación del usuario (opcional)
 * - style: object - Estilos del contenedor del mapa
 */
const GoogleMapComponent = ({
    center = { lat: -17.7833, lng: -63.1823 }, // Santa Cruz, Bolivia
    zoom = 13,
    markers = [],
    onMarkerClick,
    userLocation,
    style = { width: '100%', height: '500px' }
}) => {
    const mapRef = useRef(null);
    const [map, setMap] = useState(null);
    const [googleMapsLoaded, setGoogleMapsLoaded] = useState(false);
    const [mapMarkers, setMapMarkers] = useState([]);
    const userMarkerRef = useRef(null);
    const platform = isWebPlatform() ? 'web' : 'native';

    // Cargar Google Maps según la plataforma
    useEffect(() => {
        if (platform === 'web') {
            loadGoogleMapsWeb();
        } else {
            loadGoogleMapsNative();
        }

        return () => {
            // Cleanup
            if (map && platform === 'native') {
                GoogleMap.destroy({ id: 'interflow-map' });
            }
        };
    }, []);

    // Cargar Google Maps JavaScript API (para navegador web)
    const loadGoogleMapsWeb = async () => {
        try {
            const loader = new Loader({
                apiKey: GOOGLE_MAPS_API_KEY,
                version: 'weekly',
                libraries: ['places', 'geometry']
            });

            await loader.load();
            setGoogleMapsLoaded(true);

            // Crear mapa
            const mapInstance = new google.maps.Map(mapRef.current, {
                center,
                zoom,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
            });

            setMap(mapInstance);
        } catch (error) {
            console.error('Error cargando Google Maps (Web):', error);
        }
    };

    // Cargar Google Maps SDK (para app nativa Android/iOS)
    const loadGoogleMapsNative = async () => {
        try {
            const mapInstance = await GoogleMap.create({
                id: 'interflow-map',
                element: mapRef.current,
                apiKey: GOOGLE_MAPS_API_KEY,
                config: {
                    center,
                    zoom,
                },
            });

            setMap(mapInstance);
            setGoogleMapsLoaded(true);
        } catch (error) {
            console.error('Error cargando Google Maps (Nativo):', error);
        }
    };

    // Actualizar marcadores cuando cambien
    useEffect(() => {
        if (!map || !googleMapsLoaded) return;

        if (platform === 'web') {
            updateMarkersWeb();
        } else {
            updateMarkersNative();
        }
    }, [markers, map, googleMapsLoaded]);

    // Actualizar ubicación del usuario
    useEffect(() => {
        if (!map || !googleMapsLoaded || !userLocation) return;

        if (platform === 'web') {
            updateUserLocationWeb();
        } else {
            updateUserLocationNative();
        }
    }, [userLocation, map, googleMapsLoaded]);

    // Actualizar marcadores en Web
    const updateMarkersWeb = () => {
        // Limpiar marcadores anteriores
        mapMarkers.forEach(marker => marker.setMap(null));

        // Crear nuevos marcadores
        const newMarkers = markers.map(markerData => {
            const marker = new google.maps.Marker({
                position: { lat: markerData.lat, lng: markerData.lng },
                map: map,
                title: markerData.title || '',
                icon: markerData.icon || {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="#0891b2" stroke="white" stroke-width="2"/>
                            <path d="M9 11l3 3 5-5" stroke="white" stroke-width="2" fill="none"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32)
                }
            });

            // Evento de click
            if (onMarkerClick) {
                marker.addListener('click', () => {
                    onMarkerClick(markerData);
                });
            }

            return marker;
        });

        setMapMarkers(newMarkers);

        // Ajustar bounds del mapa para mostrar todos los marcadores
        if (newMarkers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            markers.forEach(marker => {
                bounds.extend({ lat: marker.lat, lng: marker.lng });
            });
            if (userLocation) {
                bounds.extend(userLocation);
            }
            map.fitBounds(bounds);
        }
    };

    // Actualizar marcadores en Native
    const updateMarkersNative = async () => {
        try {
            // Limpiar marcadores anteriores
            await map.removeAllMapMarkers();

            // Agregar nuevos marcadores
            const markerIds = await map.addMarkers(
                markers.map(markerData => ({
                    coordinate: {
                        lat: markerData.lat,
                        lng: markerData.lng
                    },
                    title: markerData.title || '',
                    snippet: markerData.description || ''
                }))
            );

            // Evento de click
            if (onMarkerClick) {
                await map.setOnMarkerClickListener((marker) => {
                    const markerData = markers[marker.markerId];
                    if (markerData) {
                        onMarkerClick(markerData);
                    }
                });
            }
        } catch (error) {
            console.error('Error actualizando marcadores (Nativo):', error);
        }
    };

    // Actualizar ubicación del usuario en Web
    const updateUserLocationWeb = () => {
        // Eliminar marcador anterior del usuario
        if (userMarkerRef.current) {
            userMarkerRef.current.setMap(null);
        }

        // Crear marcador del usuario
        const userMarker = new google.maps.Marker({
            position: userLocation,
            map: map,
            title: 'Tu ubicación',
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8" fill="#3b82f6" stroke="white" stroke-width="3"/>
                        <circle cx="12" cy="12" r="4" fill="white"/>
                    </svg>
                `),
                scaledSize: new google.maps.Size(24, 24)
            },
            zIndex: 1000 // Asegurar que esté encima de otros marcadores
        });

        userMarkerRef.current = userMarker;
    };

    // Actualizar ubicación del usuario en Native
    const updateUserLocationNative = async () => {
        try {
            // En nativo, agregamos un marcador especial para el usuario
            await map.addMarker({
                coordinate: userLocation,
                title: 'Tu ubicación',
                iconUrl: 'assets/user-location-marker.png' // Puedes personalizar
            });
        } catch (error) {
            console.error('Error actualizando ubicación usuario (Nativo):', error);
        }
    };

    return (
        <div
            ref={mapRef}
            style={style}
            id="interflow-map"
        >
            {!googleMapsLoaded && (
                <div style={{
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    height: '100%',
                    background: '#f3f4f6',
                    color: '#6b7280'
                }}>
                    <div style={{ textAlign: 'center' }}>
                        <div style={{
                            width: '40px',
                            height: '40px',
                            border: '4px solid #e5e7eb',
                            borderTop: '4px solid #0891b2',
                            borderRadius: '50%',
                            animation: 'spin 1s linear infinite',
                            margin: '0 auto 10px'
                        }} />
                        <p>Cargando mapa...</p>
                        <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                    </div>
                </div>
            )}
        </div>
    );
};

export default GoogleMapComponent;
