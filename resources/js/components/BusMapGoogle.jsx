import React, { useEffect, useRef, useState } from 'react';
import { Loader } from '@googlemaps/js-api-loader';
import { GoogleMap } from '@capacitor/google-maps';
import { isWebPlatform } from '../utils/platformDetector';
import { GOOGLE_MAPS_API_KEY } from '../config';

/**
 * Componente de Mapa de Buses con Google Maps
 * Detecta automáticamente si está en Web o App nativa
 *
 * @param {Object} props - Props del componente
 * @param {Array} props.buses - Array de buses a mostrar [{id, lat, lng, plate, route, ...}]
 * @param {Object} props.userLocation - Ubicación del usuario {lat, lng}
 * @param {Object} props.center - Centro del mapa {lat, lng}
 * @param {number} props.zoom - Nivel de zoom (default: 13)
 * @param {number} props.userRadius - Radio alrededor del usuario en metros (default: 2000)
 * @param {Function} props.onBusClick - Callback cuando se hace clic en un bus
 * @param {number} props.selectedBusId - ID del bus seleccionado
 * @param {string} props.height - Altura del mapa (default: '500px')
 * @param {boolean} props.showUserCircle - Mostrar círculo alrededor del usuario
 */
function BusMapGoogle({
    buses = [],
    userLocation = null,
    center = null,
    zoom = 13,
    userRadius = 2000,
    onBusClick = null,
    selectedBusId = null,
    height = '500px',
    showUserCircle = true
}) {
    const mapRef = useRef(null);
    const [map, setMap] = useState(null);
    const [googleMapsLoaded, setGoogleMapsLoaded] = useState(false);
    const [mapMarkers, setMapMarkers] = useState([]);
    const userMarkerRef = useRef(null);
    const userCircleRef = useRef(null);
    const platform = isWebPlatform() ? 'web' : 'native';

    // Centro del mapa
    const mapCenter = center || userLocation || { lat: -17.7833, lng: -63.1821 };

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
                try {
                    GoogleMap.destroy({ id: 'bus-map' });
                } catch (error) {
                    console.error('Error destroying map:', error);
                }
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
                center: mapCenter,
                zoom: zoom,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
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
                id: 'bus-map',
                element: mapRef.current,
                apiKey: GOOGLE_MAPS_API_KEY,
                config: {
                    center: mapCenter,
                    zoom: zoom,
                },
            });

            setMap(mapInstance);
            setGoogleMapsLoaded(true);
        } catch (error) {
            console.error('Error cargando Google Maps (Nativo):', error);
        }
    };

    // Actualizar marcadores de buses
    useEffect(() => {
        if (!map || !googleMapsLoaded) return;

        if (platform === 'web') {
            updateBusMarkersWeb();
        } else {
            updateBusMarkersNative();
        }
    }, [buses, map, googleMapsLoaded, selectedBusId]);

    // Actualizar ubicación del usuario
    useEffect(() => {
        if (!map || !googleMapsLoaded || !userLocation) return;

        if (platform === 'web') {
            updateUserLocationWeb();
        } else {
            updateUserLocationNative();
        }
    }, [userLocation, map, googleMapsLoaded, showUserCircle, userRadius]);

    // Actualizar marcadores de buses en Web
    const updateBusMarkersWeb = () => {
        // Limpiar marcadores anteriores
        mapMarkers.forEach(marker => marker.setMap(null));

        // Crear nuevos marcadores
        const newMarkers = buses.map(bus => {
            const isSelected = selectedBusId === (bus.bus_id || bus.id);

            const marker = new google.maps.Marker({
                position: { lat: bus.latitude, lng: bus.longitude },
                map: map,
                title: bus.bus_plate || bus.plate,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: isSelected ? 12 : 10,
                    fillColor: isSelected ? '#ef4444' : '#3b82f6',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: isSelected ? 3 : 2,
                },
                zIndex: isSelected ? 1000 : 100
            });

            // InfoWindow para mostrar información
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="min-width: 150px; font-family: system-ui;">
                        <strong style="font-size: 14px; color: #1e293b;">
                            ${bus.bus_plate || bus.plate}
                        </strong>
                        <br />
                        <span style="font-size: 12px; color: #64748b;">
                            ${bus.ruta_nombre || bus.route_name || 'Sin ruta'}
                        </span>
                        ${bus.driver_name ? `
                            <br />
                            <span style="font-size: 11px; color: #64748b;">
                                Chofer: ${bus.driver_name}
                            </span>
                        ` : ''}
                        ${bus.speed ? `
                            <br />
                            <span style="font-size: 11px; color: #64748b;">
                                Velocidad: ${bus.speed.toFixed(1)} km/h
                            </span>
                        ` : ''}
                        ${bus.distance_km ? `
                            <br />
                            <span style="font-size: 11px; color: #10b981; font-weight: 600;">
                                📍 ${bus.distance_km} km de distancia
                            </span>
                        ` : ''}
                    </div>
                `
            });

            // Evento de click
            marker.addListener('click', () => {
                infoWindow.open(map, marker);
                if (onBusClick) {
                    onBusClick(bus);
                }
            });

            return marker;
        });

        setMapMarkers(newMarkers);

        // Ajustar bounds del mapa para mostrar todos los buses
        if (newMarkers.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            buses.forEach(bus => {
                bounds.extend({ lat: bus.latitude, lng: bus.longitude });
            });
            if (userLocation) {
                bounds.extend(userLocation);
            }
            map.fitBounds(bounds);
        }
    };

    // Actualizar marcadores de buses en Native
    const updateBusMarkersNative = async () => {
        try {
            await map.removeAllMapMarkers();

            const markerIds = await map.addMarkers(
                buses.map(bus => ({
                    coordinate: {
                        lat: bus.latitude,
                        lng: bus.longitude
                    },
                    title: bus.bus_plate || bus.plate,
                    snippet: `${bus.ruta_nombre || bus.route_name || 'Sin ruta'}${bus.distance_km ? ` - ${bus.distance_km} km` : ''}`
                }))
            );

            if (onBusClick) {
                await map.setOnMarkerClickListener((marker) => {
                    const bus = buses[marker.markerId];
                    if (bus) {
                        onBusClick(bus);
                    }
                });
            }
        } catch (error) {
            console.error('Error actualizando marcadores buses (Nativo):', error);
        }
    };

    // Actualizar ubicación del usuario en Web
    const updateUserLocationWeb = () => {
        // Eliminar marcador anterior del usuario
        if (userMarkerRef.current) {
            userMarkerRef.current.setMap(null);
        }
        if (userCircleRef.current) {
            userCircleRef.current.setMap(null);
        }

        // Crear marcador del usuario
        const userMarker = new google.maps.Marker({
            position: userLocation,
            map: map,
            title: 'Tu ubicación',
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: '#10b981',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 3,
            },
            zIndex: 2000
        });

        userMarkerRef.current = userMarker;

        // Crear círculo alrededor del usuario
        if (showUserCircle) {
            const userCircle = new google.maps.Circle({
                strokeColor: '#10b981',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#10b981',
                fillOpacity: 0.15,
                map: map,
                center: userLocation,
                radius: userRadius
            });

            userCircleRef.current = userCircle;
        }
    };

    // Actualizar ubicación del usuario en Native
    const updateUserLocationNative = async () => {
        try {
            await map.addMarker({
                coordinate: userLocation,
                title: 'Tu ubicación'
            });
        } catch (error) {
            console.error('Error actualizando ubicación usuario (Nativo):', error);
        }
    };

    return (
        <div style={{ height, width: '100%', position: 'relative', borderRadius: '8px', overflow: 'hidden' }}>
            <div
                ref={mapRef}
                style={{ height: '100%', width: '100%' }}
                id="bus-map"
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
                            <p>Cargando mapa de Google...</p>
                            <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                        </div>
                    </div>
                )}
            </div>

            {/* Leyenda flotante */}
            {googleMapsLoaded && (
                <div style={{
                    position: 'absolute',
                    bottom: '20px',
                    left: '20px',
                    background: 'white',
                    padding: '10px 15px',
                    borderRadius: '8px',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                    zIndex: 1000,
                    fontSize: '12px'
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '5px' }}>
                        <div style={{ width: '12px', height: '12px', background: '#3b82f6', borderRadius: '50%' }}></div>
                        <span>Buses activos ({buses.length})</span>
                    </div>
                    {userLocation && (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <div style={{ width: '12px', height: '12px', background: '#10b981', borderRadius: '50%' }}></div>
                            <span>Tu ubicación</span>
                        </div>
                    )}
                    <div style={{ marginTop: '8px', paddingTop: '8px', borderTop: '1px solid #e5e7eb', fontSize: '10px', color: '#9ca3af' }}>
                        Powered by Google Maps
                    </div>
                </div>
            )}
        </div>
    );
}

export default BusMapGoogle;
