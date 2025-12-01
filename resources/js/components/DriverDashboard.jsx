import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import CameraButton from './CameraButton';
import HorasModal from './HorasModal';
import { API_BASE_URL, POLLING_INTERVAL } from '../config';
import { useGPSTracking } from '../hooks/useGPSTracking';

const apiClient = axios.create({
    baseURL: API_BASE_URL
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('driver_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

function DriverDashboard() {
    const navigate = useNavigate();
    const [driverData, setDriverData] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [isTripActive, setIsTripActive] = useState(false);
    const [busId, setBusId] = useState(null);
    const [driverId, setDriverId] = useState(null);
    const [availableBuses, setAvailableBuses] = useState([]);
    const [isActionLoading, setIsActionLoading] = useState(false);
    const [notification, setNotification] = useState(null);
    const [lastTransactionCount, setLastTransactionCount] = useState(0);
    const sessionIdRef = useRef((() => {
        const existing = sessionStorage.getItem('driver_session_id');
        if (existing) return existing;
        const newId = Date.now() + '_' + Math.random().toString(36);
        sessionStorage.setItem('driver_session_id', newId);
        return newId;
    })());

    const [lastEventId, setLastEventId] = useState(() => {
        const saved = sessionStorage.getItem(`driver_last_event_${sessionIdRef.current}`);
        return saved ? parseInt(saved) : 0;
    });

    const isInitialLoadRef = useRef(true);
    const notifiedEventsRef = useRef(new Set());

    const [tripReport, setTripReport] = useState('');
    const [tripReportPhoto, setTripReportPhoto] = useState(null);
    const [showReportModal, setShowReportModal] = useState(false);
    const [isFinalizingTrip, setIsFinalizingTrip] = useState(false);

    const [searchCardUid, setSearchCardUid] = useState('');
    const [searchResults, setSearchResults] = useState(null);
    const [searchLoading, setSearchLoading] = useState(false);
    const [refundRequests, setRefundRequests] = useState([]);
    const [showRefundModal, setShowRefundModal] = useState(false);
    const [selectedTransaction, setSelectedTransaction] = useState(null);
    const [refundReason, setRefundReason] = useState('');

    const [allTransactions, setAllTransactions] = useState([]);
    const [loadingAllTransactions, setLoadingAllTransactions] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [filterType, setFilterType] = useState('all');

    const [showHorasModal, setShowHorasModal] = useState(false);

    const [completedRefunds, setCompletedRefunds] = useState([]);
    const [loadingRefunds, setLoadingRefunds] = useState(false);
    const [reversalReason, setReversalReason] = useState('');
    const [selectedRefundForReversal, setSelectedRefundForReversal] = useState(null);

    const [showStartTurnoModal, setShowStartTurnoModal] = useState(false);
    const [showEndTurnoModal, setShowEndTurnoModal] = useState(false);
    const [showEndTripModal, setShowEndTripModal] = useState(false);
    
    // Estados para el nuevo modal de inicio de viaje
    const [showStartTripModal, setShowStartTripModal] = useState(false);
    const [cambiarBus, setCambiarBus] = useState(false);
    const [nuevoBusId, setNuevoBusId] = useState('');

    const [busesDisponibles, setBusesDisponibles] = useState([]);
    const [selectedBusForTurno, setSelectedBusForTurno] = useState(null);
    const [horaFinProgramada, setHoraFinProgramada] = useState('');
    const [turnoLoading, setTurnoLoading] = useState(false);
    const [tipoViaje, setTipoViaje] = useState('ida');
    const [crearViajeVuelta, setCrearViajeVuelta] = useState(true);
    const [hasTripsToday, setHasTripsToday] = useState(false);
    const [activeDriverTab, setActiveDriverTab] = useState('main'); // Pestañas: main, history, refunds

    const gpsTracking = useGPSTracking({
        busId: busId,
        isTripActive: isTripActive,
        token: localStorage.getItem('driver_token'),
        apiBaseUrl: API_BASE_URL
    });

    const fetchDriverTimeRecords = async () => {
        try {
            const response = await apiClient.get('/api/driver/time-records/turno');
            setHasTripsToday(response.data.length > 0);
        } catch (err) {
            console.error("Error fetching driver time records:", err);
            setHasTripsToday(false);
        }
    };

    useEffect(() => {
        let intervalId;
        let isMounted = true;
        const safeFetch = async () => {
            if (isMounted) {
                await fetchDriverData();
                await fetchDriverTimeRecords();
            }
        };
        safeFetch();
        intervalId = setInterval(safeFetch, POLLING_INTERVAL);
        return () => {
            isMounted = false;
            clearInterval(intervalId);
        };
    }, []);

    const fetchAvailableBuses = async () => {
        try {
            const response = await apiClient.get('/api/driver/buses');
            setAvailableBuses(response.data);
        } catch (err) {
            console.error("Error fetching available buses:", err);
        }
    };

    const fetchDriverData = async () => {
        const isFirstLoad = !driverData && !isTripActive;
        if (isFirstLoad) setLoading(true);
        if (isFirstLoad) setError(null);

        try {
            const statusResponse = await apiClient.get('/api/driver/current-trip-status');
            setDriverData(statusResponse.data);
            setIsTripActive(true);
            setBusId(statusResponse.data.trip.bus_id);
            setDriverId(statusResponse.data.trip.driver_id);
            const transactionsResponse = await apiClient.get('/api/driver/current-trip-transactions');
            setTransactions(transactionsResponse.data);
            // ... (resto de la lógica de fetch)
        } catch (err) {
            if (err.response && err.response.status === 404) {
                setIsTripActive(false);
                setDriverData(null);
                setTransactions([]);
                if (!driverId || availableBuses.length === 0) {
                    try {
                        const [profileResponse, busesResponse] = await Promise.all([
                            apiClient.get('/api/profile'),
                            apiClient.get('/api/driver/buses')
                        ]);
                        setDriverId(profileResponse.data.id);
                        setAvailableBuses(busesResponse.data);
                    } catch (profileErr) {
                        console.error("Error fetching driver profile:", profileErr);
                    }
                }
            } else {
                // ... (manejo de otros errores)
            }
        } finally {
            if (isFirstLoad) setLoading(false);
        }
    };
    
    // ... (otras funciones como handleEndTripWithReport, handleSaveReport, etc. se mantienen igual)

    const handleOpenStartTripModal = () => {
        if (!busId) {
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'Por favor, selecciona un bus antes de iniciar un viaje.'
            });
            return;
        }
        setCambiarBus(false);
        setNuevoBusId('');
        setShowStartTripModal(true);
    };

    const handleConfirmStartTrip = async () => {
        setIsActionLoading(true);
        setShowStartTripModal(false);

        let finalBusId = busId;
        if (cambiarBus && nuevoBusId) {
            finalBusId = nuevoBusId;
        } else if (cambiarBus && !nuevoBusId) {
            showNotification({ type: 'error', title: 'Error', message: 'Por favor, selecciona el nuevo bus.' });
            setIsActionLoading(false);
            return;
        }

        try {
            const payload = {
                bus_id: finalBusId,
                tipo_viaje: tipoViaje,
                cambio_bus: cambiarBus,
                nuevo_bus_id: cambiarBus ? nuevoBusId : null
            };
            await apiClient.post('/api/driver/trip/start-with-turno', payload);
            await fetchDriverData();
            showNotification({ type: 'success', title: 'Viaje iniciado', message: `Viaje de ${tipoViaje.toUpperCase()} iniciado.` });
        } catch (err) {
            const errorMessage = err.response?.data?.error || err.response?.data?.message || 'Error al iniciar viaje';
            showNotification({ type: 'error', title: 'Error', message: errorMessage });
        } finally {
            setIsActionLoading(false);
        }
    };

    const handleLogout = () => {
        // ... (lógica de logout)
    };
    
    const showNotification = (notif) => {
        // ... (lógica de notificaciones)
    };

    if (loading && !driverData && !isTripActive && !driverId) {
        return ( <div>Cargando...</div> );
    }

    return (
        <div style={{ minHeight: '100vh', background: 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)', fontFamily: 'system-ui' }}>
            <div style={{ background: 'rgba(255,255,255,0.95)', padding: '16px 20px', position: 'sticky', top: 0, zIndex: 100 }}>
                {/* ... (Header sin cambios) ... */}
            </div>

            <div style={{ maxWidth: '800px', margin: '0 auto', padding: '20px' }}>
                {error && ( <div style={{color: 'red'}}>{error}</div> )}

                {!isTripActive ? (
                    <div style={{ background: 'white', borderRadius: '16px', padding: '40px 30px', textAlign: 'center' }}>
                        {/* ... (vista de "No hay viaje activo" sin cambios) ... */}
                        <button onClick={handleOpenStartTripModal} disabled={!busId || isActionLoading} style={{ width: '100%', padding: '16px', background: 'green', color: 'white' }}>
                            {isActionLoading ? 'Iniciando...' : 'Iniciar Viaje'}
                        </button>
                    </div>
                ) : (
                    <>
                        <div style={{ background: 'white', borderRadius: '12px', padding: '16px', marginBottom: '16px' }}>
                             {/* ... (Contenido de la tarjeta de información del viaje) ... */}
                        </div>

                        <div style={{ display: 'flex', gap: '8px', marginBottom: '16px' }}>
                            <button onClick={() => setActiveDriverTab('main')} style={{ flex: 1, padding: '10px', borderRadius: '8px', background: activeDriverTab === 'main' ? 'white' : 'transparent', color: activeDriverTab === 'main' ? '#1e3a8a' : 'white' }}>
                                 resumen
                            </button>
                            <button onClick={() => setActiveDriverTab('history')} style={{ flex: 1, padding: '10px', borderRadius: '8px', background: activeDriverTab === 'history' ? 'white' : 'transparent', color: activeDriverTab === 'history' ? '#1e3a8a' : 'white' }}>
                                Historial
                            </button>
                            <button onClick={() => setActiveDriverTab('refunds')} style={{ flex: 1, padding: '10px', borderRadius: '8px', background: activeDriverTab === 'refunds' ? 'white' : 'transparent', color: activeDriverTab === 'refunds' ? '#1e3a8a' : 'white' }}>
                                Devoluciones
                            </button>
                        </div>
                        
                        {activeDriverTab === 'main' && (
                            <>
                                {/* ... (Contenido principal: botones de acción y lista de transacciones recientes) ... */}
                            </>
                        )}
                        {activeDriverTab === 'history' && (
                            <div style={{ background: 'white', borderRadius: '16px', padding: '24px' }}>
                                <h3>Historial Completo</h3>
                                {/* ... (Aquí iría la lista completa de transacciones (`allTransactions`)) ... */}
                            </div>
                        )}
                        {activeDriverTab === 'refunds' && (
                            <div style={{ background: 'white', borderRadius: '16px', padding: '24px' }}>
                                <h3>Sistema de Devoluciones</h3>
                                {/* ... (Aquí iría el sistema de devoluciones (`refundRequests`)) ... */}
                            </div>
                        )}
                    </>
                )}
            </div>

            {showStartTripModal && (
                <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.6)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <div style={{ background: 'white', borderRadius: '16px', padding: '24px', maxWidth: '500px' }}>
                        <h3>Confirmar Inicio de Viaje</h3>
                        <p>Vas a iniciar un viaje de <strong>{tipoViaje.toUpperCase()}</strong> en el bus <strong>{availableBuses.find(b => b.id == busId)?.plate}</strong>.</p>
                        <label><input type="checkbox" checked={cambiarBus} onChange={(e) => setCambiarBus(e.target.checked)} /> ¿Deseas cambiar de bus?</label>
                        {cambiarBus && (
                            <select value={nuevoBusId} onChange={(e) => setNuevoBusId(e.target.value)}>
                                <option value="">-- Nuevo Bus --</option>
                                {availableBuses.map(bus => <option key={bus.id} value={bus.id}>{bus.plate}</option>)}
                            </select>
                        )}
                        <button onClick={() => setShowStartTripModal(false)}>Cancelar</button>
                        <button onClick={handleConfirmStartTrip} disabled={isActionLoading}>
                            {isActionLoading ? 'Iniciando...' : 'Confirmar e Iniciar'}
                        </button>
                    </div>
                </div>
            )}
             {/* ... (otros modales) ... */}
        </div>
    );
}

export default DriverDashboard;