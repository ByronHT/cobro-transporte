import React, { useState, useCallback, useRef, useEffect } from 'react';
import { GoogleMap, useLoadScript, Marker, Circle, InfoWindow, Polyline } from '@react-google-maps/api';
import { GOOGLE_MAPS_API_KEY } from '../config';

const containerStyle = {
    width: '100%',
    height: '100%'
};

const defaultCenter = {
    lat: -17.7833,
    lng: -63.1821
};

const libraries = ['places', 'geometry'];

function useGoogleMaps() {
    const { isLoaded, loadError } = useLoadScript({
        googleMapsApiKey: GOOGLE_MAPS_API_KEY,
        libraries: libraries,
    });

    return { isLoaded, loadError };
}

/**
 * Componente de Mapa de Buses con Google Maps usando @react-google-maps/api
 * Funciona en Web y Android (Capacitor WebView)
 *
 * @param {Object} props - Props del componente
 * @param {Array} props.buses - Array de buses a mostrar
 * @param {Object} props.userLocation - Ubicación del usuario {lat, lng}
 * @param {Object} props.center - Centro del mapa {lat, lng}
 * @param {number} props.zoom - Nivel de zoom (default: 13)
 * @param {number} props.userRadius - Radio alrededor del usuario en metros
 * @param {Function} props.onBusClick - Callback cuando se hace clic en un bus
 * @param {number} props.selectedBusId - ID del bus seleccionado
 * @param {string} props.height - Altura del mapa (default: '500px')
 * @param {boolean} props.showUserCircle - Mostrar círculo alrededor del usuario
 * @param {Object} props.routeData - Datos de la ruta con waypoints ida/vuelta
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
    showUserCircle = true,
    routeData = null
}) {
    const [map, setMap] = useState(null);
    const [selectedMarker, setSelectedMarker] = useState(null);
    const [busMarkers, setBusMarkers] = useState({});
    const prevBusesRef = useRef({});

    const { isLoaded, loadError } = useGoogleMaps();

    const mapCenter = center || userLocation || defaultCenter;

    const calculateBearing = (lat1, lng1, lat2, lng2) => {
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const lat1Rad = lat1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;

        const y = Math.sin(dLng) * Math.cos(lat2Rad);
        const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) -
                  Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLng);

        const bearing = Math.atan2(y, x) * 180 / Math.PI;
        return (bearing + 360) % 360; // Normalizar a 0-360
    };

    const onLoad = useCallback((map) => {
        setMap(map);

        if (buses.length > 0 || userLocation) {
            const bounds = new window.google.maps.LatLngBounds();

            buses.forEach(bus => {
                bounds.extend({ lat: bus.latitude, lng: bus.longitude });
            });

            if (userLocation) {
                bounds.extend(userLocation);
            }

            map.fitBounds(bounds);
        }
    }, [buses, userLocation]);

    const onUnmount = useCallback(() => {
        setMap(null);
    }, []);

    const mapOptions = {
        disableDefaultUI: false,
        zoomControl: true,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
        styles: [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            }
        ]
    };

    if (!GOOGLE_MAPS_API_KEY || GOOGLE_MAPS_API_KEY === '') {
        return (
            <div style={{
                height,
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                background: '#fee2e2',
                color: '#dc2626',
                padding: '20px',
                textAlign: 'center'
            }}>
                <div>
                    <strong>⚠️ Error: API Key no configurada</strong>
                    <p style={{ fontSize: '14px', marginTop: '10px' }}>
                        Configura VITE_GOOGLE_MAPS_API_KEY en el archivo .env
                    </p>
                </div>
            </div>
        );
    }

    if (!isLoaded) {
        return (
            <div style={{
                height,
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
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
                    <p>Cargando Google Maps...</p>
                    <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                </div>
            </div>
        );
    }

    if (loadError) {
        return (
            <div style={{
                height,
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                background: '#fee2e2',
                color: '#dc2626',
                padding: '20px',
                textAlign: 'center'
            }}>
                <div>
                    <strong>⚠️ Error al cargar Google Maps</strong>
                    <p style={{ fontSize: '14px', marginTop: '10px' }}>
                        {loadError.message || 'Error desconocido'}
                    </p>
                    <p style={{ fontSize: '12px', marginTop: '5px', color: '#991b1b' }}>
                        Verifica tu conexión a internet y que la API Key sea válida
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div style={{ height, width: '100%', position: 'relative', borderRadius: '8px', overflow: 'hidden' }}>
                <GoogleMap
                    mapContainerStyle={containerStyle}
                    center={mapCenter}
                    zoom={zoom}
                    onLoad={onLoad}
                    onUnmount={onUnmount}
                    options={mapOptions}
                >
                    {/* Marcador de ubicación del usuario */}
                    {userLocation && (
                        <>
                            <Marker
                                position={userLocation}
                                title="Tu ubicación"
                                icon={{
                                    url: '/images/map-icons/user-3d.svg',
                                    scaledSize: new window.google.maps.Size(64, 64),
                                    anchor: new window.google.maps.Point(32, 58),
                                }}
                                zIndex={2000}
                            />

                            {/* Círculo alrededor del usuario */}
                            {showUserCircle && (
                                <Circle
                                    center={userLocation}
                                    radius={userRadius}
                                    options={{
                                        strokeColor: '#10b981',
                                        strokeOpacity: 0.8,
                                        strokeWeight: 2,
                                        fillColor: '#10b981',
                                        fillOpacity: 0.15,
                                    }}
                                />
                            )}
                        </>
                    )}

                    {/* Marcadores de buses */}
                    {buses.map((bus) => {
                        const busId = bus.bus_id || bus.id;
                        const isSelected = selectedBusId === busId;

                        let rotation = 0;
                        if (prevBusesRef.current[busId]) {
                            const prev = prevBusesRef.current[busId];
                            rotation = calculateBearing(
                                prev.latitude,
                                prev.longitude,
                                bus.latitude,
                                bus.longitude
                            );
                        }

                        prevBusesRef.current[busId] = {
                            latitude: bus.latitude,
                            longitude: bus.longitude
                        };

                        const tipoViaje = bus.tipo_viaje || 'ida'; // Default a 'ida' si no existe
                        let iconUrl;
                        if (isSelected) {
                            iconUrl = '/images/map-icons/bus-3d-selected.svg';
                        } else {
                            iconUrl = tipoViaje === 'vuelta'
                                ? '/images/map-icons/bus-3d-vuelta.svg'
                                : '/images/map-icons/bus-3d-ida.svg';
                        }

                        return (
                            <Marker
                                key={busId}
                                position={{ lat: bus.latitude, lng: bus.longitude }}
                                title={bus.bus_plate || bus.plate}
                                icon={{
                                    url: iconUrl,
                                    scaledSize: new window.google.maps.Size(isSelected ? 96 : 72, isSelected ? 96 : 72),
                                    anchor: new window.google.maps.Point(isSelected ? 48 : 36, isSelected ? 86 : 64),
                                    rotation: rotation
                                }}
                                zIndex={isSelected ? 1500 : 500}
                                onClick={() => {
                                    setSelectedMarker(bus);
                                    if (onBusClick) {
                                        onBusClick(bus);
                                    }
                                }}
                            >
                                {selectedMarker && selectedMarker.bus_id === busId && (
                                    <InfoWindow
                                        position={{ lat: bus.latitude, lng: bus.longitude }}
                                        onCloseClick={() => setSelectedMarker(null)}
                                    >
                                        <div style={{ minWidth: '150px', fontFamily: 'system-ui' }}>
                                            <strong style={{ fontSize: '14px', color: '#1e293b' }}>
                                                {bus.bus_plate || bus.plate}
                                            </strong>
                                            <br />
                                            <span style={{ fontSize: '12px', color: '#64748b' }}>
                                                {bus.ruta_nombre || bus.route_name || 'Sin ruta'}
                                            </span>
                                            <br />
                                            <span style={{
                                                fontSize: '11px',
                                                fontWeight: '600',
                                                color: tipoViaje === 'vuelta' ? '#16a34a' : '#2563eb',
                                                background: tipoViaje === 'vuelta' ? '#d1fae5' : '#dbeafe',
                                                padding: '2px 8px',
                                                borderRadius: '4px',
                                                display: 'inline-block',
                                                marginTop: '4px'
                                            }}>
                                                {tipoViaje === 'vuelta' ? '🟢 VUELTA' : '🔵 IDA'}
                                            </span>
                                            {bus.driver_name && (
                                                <>
                                                    <br />
                                                    <span style={{ fontSize: '11px', color: '#64748b' }}>
                                                        Chofer: {bus.driver_name}
                                                    </span>
                                                </>
                                            )}
                                            {bus.speed && (
                                                <>
                                                    <br />
                                                    <span style={{ fontSize: '11px', color: '#64748b' }}>
                                                        Velocidad: {bus.speed.toFixed(1)} km/h
                                                    </span>
                                                </>
                                            )}
                                            {bus.distance_km && (
                                                <>
                                                    <br />
                                                    <span style={{ fontSize: '11px', color: '#10b981', fontWeight: '600' }}>
                                                        📍 {bus.distance_km} km de distancia
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </InfoWindow>
                                )}
                            </Marker>
                        );
                    })}

                    {/* Polylines para rutas IDA y VUELTA */}
                    {routeData && routeData.ruta_ida_waypoints && Array.isArray(routeData.ruta_ida_waypoints) && routeData.ruta_ida_waypoints.length > 0 && (
                        <Polyline
                            path={routeData.ruta_ida_waypoints.map(wp => ({
                                lat: parseFloat(wp.lat || wp.latitude),
                                lng: parseFloat(wp.lng || wp.longitude)
                            }))}
                            options={{
                                strokeColor: '#2563eb',
                                strokeOpacity: 0.8,
                                strokeWeight: 5,
                                geodesic: true,
                                zIndex: 50
                            }}
                        />
                    )}

                    {routeData && routeData.ruta_vuelta_waypoints && Array.isArray(routeData.ruta_vuelta_waypoints) && routeData.ruta_vuelta_waypoints.length > 0 && (
                        <Polyline
                            path={routeData.ruta_vuelta_waypoints.map(wp => ({
                                lat: parseFloat(wp.lat || wp.latitude),
                                lng: parseFloat(wp.lng || wp.longitude)
                            }))}
                            options={{
                                strokeColor: '#16a34a',
                                strokeOpacity: 0.8,
                                strokeWeight: 5,
                                geodesic: true,
                                zIndex: 50
                            }}
                        />
                    )}
                </GoogleMap>

            {/* Leyenda flotante */}
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
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '5px' }}>
                        <div style={{ width: '12px', height: '12px', background: '#10b981', borderRadius: '50%' }}></div>
                        <span>Tu ubicación</span>
                    </div>
                )}
                {routeData && (
                    <>
                        {routeData.ruta_ida_waypoints && routeData.ruta_ida_waypoints.length > 0 && (
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '5px' }}>
                                <div style={{ width: '20px', height: '3px', background: '#2563eb', borderRadius: '2px' }}></div>
                                <span>Ruta IDA</span>
                            </div>
                        )}
                        {routeData.ruta_vuelta_waypoints && routeData.ruta_vuelta_waypoints.length > 0 && (
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '5px' }}>
                                <div style={{ width: '20px', height: '3px', background: '#16a34a', borderRadius: '2px' }}></div>
                                <span>Ruta VUELTA</span>
                            </div>
                        )}
                    </>
                )}
                <div style={{ marginTop: '8px', paddingTop: '8px', borderTop: '1px solid #e5e7eb', fontSize: '10px', color: '#9ca3af' }}>
                    Powered by Google Maps
                </div>
            </div>
        </div>
    );
}

export default BusMapGoogle;
