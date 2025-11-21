import React from 'react';
import { calculateETA, formatDistance, getETAColor, getETAEmoji } from '../utils/etaCalculator';

/**
 * Modal emergente con información detallada del bus
 *
 * @param {Object} props
 * @param {boolean} props.isOpen - Si el modal está abierto
 * @param {Function} props.onClose - Función para cerrar el modal
 * @param {Object} props.bus - Datos del bus seleccionado
 * @param {Object} props.userLocation - Ubicación del usuario {lat, lng}
 */
function BusInfoModal({ isOpen, onClose, bus, userLocation }) {
    if (!isOpen || !bus) return null;

    // Calcular ETA si tenemos ubicación del usuario
    const eta = userLocation ? calculateETA(bus.distance_km || 0, bus.speed) : null;
    const etaColor = eta ? getETAColor(eta.minutes) : '#6b7280';
    const etaEmoji = eta ? getETAEmoji(eta.minutes) : '📍';

    return (
        <>
            {/* Overlay */}
            <div
                onClick={onClose}
                style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0, 0, 0, 0.5)',
                    zIndex: 9998,
                    animation: 'fadeIn 0.2s ease-out'
                }}
            />

            {/* Modal */}
            <div style={{
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: 'white',
                borderRadius: '16px',
                boxShadow: '0 20px 60px rgba(0, 0, 0, 0.3)',
                zIndex: 9999,
                maxWidth: '400px',
                width: '90%',
                maxHeight: '80vh',
                overflow: 'auto',
                animation: 'slideUp 0.3s ease-out'
            }}>
                {/* Header con gradiente */}
                <div style={{
                    background: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                    padding: '20px',
                    borderTopLeftRadius: '16px',
                    borderTopRightRadius: '16px',
                    position: 'relative'
                }}>
                    <button
                        onClick={onClose}
                        style={{
                            position: 'absolute',
                            top: '15px',
                            right: '15px',
                            background: 'rgba(255, 255, 255, 0.2)',
                            border: 'none',
                            borderRadius: '50%',
                            width: '32px',
                            height: '32px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer',
                            color: 'white',
                            fontSize: '20px',
                            fontWeight: 'bold',
                            transition: 'background 0.2s'
                        }}
                        onMouseEnter={(e) => e.target.style.background = 'rgba(255, 255, 255, 0.3)'}
                        onMouseLeave={(e) => e.target.style.background = 'rgba(255, 255, 255, 0.2)'}
                    >
                        ×
                    </button>

                    <div style={{ textAlign: 'center', color: 'white' }}>
                        <div style={{ fontSize: '40px', marginBottom: '10px' }}>🚌</div>
                        <h2 style={{ margin: '0 0 5px 0', fontSize: '24px', fontWeight: '700' }}>
                            {bus.bus_plate || bus.plate}
                        </h2>
                        <p style={{ margin: 0, fontSize: '14px', opacity: 0.9 }}>
                            {bus.ruta_nombre || bus.route_name}
                        </p>
                    </div>
                </div>

                {/* Contenido */}
                <div style={{ padding: '20px' }}>
                    {/* ETA - Destacado */}
                    {eta && (
                        <div style={{
                            background: `linear-gradient(135deg, ${etaColor}15, ${etaColor}25)`,
                            border: `2px solid ${etaColor}`,
                            borderRadius: '12px',
                            padding: '16px',
                            marginBottom: '20px',
                            textAlign: 'center'
                        }}>
                            <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px', fontWeight: '600' }}>
                                Tiempo Estimado de Llegada
                            </div>
                            <div style={{
                                fontSize: '36px',
                                fontWeight: '700',
                                color: etaColor,
                                marginBottom: '4px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '10px'
                            }}>
                                <span>{etaEmoji}</span>
                                <span>{eta.text}</span>
                            </div>
                            <div style={{ fontSize: '12px', color: '#6b7280' }}>
                                Basado en distancia de {formatDistance(parseFloat(eta.distanceKm))}
                                {eta.speed && ` y velocidad de ${Math.round(eta.speed)} km/h`}
                            </div>
                        </div>
                    )}

                    {/* Información del bus */}
                    <div style={{ display: 'grid', gap: '12px' }}>
                        {/* Chofer */}
                        <div style={{
                            background: '#f8fafc',
                            borderRadius: '10px',
                            padding: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px'
                        }}>
                            <div style={{
                                width: '40px',
                                height: '40px',
                                background: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                                borderRadius: '50%',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontSize: '20px'
                            }}>
                                👨‍✈️
                            </div>
                            <div style={{ flex: 1 }}>
                                <div style={{ fontSize: '11px', color: '#6b7280', marginBottom: '2px' }}>Chofer</div>
                                <div style={{ fontSize: '14px', fontWeight: '600', color: '#1e293b' }}>
                                    {bus.driver_name || 'No disponible'}
                                </div>
                            </div>
                        </div>

                        {/* Grid de datos */}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                            {/* Línea */}
                            <div style={{
                                background: '#f8fafc',
                                borderRadius: '10px',
                                padding: '12px',
                                textAlign: 'center'
                            }}>
                                <div style={{ fontSize: '11px', color: '#6b7280', marginBottom: '4px' }}>Línea</div>
                                <div style={{ fontSize: '14px', fontWeight: '600', color: '#1e293b' }}>
                                    {bus.ruta_nombre || bus.route_name || 'N/A'}
                                </div>
                            </div>

                            {/* Placa */}
                            <div style={{
                                background: '#f8fafc',
                                borderRadius: '10px',
                                padding: '12px',
                                textAlign: 'center'
                            }}>
                                <div style={{ fontSize: '11px', color: '#6b7280', marginBottom: '4px' }}>Placa</div>
                                <div style={{ fontSize: '14px', fontWeight: '600', color: '#1e293b' }}>
                                    {bus.bus_plate || bus.plate || 'N/A'}
                                </div>
                            </div>

                            {/* Distancia */}
                            {bus.distance_km && (
                                <div style={{
                                    background: '#f8fafc',
                                    borderRadius: '10px',
                                    padding: '12px',
                                    textAlign: 'center'
                                }}>
                                    <div style={{ fontSize: '11px', color: '#6b7280', marginBottom: '4px' }}>Distancia</div>
                                    <div style={{ fontSize: '14px', fontWeight: '600', color: '#10b981' }}>
                                        {formatDistance(bus.distance_km)}
                                    </div>
                                </div>
                            )}

                            {/* Velocidad */}
                            {bus.speed && (
                                <div style={{
                                    background: '#f8fafc',
                                    borderRadius: '10px',
                                    padding: '12px',
                                    textAlign: 'center'
                                }}>
                                    <div style={{ fontSize: '11px', color: '#6b7280', marginBottom: '4px' }}>Velocidad</div>
                                    <div style={{ fontSize: '14px', fontWeight: '600', color: '#1e293b' }}>
                                        {bus.speed.toFixed(1)} km/h
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Última actualización */}
                    {bus.last_update && (
                        <div style={{
                            marginTop: '16px',
                            padding: '10px',
                            background: '#fef3c7',
                            borderRadius: '8px',
                            textAlign: 'center',
                            fontSize: '11px',
                            color: '#92400e'
                        }}>
                            ℹ️ Última actualización: {bus.last_update}
                        </div>
                    )}
                </div>
            </div>

            {/* CSS Animations */}
            <style>{`
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translate(-50%, -45%);
                    }
                    to {
                        opacity: 1;
                        transform: translate(-50%, -50%);
                    }
                }
            `}</style>
        </>
    );
}

export default BusInfoModal;
