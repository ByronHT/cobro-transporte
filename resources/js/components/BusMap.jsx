import React, { useEffect, useRef, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap, Circle } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix para iconos de Leaflet en webpack/vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

// Icono personalizado para buses
const busIcon = new L.Icon({
    iconUrl: 'data:image/svg+xml;base64,' + btoa(`
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#3b82f6" width="32" height="32">
            <path d="M12 2C7 2 3 6 3 11c0 5.25 9 13 9 13s9-7.75 9-13c0-5-4-9-9-9zm0 12.5c-1.93 0-3.5-1.57-3.5-3.5S10.07 7.5 12 7.5s3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
        </svg>
    `),
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

// Icono para bus seleccionado
const busIconSelected = new L.Icon({
    iconUrl: 'data:image/svg+xml;base64,' + btoa(`
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ef4444" width="40" height="40">
            <path d="M12 2C7 2 3 6 3 11c0 5.25 9 13 9 13s9-7.75 9-13c0-5-4-9-9-9zm0 12.5c-1.93 0-3.5-1.57-3.5-3.5S10.07 7.5 12 7.5s3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/>
        </svg>
    `),
    iconSize: [40, 40],
    iconAnchor: [20, 40],
    popupAnchor: [0, -40]
});

// Icono para ubicación del usuario
const userIcon = new L.Icon({
    iconUrl: 'data:image/svg+xml;base64,' + btoa(`
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#10b981" width="28" height="28">
            <circle cx="12" cy="12" r="8" fill="#10b981" stroke="white" stroke-width="3"/>
            <circle cx="12" cy="12" r="4" fill="white"/>
        </svg>
    `),
    iconSize: [28, 28],
    iconAnchor: [14, 14],
    popupAnchor: [0, 0]
});

/**
 * Componente interno para manejar cambios de centro del mapa
 */
function MapController({ center, zoom }) {
    const map = useMap();

    useEffect(() => {
        if (center) {
            map.setView(center, zoom || map.getZoom());
        }
    }, [center, zoom, map]);

    return null;
}

/**
 * Componente de Mapa de Buses con OpenStreetMap
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
function BusMap({
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
    const [mapCenter, setMapCenter] = useState(center || { lat: -17.7833, lng: -63.1821 }); // Santa Cruz, Bolivia
    const [mapZoom, setMapZoom] = useState(zoom);
    const mapRef = useRef(null);

    // Actualizar centro cuando cambian las props
    useEffect(() => {
        if (center) {
            setMapCenter(center);
        } else if (userLocation) {
            setMapCenter(userLocation);
        } else if (buses.length > 0) {
            // Centrar en el primer bus si no hay ubicación de usuario
            setMapCenter({ lat: buses[0].latitude, lng: buses[0].longitude });
        }
    }, [center, userLocation, buses]);

    // Auto-ajustar zoom para mostrar todos los buses
    useEffect(() => {
        if (mapRef.current && buses.length > 1) {
            const bounds = L.latLngBounds(
                buses.map(bus => [bus.latitude, bus.longitude])
            );
            if (userLocation) {
                bounds.extend([userLocation.lat, userLocation.lng]);
            }
            mapRef.current.fitBounds(bounds, { padding: [50, 50] });
        }
    }, [buses, userLocation]);

    return (
        <div style={{ height, width: '100%', position: 'relative', borderRadius: '8px', overflow: 'hidden' }}>
            <MapContainer
                center={[mapCenter.lat, mapCenter.lng]}
                zoom={mapZoom}
                style={{ height: '100%', width: '100%' }}
                ref={mapRef}
                scrollWheelZoom={true}
                zoomControl={true}
            >
                <MapController center={[mapCenter.lat, mapCenter.lng]} zoom={mapZoom} />

                {/* OpenStreetMap Tile Layer */}
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    maxZoom={19}
                />

                {/* Marcador de ubicación del usuario */}
                {userLocation && (
                    <>
                        <Marker
                            position={[userLocation.lat, userLocation.lng]}
                            icon={userIcon}
                        >
                            <Popup>
                                <strong>Tu ubicación</strong>
                            </Popup>
                        </Marker>

                        {/* Círculo alrededor del usuario */}
                        {showUserCircle && (
                            <Circle
                                center={[userLocation.lat, userLocation.lng]}
                                radius={userRadius}
                                pathOptions={{
                                    color: '#10b981',
                                    fillColor: '#10b981',
                                    fillOpacity: 0.1,
                                    weight: 2
                                }}
                            />
                        )}
                    </>
                )}

                {/* Marcadores de buses */}
                {buses.map((bus) => (
                    <Marker
                        key={bus.bus_id || bus.id}
                        position={[bus.latitude, bus.longitude]}
                        icon={selectedBusId === (bus.bus_id || bus.id) ? busIconSelected : busIcon}
                        eventHandlers={{
                            click: () => {
                                if (onBusClick) {
                                    onBusClick(bus);
                                }
                            }
                        }}
                    >
                        <Popup>
                            <div style={{ minWidth: '150px' }}>
                                <strong style={{ fontSize: '14px', color: '#1e293b' }}>
                                    {bus.bus_plate || bus.plate}
                                </strong>
                                <br />
                                <span style={{ fontSize: '12px', color: '#64748b' }}>
                                    {bus.ruta_nombre || bus.route_name}
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
                                {bus.trip_earnings && (
                                    <>
                                        <br />
                                        <span style={{ fontSize: '11px', color: '#059669', fontWeight: '600' }}>
                                            💰 Bs {bus.trip_earnings}
                                        </span>
                                    </>
                                )}
                            </div>
                        </Popup>
                    </Marker>
                ))}
            </MapContainer>

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
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <div style={{ width: '12px', height: '12px', background: '#10b981', borderRadius: '50%' }}></div>
                        <span>Tu ubicación</span>
                    </div>
                )}
            </div>
        </div>
    );
}

export default BusMap;
