import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import ComplaintsSection from './ComplaintsSection';
import BusMapGoogle from './BusMapGoogle'; // Migrado a Google Maps
import BusInfoModal from './BusInfoModal';
import { API_BASE_URL, POLLING_INTERVAL } from '../config';

const apiClient = axios.create({
    baseURL: API_BASE_URL
});

apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('passenger_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

const formatBoliviaDate = (dateString, options = {}) => {
    if (!dateString) return 'Fecha no disponible';

    try {

        if (dateString.endsWith('Z')) {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) throw new Error('Invalid date');

            return date.toLocaleString('es-BO', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                timeZone: 'America/La_Paz',
                ...options
            });
        }

        if (dateString.includes('+') || dateString.includes('-04:00')) {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) throw new Error('Invalid date');
            return date.toLocaleString('es-BO', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                ...options
            });
        }

        const dateStr = dateString.includes('T') ? dateString : dateString.replace(' ', 'T');

        const parts = dateStr.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/);
        if (!parts) throw new Error('Invalid date format');

        const [, year, month, day, hour, minute, second] = parts;
        const date = new Date(year, month - 1, day, hour, minute, second);

        if (isNaN(date.getTime())) throw new Error('Invalid date');

        return date.toLocaleString('es-BO', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            ...options
        });
    } catch (error) {
        console.error('❌ Error formateando fecha:', dateString, error);
        return 'Fecha inválida';
    }
};

function PassengerDashboard() {
    const navigate = useNavigate();
    const [user, setUser] = useState(null);
    const [trips, setTrips] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [showQr, setShowQr] = useState(false);
    const [showBalance, setShowBalance] = useState(true); // Estado para mostrar/ocultar saldo
    const [transactions, setTransactions] = useState([]);
    const [notification, setNotification] = useState(null);
    const [previousBalance, setPreviousBalance] = useState(null);
    const sessionIdRef = useRef((() => {
        const existing = sessionStorage.getItem('passenger_session_id');
        if (existing) return existing;
        const newId = Date.now() + '_' + Math.random().toString(36);
        sessionStorage.setItem('passenger_session_id', newId);
        return newId;
    })());

    const [lastEventId, setLastEventId] = useState(() => {
        const saved = sessionStorage.getItem(`passenger_last_event_${sessionIdRef.current}`);
        return saved ? parseInt(saved) : 0;
    });

    const isInitialLoadRef = useRef(true);
    const qrCodeRef = useRef(null);

    const notifiedEventsRef = useRef(new Set());

    const [refundRequests, setRefundRequests] = useState([]);
    const [showRefundSection, setShowRefundSection] = useState(false);
    const [selectedTrip, setSelectedTrip] = useState(null);
    const [showRequestRefundModal, setShowRequestRefundModal] = useState(false);
    const [refundReason, setRefundReason] = useState('');
    const [refundActionLoading, setRefundActionLoading] = useState(false);

    const [allTrips, setAllTrips] = useState([]);
    const [loadingAllTrips, setLoadingAllTrips] = useState(false);

    const [activeTab, setActiveTab] = useState('inicio'); // inicio, movimientos, devoluciones, quejas, mas

    const [showFindLineView, setShowFindLineView] = useState(false);
    const [availableRoutes, setAvailableRoutes] = useState([]);
    const [selectedRouteId, setSelectedRouteId] = useState('');
    const [nearbyBuses, setNearbyBuses] = useState([]);
    const [userLocation, setUserLocation] = useState(null);
    const [loadingLocation, setLoadingLocation] = useState(false);
    const [loadingBuses, setLoadingBuses] = useState(false);
    const [selectedBus, setSelectedBus] = useState(null);
    const [showBusModal, setShowBusModal] = useState(false);

    const [tipoViajeFilter, setTipoViajeFilter] = useState('todos'); // 'todos', 'ida', 'vuelta'

    useEffect(() => {
        let intervalId;
        let isMounted = true;

        const fetchData = async () => {
            const token = localStorage.getItem('passenger_token');
            if (!token) {
                navigate('/login-passenger');
                return;
            }

            const isFirstLoad = !user;
            if (isFirstLoad) {
                setLoading(true);
            }

            try {
                const profileResponse = await apiClient.get('/api/profile');
                if (isMounted) {
                    const newUser = profileResponse.data;
                    const newBalance = parseFloat(newUser.balance);

                    if (previousBalance !== null && previousBalance !== newBalance) {
                        const difference = newBalance - previousBalance;
                        if (difference < 0) {
                            showNotification({
                                type: 'info',
                                title: 'Pago realizado',
                                message: `Se descontó ${Math.abs(difference).toFixed(2)} Bs de tu saldo`
                            });
                        } else if (difference > 0) {
                            showNotification({
                                type: 'success',
                                title: 'Recarga exitosa',
                                message: `Se agregaron ${difference.toFixed(2)} Bs a tu saldo`
                            });
                        }
                    }

                    setUser(newUser);
                    setPreviousBalance(newBalance);
                }

                const tripsUrl = tipoViajeFilter === 'todos'
                    ? '/api/trips'
                    : `/api/trips?tipo_viaje=${tipoViajeFilter}`;
                const tripsResponse = await apiClient.get(tripsUrl);
                if (isMounted) {
                    setTrips(tripsResponse.data.data);
                }

                const transactionsResponse = await apiClient.get('/api/transactions');
                if (isMounted) {
                    setTransactions(transactionsResponse.data.data || []);
                }

                const eventsResponse = await apiClient.get(`/api/passenger/payment-events?last_event_id=${lastEventId}`);
                const newEvents = eventsResponse.data;

                console.log('🔔 [PASSENGER] Eventos recibidos:', newEvents.length);
                console.log('🔔 [PASSENGER] isInitialLoad:', isInitialLoadRef.current);
                console.log('🔔 [PASSENGER] lastEventId:', lastEventId);

                const wasInitialLoad = isInitialLoadRef.current;
                if (isInitialLoadRef.current && isMounted) {
                    isInitialLoadRef.current = false;
                    console.log('🔔 [PASSENGER] Marcando isInitialLoad como false');
                }

                if (newEvents.length > 0 && isMounted) {
                    console.log('🔔 [PASSENGER] Procesando eventos. wasInitialLoad:', wasInitialLoad);
                    if (!wasInitialLoad) {
                        console.log('🔔 [PASSENGER] Mostrando notificaciones para', newEvents.length, 'eventos');
                        newEvents.forEach(event => {
                            if (notifiedEventsRef.current.has(event.id)) {
                                console.log('🔔 [PASSENGER] Evento ya notificado, omitiendo:', event.id);
                                return; // Skip este evento
                            }

                            console.log('🔔 [PASSENGER] Evento:', event.event_type, event.message);

                            notifiedEventsRef.current.add(event.id);

                            if (event.event_type === 'success') {
                                const rutaInfo = event.trip?.bus?.ruta
                                    ? `${event.trip.bus.ruta.nombre}${event.trip.bus.ruta.descripcion ? ' - ' + event.trip.bus.ruta.descripcion : ''}`
                                    : 'ruta';
                                showNotification({
                                    type: 'success',
                                    title: '✅ Pago realizado',
                                    message: `Pasaje pagado: ${parseFloat(event.amount).toFixed(2)} Bs en ${rutaInfo}`
                                });
                            } else if (event.event_type === 'recharge') {
                                showNotification({
                                    type: 'success',
                                    title: '💳 Recarga realizada',
                                    message: `Saldo abonado: ${parseFloat(event.amount).toFixed(2)} Bs`
                                });
                            } else if (event.event_type === 'insufficient_balance') {
                                showNotification({
                                    type: 'error',
                                    title: '❌ Saldo insuficiente',
                                    message: `Saldo: ${parseFloat(event.amount || 0).toFixed(2)} Bs / Requiere: ${parseFloat(event.required_amount).toFixed(2)} Bs. Por favor recarga tu tarjeta.`
                                });
                            } else if (event.event_type === 'inactive_card') {
                                showNotification({
                                    type: 'error',
                                    title: '❌ Tarjeta inactiva',
                                    message: 'Tu tarjeta está bloqueada. Contacta al administrador.'
                                });
                            } else if (event.event_type === 'error') {
                                showNotification({
                                    type: 'error',
                                    title: '❌ Error al procesar pago',
                                    message: 'Hubo un error al procesar tu pago. Intenta nuevamente.'
                                });
                            } else if (event.event_type === 'refund_approved') {
                                showNotification({
                                    type: 'success',
                                    title: '✅ Solicitud aprobada',
                                    message: event.message || `Tu solicitud de devolución fue aprobada. Se devolvieron ${parseFloat(event.amount).toFixed(2)} Bs a tu tarjeta.`
                                });
                                loadRefundRequests();
                            } else if (event.event_type === 'refund_rejected') {
                                showNotification({
                                    type: 'error',
                                    title: '❌ Solicitud rechazada',
                                    message: event.message || 'Tu solicitud de devolución fue rechazada por el chofer.'
                                });
                                loadRefundRequests();
                            } else if (event.event_type === 'refund_reversed') {
                                showNotification({
                                    type: 'warning',
                                    title: '🔁 Devolución revertida',
                                    message: event.message || `Una devolución de ${parseFloat(event.amount).toFixed(2)} Bs ha sido revertida.`
                                });
                                loadRefundRequests();
                                loadRecentTrips();
                            }
                        });
                    }

                    const maxId = Math.max(...newEvents.map(e => e.id));
                    setLastEventId(maxId);
                    sessionStorage.setItem(`passenger_last_event_${sessionIdRef.current}`, maxId.toString());
                }

            } catch (err) {
                console.error('Error en Dashboard:', err.response?.data || err.message);
                if (isFirstLoad && isMounted) {
                    setError(`No se pudieron cargar los datos: ${err.response?.data?.message || err.message}`);
                }
                if (err.response && err.response.status === 401) {
                    const errorMessage = err.response.data?.message || '';

                    if (errorMessage.toLowerCase().includes('unauthenticated') ||
                        errorMessage.toLowerCase().includes('token')) {
                        console.error('Token inválido. Cerrando sesión...');
                        localStorage.removeItem('passenger_token');
                        localStorage.removeItem('passenger_role');
                        localStorage.removeItem('passenger_name');
                        sessionStorage.clear();
                        navigate('/login');
                    } else {
                        console.warn('Error 401 temporal. Reintentando en siguiente ciclo...');
                    }
                }
            } finally {
                if (isFirstLoad && isMounted) {
                    setLoading(false);
                }
            }
        };

        fetchData(); // Primera carga (incluye transactions)
        intervalId = setInterval(fetchData, POLLING_INTERVAL); // Actualizar cada 5 segundos

        loadRefundRequests();

        return () => {
            isMounted = false;
            clearInterval(intervalId);
        };
    }, [navigate, tipoViajeFilter]); // Agregada dependencia navigate y tipoViajeFilter

    const handleLogout = () => {
        localStorage.removeItem('passenger_token');
        localStorage.removeItem('passenger_role');
        localStorage.removeItem('passenger_name');
        sessionStorage.clear(); // Limpiar toda la sesión
        navigate('/login');
    };

    const loadAllTrips = async () => {
        setLoadingAllTrips(true);
        try {
            const response = await apiClient.get('/api/trips?per=1000'); // Cargar hasta 1000 viajes
            setAllTrips(response.data.data || []);
        } catch (err) {
            console.error('Error loading all trips:', err);
            setError('No se pudieron cargar todos los viajes.');
        } finally {
            setLoadingAllTrips(false);
        }
    };

    const loadAvailableRoutes = async () => {
        try {
            const response = await apiClient.get('/api/passenger/available-routes');
            setAvailableRoutes(response.data.routes || []);
        } catch (err) {
            console.error('Error loading routes:', err);
        }
    };

    const getUserLocation = () => {
        setLoadingLocation(true);
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setUserLocation({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    });
                    setLoadingLocation(false);
                },
                (error) => {
                    console.error('Error getting location:', error);
                    setLoadingLocation(false);
                    showNotification({
                        type: 'error',
                        title: 'Error de ubicación',
                        message: 'No se pudo obtener tu ubicación. Verifica los permisos.'
                    });
                },
                {
                    enableHighAccuracy: false,
                    timeout: 10000,
                    maximumAge: 30000
                }
            );
        } else {
            setLoadingLocation(false);
            showNotification({
                type: 'error',
                title: 'Geolocalización no soportada',
                message: 'Tu navegador no soporta geolocalización.'
            });
        }
    };

    const loadNearbyBuses = async (routeId) => {
        if (!routeId) {
            setNearbyBuses([]);
            return;
        }

        setLoadingBuses(true);
        try {
            const response = await apiClient.get(`/api/passenger/nearby-buses`, {
                params: {
                    ruta_id: routeId,
                    latitude: userLocation?.lat,
                    longitude: userLocation?.lng
                }
            });
            setNearbyBuses(response.data.buses || []);
        } catch (err) {
            console.error('Error loading nearby buses:', err);
            setNearbyBuses([]);
        } finally {
            setLoadingBuses(false);
        }
    };

    const loadRefundRequests = async () => {
        try {
            const response = await apiClient.get('/api/passenger/refund-requests');
            if (response.data.success) {
                const newRequests = response.data.refund_requests;

                if (refundRequests.length > 0) {
                    const newPendingRequests = newRequests.filter(req =>
                        req.needs_verification &&
                        !refundRequests.some(oldReq => oldReq.id === req.id)
                    );

                    newPendingRequests.forEach(req => {
                        showNotification({
                            type: 'info',
                            title: '💰 Nueva solicitud de devolución',
                            message: `El chofer solicita devolver ${req.amount} Bs. Revisa y aprueba/rechaza.`
                        });
                    });
                }

                setRefundRequests(newRequests);
            }
        } catch (err) {
            console.error('Error loading refund requests:', err);
        }
    };

    const handleRequestRefund = async () => {
        if (!selectedTrip || !refundReason.trim()) {
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'Por favor, escribe el motivo de la devolución'
            });
            return;
        }

        setRefundActionLoading(true);
        try {
            const response = await apiClient.post('/api/passenger/request-refund', {
                transaction_id: selectedTrip.id,  // Usar el ID de la transacción directamente
                reason: refundReason
            });

            if (response.data.success) {
                showNotification({
                    type: 'success',
                    title: '✅ Solicitud enviada',
                    message: response.data.message
                });

                setShowRequestRefundModal(false);
                setSelectedTrip(null);
                setRefundReason('');
                await loadRefundRequests();
            }
        } catch (err) {
            const errorMessage = err.response?.data?.message || 'Error al enviar la solicitud';
            const errorStatus = err.response?.status;

            showNotification({
                type: errorStatus === 409 ? 'info' : 'error',
                title: errorStatus === 409 ? '✅ Solicitud ya registrada' : 'Error',
                message: errorStatus === 409 ? 'Tu solicitud de devolución ya fue enviada al chofer' : errorMessage
            });

            if (errorStatus === 409) {
                setShowRequestRefundModal(false);
                setSelectedTrip(null);
                setRefundReason('');
                await loadRefundRequests();
            }
        } finally {
            setRefundActionLoading(false);
        }
    };

    useEffect(() => {
        if (user) {
            loadRefundRequests();
            const interval = setInterval(loadRefundRequests, 10000); // Cada 10 segundos
            return () => clearInterval(interval);
        }
    }, [user]);

    const hasActiveRefundRequest = (transactionId) => {
        return refundRequests.some(req =>
            req.transaction_id === transactionId &&
            ['pending', 'verified'].includes(req.status)  // Solo activas, no completadas
        );
    };

    const getRefundRequestForTrip = (transactionId) => {
        return refundRequests.find(req =>
            req.transaction_id === transactionId &&
            ['pending', 'verified'].includes(req.status)  // Solo activas, no completadas
        );
    };

    const showNotification = (notif) => {
        console.log('🔔 [PASSENGER] showNotification llamado:', notif);
        setNotification(notif);
        setTimeout(() => {
            setNotification(null);
        }, 5000);
    };

    const downloadQR = () => {
        const svg = qrCodeRef.current.querySelector('svg');
        const svgData = new XMLSerializer().serializeToString(svg);
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = function() {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);
            const pngFile = canvas.toDataURL('image/png');

            const downloadLink = document.createElement('a');
            downloadLink.download = `qr-recarga-${user.primary_card_id}.png`;
            downloadLink.href = pngFile;
            downloadLink.click();
        };

        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    };

    if (loading && !user) {
        return (
            <div style={{
                minHeight: '100vh',
                background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontFamily: 'system-ui, -apple-system, sans-serif'
            }}>
                <div style={{ textAlign: 'center', color: 'white' }}>
                    <div style={{
                        width: '60px',
                        height: '60px',
                        border: '4px solid rgba(255,255,255,0.3)',
                        borderTop: '4px solid white',
                        borderRadius: '50%',
                        margin: '0 auto 20px',
                        animation: 'spin 1s linear infinite'
                    }}></div>
                    <p style={{ fontSize: '18px' }}>Cargando tu panel...</p>
                    <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div style={{
                minHeight: '100vh',
                background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '20px'
            }}>
                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '30px',
                    maxWidth: '400px',
                    textAlign: 'center',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.2)'
                }}>
                    <div style={{
                        width: '60px',
                        height: '60px',
                        background: '#fee2e2',
                        borderRadius: '50%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        margin: '0 auto 20px'
                    }}>
                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '30px', height: '30px', color: '#dc2626' }} viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                        </svg>
                    </div>
                    <h3 style={{ color: '#1e293b', marginBottom: '10px' }}>Error al cargar</h3>
                    <p style={{ color: '#64748b', marginBottom: '20px' }}>{error}</p>
                    <button
                        onClick={() => navigate('/login-passenger')}
                        style={{
                            padding: '10px 20px',
                            background: '#0891b2',
                            color: 'white',
                            border: 'none',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            fontWeight: '600'
                        }}
                    >
                        Volver al Login
                    </button>
                </div>
            </div>
        );
    }

    const renderMovimientosScreen = () => {
        return (
            <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
                <h2 style={{ color: 'white', fontSize: '24px', fontWeight: '700', marginBottom: '20px' }}>
                    Historial de Transacciones
                </h2>

                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '24px',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.1)'
                }}>
                    {transactions.length > 0 ? (
                        <div style={{ display: 'flex', flexDirection: 'column' }}>
                            {transactions.map((tx, index) => {
                                const isRefund = tx.type === 'refund';
                                const isReversal = tx.type === 'refund_reversal';
                                const isRecharge = tx.type === 'recharge';
                                const isFare = tx.type === 'fare';

                                return (
                                    <div
                                        key={tx.id}
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            padding: '16px',
                                            borderBottom: index < transactions.length - 1 ? '1px solid #e5e7eb' : 'none',
                                            transition: 'background 0.2s'
                                        }}
                                        onMouseEnter={(e) => e.currentTarget.style.background = '#f9fafb'}
                                        onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}
                                    >
                                        <div style={{ flex: 1 }}>
                                            <p style={{
                                                color: '#1e293b',
                                                fontSize: '15px',
                                                fontWeight: '600',
                                                margin: '0 0 4px 0',
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: '8px'
                                            }}>
                                                {isRefund && (
                                                    <>
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px', color: '#8b5cf6' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clipRule="evenodd" />
                                                        </svg>
                                                        Devolución
                                                    </>
                                                )}
                                                {isReversal && (
                                                    <>
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px', color: '#f97316' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                                        </svg>
                                                        Reversión de Devolución
                                                    </>
                                                )}
                                                {isRecharge && (
                                                    <>
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px', color: '#6366f1' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                                            <path fillRule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clipRule="evenodd" />
                                                        </svg>
                                                        Recarga de Saldo
                                                    </>
                                                )}
                                                {isFare && (
                                                    <>
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px', color: '#f59e0b' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
                                                        </svg>
                                                        Pago de Pasaje
                                                    </>
                                                )}
                                            </p>
                                            <p style={{
                                                color: '#6b7280',
                                                fontSize: '13px',
                                                margin: '0 0 4px 0'
                                            }}>
                                                {formatBoliviaDate(tx.created_at)}
                                            </p>
                                            {(isRefund || isReversal) && tx.description && (
                                                <p style={{
                                                    color: '#9ca3af',
                                                    fontSize: '12px',
                                                    margin: 0,
                                                    fontStyle: 'italic'
                                                }}>
                                                    {tx.description}
                                                </p>
                                            )}
                                        </div>
                                        <div style={{
                                            background: isReversal ? '#fff7ed' : (isRefund ? '#faf5ff' : (isRecharge ? '#ecfdf5' : '#fef3c7')),
                                            padding: '8px 16px',
                                            borderRadius: '8px',
                                            border: isReversal ? '1px solid #fed7aa' : (isRefund ? '1px solid #d8b4fe' : (isRecharge ? '1px solid #86efac' : '1px solid #fcd34d'))
                                        }}>
                                            <p style={{
                                                color: isReversal ? '#f97316' : (isRefund ? '#8b5cf6' : (isRecharge ? '#16a34a' : '#f59e0b')),
                                                fontSize: '17px',
                                                fontWeight: '700',
                                                margin: 0
                                            }}>
                                                {(() => {
                                                    const amount = parseFloat(tx.amount || 0);
                                                    const isFare = tx.type === 'fare';


                                                    if (isFare || isReversal) {
                                                        return `-${Math.abs(amount).toFixed(2)} Bs`;
                                                    } else if (isRecharge || isRefund) {
                                                        return `+${Math.abs(amount).toFixed(2)} Bs`;
                                                    } else {
                                                        return `${amount.toFixed(2)} Bs`;
                                                    }
                                                })()}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div style={{
                            textAlign: 'center',
                            padding: '40px 20px',
                            color: '#9ca3af'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '64px', height: '64px', margin: '0 auto 16px', opacity: 0.5 }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p style={{ fontSize: '16px', margin: 0 }}>No hay transacciones registradas</p>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderViajesScreen = () => {
        return (
            <div style={{ padding: '20px', maxWidth: '900px', margin: '0 auto' }}>
                <h2 style={{ color: 'white', fontSize: '24px', fontWeight: '700', marginBottom: '20px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '28px', height: '28px' }} viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                    </svg>
                    Historial Completo ({allTrips.length} viajes)
                </h2>

                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '24px',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.1)'
                }}>
                    {loadingAllTrips ? (
                        <div style={{
                            textAlign: 'center',
                            padding: '60px 20px',
                            color: '#6b7280'
                        }}>
                            <div style={{
                                width: '60px',
                                height: '60px',
                                border: '4px solid #e5e7eb',
                                borderTop: '4px solid #0891b2',
                                borderRadius: '50%',
                                margin: '0 auto 20px',
                                animation: 'spin 1s linear infinite'
                            }}></div>
                            <p style={{ fontSize: '16px', margin: 0 }}>Cargando historial completo...</p>
                            <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                        </div>
                    ) : allTrips.length > 0 ? (
                        <div style={{ display: 'flex', flexDirection: 'column' }}>
                            {allTrips.map((trip, index) => {
                                const refundRequest = getRefundRequestForTrip(trip.transaction_id);
                                const hasRequest = hasActiveRefundRequest(trip.transaction_id);

                                return (
                                    <div
                                        key={trip.id}
                                        style={{
                                            padding: '16px',
                                            borderBottom: index < allTrips.length - 1 ? '1px solid #e5e7eb' : 'none',
                                            transition: 'background 0.2s'
                                        }}
                                        onMouseEnter={(e) => e.currentTarget.style.background = '#f9fafb'}
                                        onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}
                                    >
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '12px' }}>
                                            <div style={{ flex: 1 }}>
                                                <p style={{
                                                    color: '#1e293b',
                                                    fontSize: '15px',
                                                    fontWeight: '600',
                                                    margin: '0 0 4px 0',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: '8px'
                                                }}>
                                                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '16px', height: '16px', color: '#0891b2' }} viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" />
                                                    </svg>
                                                    {trip.ruta?.nombre || 'Ruta no disponible'}
                                                </p>
                                                <p style={{ color: '#6b7280', fontSize: '12px', margin: '0 0 4px 0' }}>
                                                    {trip.ruta?.descripcion || 'Sin descripción'}
                                                </p>
                                                <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                                                    <p style={{ color: '#6b7280', fontSize: '12px', margin: 0, display: 'flex', alignItems: 'center', gap: '4px' }}>
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '14px', height: '14px' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                                        </svg>
                                                        {trip.driver_name || 'Chofer desconocido'}
                                                    </p>
                                                    <p style={{ color: '#6b7280', fontSize: '12px', margin: 0, display: 'flex', alignItems: 'center', gap: '4px' }}>
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '14px', height: '14px' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
                                                        </svg>
                                                        {trip.bus_plate || 'Sin placa'}
                                                    </p>
                                                    <p style={{ color: '#6b7280', fontSize: '12px', margin: 0 }}>
                                                        {formatBoliviaDate(trip.inicio, { month: 'short' })}
                                                    </p>
                                                </div>
                                            </div>
                                            <div style={{
                                                background: '#fee2e2',
                                                padding: '8px 16px',
                                                borderRadius: '8px',
                                                border: '1px solid #fecaca'
                                            }}>
                                                <p style={{
                                                    color: '#dc2626',
                                                    fontSize: '17px',
                                                    fontWeight: '700',
                                                    margin: 0
                                                }}>
                                                    -{parseFloat(trip.fare || 0).toFixed(2)} Bs
                                                </p>
                                            </div>
                                        </div>

                                        {hasRequest && refundRequest ? (
                                            <div style={{
                                                width: '100%',
                                                padding: '10px',
                                                background:
                                                    refundRequest.status === 'pending' ? 'linear-gradient(135deg, #fbbf24, #f59e0b)' :
                                                    'linear-gradient(135deg, #22c55e, #16a34a)',
                                                color: 'white',
                                                borderRadius: '8px',
                                                fontSize: '14px',
                                                fontWeight: '600',
                                                textAlign: 'center'
                                            }}>
                                                {refundRequest.status === 'pending' && '⏳ Solicitud pendiente de aprobación'}
                                                {refundRequest.status === 'verified' && '✅ Aprobado - Esperando devolución'}
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => {
                                                    if (trip.transaction_id) {
                                                        setSelectedTrip(trip);
                                                        setShowRequestRefundModal(true);
                                                    }
                                                }}
                                                disabled={!trip.transaction_id}
                                                style={{
                                                    width: '100%',
                                                    padding: '10px',
                                                    background: trip.transaction_id
                                                        ? 'linear-gradient(135deg, #ef4444, #dc2626)'
                                                        : '#e5e7eb',
                                                    color: trip.transaction_id ? 'white' : '#9ca3af',
                                                    border: 'none',
                                                    borderRadius: '8px',
                                                    fontSize: '14px',
                                                    fontWeight: '600',
                                                    cursor: !trip.transaction_id ? 'not-allowed' : 'pointer',
                                                    transition: 'all 0.3s',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    gap: '6px',
                                                    opacity: !trip.transaction_id ? 0.5 : 1
                                                }}
                                                onMouseEnter={(e) => {
                                                    if (trip.transaction_id) e.target.style.transform = 'translateY(-2px)';
                                                }}
                                                onMouseLeave={(e) => {
                                                    if (trip.transaction_id) e.target.style.transform = 'translateY(0)';
                                                }}
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '16px', height: '16px' }} viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clipRule="evenodd" />
                                                </svg>
                                                {!trip.transaction_id ? 'No disponible' : 'Solicitar Devolución'}
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div style={{
                            textAlign: 'center',
                            padding: '60px 20px',
                            color: '#9ca3af'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '64px', height: '64px', margin: '0 auto 16px', opacity: 0.5 }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p style={{ fontSize: '16px', margin: 0 }}>No hay viajes registrados</p>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderDevolucionesScreen = () => {
        const fareTransactions = transactions.filter(tx => tx.type === 'fare').slice(0, 8);

        const hasActiveRefundRequest = (transactionId) => {
            return refundRequests.some(req =>
                req.transaction_id === transactionId &&
                (req.status === 'pending' || req.status === 'verified' || req.status === 'completed')
            );
        };

        return (
            <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
                <h2 style={{ color: 'white', fontSize: '24px', fontWeight: '700', marginBottom: '20px' }}>
                    Solicitar Devolución
                </h2>

                {/* SECCIÓN 1: Últimos 8 pagos con botón de solicitar */}
                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '24px',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.1)',
                    marginBottom: '24px'
                }}>
                    <h3 style={{
                        fontSize: '18px',
                        fontWeight: '600',
                        marginBottom: '16px',
                        color: '#1e293b'
                    }}>
                        Últimos Pagos de Pasajes
                    </h3>

                    {fareTransactions.length === 0 ? (
                        <div style={{
                            textAlign: 'center',
                            padding: '40px 20px',
                            color: '#9ca3af'
                        }}>
                            <p style={{ fontSize: '16px', margin: 0 }}>No tienes pagos registrados</p>
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                            {fareTransactions.map(tx => {
                                const hasRefund = hasActiveRefundRequest(tx.id);

                                return (
                                    <div key={tx.id} style={{
                                        padding: '16px',
                                        background: '#f8fafc',
                                        borderRadius: '12px',
                                        border: '1px solid #e2e8f0'
                                    }}>
                                        {/* Información del pago */}
                                        <div style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            marginBottom: '12px'
                                        }}>
                                            <div style={{ flex: 1 }}>
                                                <p style={{ fontWeight: '600', margin: '0 0 4px 0', color: '#1e293b' }}>
                                                    {tx.description || 'Pago de pasaje'}
                                                </p>
                                                <p style={{ fontSize: '13px', color: '#64748b', margin: 0 }}>
                                                    {formatBoliviaDate(tx.created_at)}
                                                </p>
                                            </div>

                                            <span style={{
                                                fontWeight: '700',
                                                color: '#f59e0b',
                                                fontSize: '18px',
                                                marginLeft: '12px'
                                            }}>
                                                {parseFloat(tx.amount).toFixed(2)} Bs
                                            </span>
                                        </div>

                                        {/* Botón debajo */}
                                        {hasRefund ? (
                                            <div style={{
                                                padding: '10px',
                                                background: '#fef3c7',
                                                color: '#92400e',
                                                borderRadius: '8px',
                                                fontSize: '14px',
                                                fontWeight: '600',
                                                textAlign: 'center'
                                            }}>
                                                ✓ Devolución ya solicitada
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => {
                                                    setSelectedTrip(tx);
                                                    setShowRequestRefundModal(true);
                                                }}
                                                style={{
                                                    width: '100%',
                                                    padding: '12px 16px',
                                                    background: '#8b5cf6',
                                                    color: 'white',
                                                    border: 'none',
                                                    borderRadius: '8px',
                                                    cursor: 'pointer',
                                                    fontSize: '14px',
                                                    fontWeight: '600',
                                                    transition: 'background 0.2s'
                                                }}
                                                onMouseOver={(e) => e.target.style.background = '#7c3aed'}
                                                onMouseOut={(e) => e.target.style.background = '#8b5cf6'}
                                            >
                                                💰 Solicitar Devolución
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* SECCIÓN 2: Mis solicitudes de devolución */}
                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '24px',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.1)'
                }}>
                    <h3 style={{
                        fontSize: '18px',
                        fontWeight: '600',
                        marginBottom: '16px',
                        color: '#1e293b'
                    }}>
                        Mis Solicitudes de Devolución
                    </h3>

                    {refundRequests.length === 0 ? (
                        <div style={{
                            textAlign: 'center',
                            padding: '60px 20px',
                            color: '#9ca3af'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '64px', height: '64px', margin: '0 auto 16px', opacity: 0.5 }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p style={{ fontSize: '16px', margin: 0 }}>No tienes solicitudes de devolución</p>
                            <p style={{ fontSize: '14px', margin: '8px 0 0 0', color: '#64748b' }}>
                                Tus solicitudes de devolución aparecerán aquí
                            </p>
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                            {refundRequests.map(request => (
                                <div key={request.id} style={{
                                    background: request.needs_verification ? '#fef3c7' : '#f8fafc',
                                    borderRadius: '12px',
                                    padding: '20px',
                                    border: request.needs_verification ? '2px solid #fbbf24' : '2px solid #e2e8f0',
                                    transition: 'all 0.3s'
                                }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '16px' }}>
                                        <div style={{ flex: 1 }}>
                                            <p style={{ margin: '0 0 8px 0', color: '#1e293b', fontSize: '18px', fontWeight: '700' }}>
                                                Devolución: {request.amount} Bs
                                            </p>
                                            <p style={{ margin: '0 0 12px 0', color: '#475569', fontSize: '14px' }}>
                                                <strong>Motivo:</strong> {request.reason}
                                            </p>
                                            <p style={{ margin: '0 0 6px 0', color: '#6b7280', fontSize: '13px' }}>
                                                🚌 {request.trip_info.ruta} | Bus: {request.trip_info.bus_plate}
                                            </p>
                                            <p style={{ margin: '0 0 6px 0', color: '#6b7280', fontSize: '13px' }}>
                                                👤 Chofer: {request.driver_name}
                                            </p>
                                            <p style={{ margin: 0, color: '#6b7280', fontSize: '12px' }}>
                                                📅 {formatBoliviaDate(request.created_at)}
                                            </p>
                                        </div>
                                        <span style={{
                                            padding: '8px 16px',
                                            borderRadius: '999px',
                                            fontSize: '13px',
                                            fontWeight: '700',
                                            background:
                                                request.status === 'pending' ? '#fef3c7' :
                                                request.status === 'verified' ? '#d1fae5' :
                                                request.status === 'completed' ? '#dbeafe' :
                                                request.status === 'rejected' ? '#fee2e2' : '#f3f4f6',
                                            color:
                                                request.status === 'pending' ? '#92400e' :
                                                request.status === 'verified' ? '#065f46' :
                                                request.status === 'completed' ? '#1e40af' :
                                                request.status === 'rejected' ? '#991b1b' : '#6b7280'
                                        }}>
                                            {request.status === 'pending' ? 'Pendiente' :
                                             request.status === 'verified' ? 'Aprobado' :
                                             request.status === 'completed' ? 'Completado' :
                                             request.status === 'rejected' ? 'Rechazado' : request.status}
                                        </span>
                                    </div>

                                    {request.status === 'pending' && (
                                        <div style={{
                                            padding: '14px',
                                            background: '#fef3c7',
                                            borderRadius: '10px',
                                            borderLeft: '4px solid #fbbf24',
                                            color: '#92400e',
                                            fontSize: '14px',
                                            fontWeight: '500'
                                        }}>
                                            ⏳ Pendiente - Esperando respuesta del chofer
                                        </div>
                                    )}

                                    {request.status === 'completed' && (
                                        <div style={{
                                            padding: '14px',
                                            background: '#dbeafe',
                                            borderRadius: '10px',
                                            borderLeft: '4px solid #3b82f6',
                                            color: '#1e40af',
                                            fontSize: '14px',
                                            fontWeight: '500'
                                        }}>
                                            💰 Completado - El monto fue devuelto a tu tarjeta
                                        </div>
                                    )}

                                    {request.status === 'rejected' && request.driver_comments && (
                                        <div style={{
                                            padding: '14px',
                                            background: '#fee2e2',
                                            borderRadius: '10px',
                                            borderLeft: '4px solid #ef4444',
                                            color: '#991b1b',
                                            fontSize: '14px',
                                            fontWeight: '500'
                                        }}>
                                            ❌ Rechazado - {request.driver_comments}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderQuejasScreen = () => {
        return (
            <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
                <h2 style={{ color: 'white', fontSize: '24px', fontWeight: '700', marginBottom: '20px' }}>
                    Quejas y Reclamos
                </h2>

                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '20px',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                    marginBottom: '20px'
                }}>
                    <ComplaintsSection apiClient={apiClient} />
                </div>
            </div>
        );
    };

    return (
        <div style={{
            minHeight: '100vh',
            background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
            fontFamily: 'system-ui, -apple-system, sans-serif',
            padding: '0',
            paddingBottom: '80px'
        }}>
            {/* Header Tipo Yape */}
            <div style={{
                background: 'transparent',
                padding: '20px 20px 30px 20px',
                position: 'relative'
            }}>
                <div style={{
                    maxWidth: '800px',
                    margin: '0 auto',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center'
                }}>
                    <div>
                        <h2 style={{
                            color: 'white',
                            fontSize: '24px',
                            fontWeight: '600',
                            margin: 0
                        }}>Hola, {user?.name?.split(' ')[0] || 'Usuario'}</h2>
                    </div>
                    <button
                        onClick={() => {
                            if (activeTab !== 'inicio') {
                                setActiveTab('inicio');
                            } else {
                                handleLogout();
                            }
                        }}
                        style={{
                            width: '40px',
                            height: '40px',
                            background: 'rgba(255,255,255,0.2)',
                            color: 'white',
                            border: 'none',
                            borderRadius: '50%',
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            transition: 'all 0.3s'
                        }}
                        onMouseEnter={(e) => e.target.style.background = 'rgba(255,255,255,0.3)'}
                        onMouseLeave={(e) => e.target.style.background = 'rgba(255,255,255,0.2)'}
                    >
                        {activeTab !== 'inicio' ? (
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '20px', height: '20px' }} viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clipRule="evenodd" />
                            </svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '20px', height: '20px' }} viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clipRule="evenodd" />
                            </svg>
                        )}
                    </button>
                </div>
            </div>

            {/* Main Content - Renderiza diferentes pantallas según activeTab */}
            {activeTab === 'movimientos' && renderMovimientosScreen()}
            {activeTab === 'viajes' && renderViajesScreen()}
            {activeTab === 'devoluciones' && renderDevolucionesScreen()}
            {activeTab === 'quejas' && renderQuejasScreen()}

            {/* Pantalla de Inicio */}
            {activeTab === 'inicio' && (
            <div style={{
                maxWidth: '800px',
                margin: '-20px auto 0',
                padding: '0 16px'
            }}>
                {/* Balance Card Tipo Yape */}
                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '20px',
                    marginBottom: '16px',
                    boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
                        <h1 style={{
                            color: '#1e293b',
                            fontSize: '32px',
                            fontWeight: '700',
                            margin: '0'
                        }}>
                            {showBalance ? `Bs. ${user?.balance || '0.00'}` : 'Bs. ••••'}
                        </h1>
                        <button
                            onClick={() => setShowBalance(!showBalance)}
                            style={{
                                background: 'transparent',
                                border: 'none',
                                color: '#0891b2',
                                fontSize: '14px',
                                fontWeight: '600',
                                cursor: 'pointer',
                                textDecoration: 'underline'
                            }}
                        >
                            {showBalance ? 'Ocultar saldo' : 'Mostrar saldo'}
                        </button>
                    </div>

                    <div style={{ display: 'flex', gap: '12px', marginTop: '16px' }}>
                        <button
                            onClick={() => setShowQr(true)}
                            style={{
                                flex: 1,
                                padding: '12px',
                                background: '#f1f5f9',
                                border: 'none',
                                borderRadius: '12px',
                                cursor: 'pointer',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '8px',
                                transition: 'all 0.3s'
                            }}
                            onMouseEnter={(e) => e.target.style.background = '#e2e8f0'}
                            onMouseLeave={(e) => e.target.style.background = '#f1f5f9'}
                        >
                            <div style={{
                                width: '32px',
                                height: '32px',
                                background: 'linear-gradient(135deg, #0891b2, #06b6d4)',
                                borderRadius: '50%',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px' }} viewBox="0 0 20 20" fill="white">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <span style={{ color: '#1e293b', fontWeight: '600', fontSize: '14px' }}>Agregar dinero</span>
                        </button>

                        <button
                            onClick={() => {
                                setActiveTab('movimientos');
                                loadTransactions();
                            }}
                            style={{
                                flex: 1,
                                padding: '12px',
                                background: '#f1f5f9',
                                border: 'none',
                                borderRadius: '12px',
                                cursor: 'pointer',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: '8px',
                                transition: 'all 0.3s'
                            }}
                            onMouseEnter={(e) => e.target.style.background = '#e2e8f0'}
                            onMouseLeave={(e) => e.target.style.background = '#f1f5f9'}
                        >
                            <div style={{
                                width: '32px',
                                height: '32px',
                                background: 'linear-gradient(135deg, #0284c7, #0ea5e9)',
                                borderRadius: '50%',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                            }}>
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px' }} viewBox="0 0 20 20" fill="white">
                                    <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <span style={{ color: '#1e293b', fontWeight: '600', fontSize: '14px' }}>Movimientos</span>
                        </button>
                    </div>
                </div>

                {/* Action Cards Grid (4 opciones tipo Yape) */}
                <div style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(4, 1fr)',
                    gap: '12px',
                    marginBottom: '20px'
                }}>
                    <button
                        onClick={() => {
                            setShowFindLineView(true);
                            loadAvailableRoutes();
                            getUserLocation();
                        }}
                        style={{
                            background: 'white',
                            border: 'none',
                            borderRadius: '16px',
                            padding: '16px 8px',
                            cursor: 'pointer',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: '8px',
                            transition: 'all 0.3s',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
                        }}
                        onMouseEnter={(e) => e.target.style.transform = 'translateY(-2px)'}
                        onMouseLeave={(e) => e.target.style.transform = 'translateY(0)'}
                    >
                        <div style={{
                            width: '48px',
                            height: '48px',
                            background: 'linear-gradient(135deg, #10b981, #059669)',
                            borderRadius: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="white">
                                <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <span style={{ fontSize: '12px', color: '#1e293b', fontWeight: '600', textAlign: 'center' }}>Buscar Línea</span>
                    </button>

                    <button
                        onClick={() => {
                            setActiveTab('viajes');
                            loadAllTrips();
                        }}
                        style={{
                            background: 'white',
                            border: 'none',
                            borderRadius: '16px',
                            padding: '16px 8px',
                            cursor: 'pointer',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: '8px',
                            transition: 'all 0.3s',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                            position: 'relative'
                        }}
                        onMouseEnter={(e) => e.target.style.transform = 'translateY(-2px)'}
                        onMouseLeave={(e) => e.target.style.transform = 'translateY(0)'}
                    >
                        <div style={{
                            width: '48px',
                            height: '48px',
                            background: 'linear-gradient(135deg, #0284c7, #0ea5e9)',
                            borderRadius: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="white">
                                <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <span style={{ fontSize: '12px', color: '#1e293b', fontWeight: '600', textAlign: 'center' }}>Ver viajes</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('devoluciones')}
                        style={{
                            background: 'white',
                            border: 'none',
                            borderRadius: '16px',
                            padding: '16px 8px',
                            cursor: 'pointer',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: '8px',
                            transition: 'all 0.3s',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                            position: 'relative'
                        }}
                        onMouseEnter={(e) => e.target.style.transform = 'translateY(-2px)'}
                        onMouseLeave={(e) => e.target.style.transform = 'translateY(0)'}
                    >
                        {refundRequests.filter(r => r.needs_verification).length > 0 && (
                            <span style={{
                                position: 'absolute',
                                top: '8px',
                                right: '8px',
                                background: '#ef4444',
                                color: 'white',
                                fontSize: '10px',
                                fontWeight: '700',
                                padding: '2px 6px',
                                borderRadius: '999px',
                                minWidth: '18px',
                                textAlign: 'center'
                            }}>
                                {refundRequests.filter(r => r.needs_verification).length}
                            </span>
                        )}
                        <div style={{
                            width: '48px',
                            height: '48px',
                            background: 'linear-gradient(135deg, #14b8a6, #0d9488)',
                            borderRadius: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="white">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <span style={{ fontSize: '12px', color: '#1e293b', fontWeight: '600', textAlign: 'center' }}>Devoluciones</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('quejas')}
                        style={{
                            background: 'white',
                            border: 'none',
                            borderRadius: '16px',
                            padding: '16px 8px',
                            cursor: 'pointer',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            gap: '8px',
                            transition: 'all 0.3s',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.08)'
                        }}
                        onMouseEnter={(e) => e.target.style.transform = 'translateY(-2px)'}
                        onMouseLeave={(e) => e.target.style.transform = 'translateY(0)'}
                    >
                        <div style={{
                            width: '48px',
                            height: '48px',
                            background: 'linear-gradient(135deg, #f59e0b, #d97706)',
                            borderRadius: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="white">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <span style={{ fontSize: '12px', color: '#1e293b', fontWeight: '600', textAlign: 'center' }}>Quejas</span>
                    </button>
                </div>

                {/* Trips History */}
                <div style={{
                    background: 'white',
                    borderRadius: '16px',
                    padding: '24px',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.1)'
                }}>
                    <h3 style={{
                        color: '#1e293b',
                        fontSize: '18px',
                        fontWeight: '700',
                        margin: '0 0 20px 0',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '10px'
                    }}>
                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px', color: '#0891b2' }} viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
                        </svg>
                        Historial de Viajes
                    </h3>

                    {/* Filtros de tipo de viaje */}
                    <div style={{
                        display: 'flex',
                        gap: '8px',
                        marginBottom: '20px'
                    }}>
                        <button
                            onClick={() => setTipoViajeFilter('todos')}
                            style={{
                                flex: 1,
                                padding: '10px 16px',
                                background: tipoViajeFilter === 'todos' ? 'linear-gradient(135deg, #0891b2, #06b6d4)' : '#f1f5f9',
                                color: tipoViajeFilter === 'todos' ? 'white' : '#64748b',
                                border: 'none',
                                borderRadius: '8px',
                                fontSize: '14px',
                                fontWeight: '600',
                                cursor: 'pointer',
                                transition: 'all 0.2s'
                            }}
                        >
                            Todos
                        </button>
                        <button
                            onClick={() => setTipoViajeFilter('ida')}
                            style={{
                                flex: 1,
                                padding: '10px 16px',
                                background: tipoViajeFilter === 'ida' ? 'linear-gradient(135deg, #3b82f6, #2563eb)' : '#f1f5f9',
                                color: tipoViajeFilter === 'ida' ? 'white' : '#64748b',
                                border: 'none',
                                borderRadius: '8px',
                                fontSize: '14px',
                                fontWeight: '600',
                                cursor: 'pointer',
                                transition: 'all 0.2s'
                            }}
                        >
                            🔵 Ida
                        </button>
                        <button
                            onClick={() => setTipoViajeFilter('vuelta')}
                            style={{
                                flex: 1,
                                padding: '10px 16px',
                                background: tipoViajeFilter === 'vuelta' ? 'linear-gradient(135deg, #22c55e, #16a34a)' : '#f1f5f9',
                                color: tipoViajeFilter === 'vuelta' ? 'white' : '#64748b',
                                border: 'none',
                                borderRadius: '8px',
                                fontSize: '14px',
                                fontWeight: '600',
                                cursor: 'pointer',
                                transition: 'all 0.2s'
                            }}
                        >
                            🟢 Vuelta
                        </button>
                    </div>

                    {trips.length > 0 ? (
                        <div style={{ maxHeight: '400px', overflowY: 'auto' }}>
                            {trips.slice(0, 5).map((trip, index) => (
                                <div
                                    key={`trip-${trip.id}-${index}`}
                                    style={{
                                        padding: '16px',
                                        borderBottom: index < trips.length - 1 ? '1px solid #e5e7eb' : 'none',
                                        transition: 'background 0.2s'
                                    }}
                                    onMouseEnter={(e) => e.currentTarget.style.background = '#f9fafb'}
                                    onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}
                                >
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '12px' }}>
                                        <div style={{ flex: 1 }}>
                                            <p style={{
                                                color: '#1e293b',
                                                fontSize: '15px',
                                                fontWeight: '600',
                                                margin: '0 0 4px 0',
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: '8px'
                                            }}>
                                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px', color: '#0891b2' }} viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                                                </svg>
                                                {trip.ruta.nombre}{trip.ruta.descripcion ? ` - ${trip.ruta.descripcion}` : ''}
                                            </p>
                                            <p style={{
                                                color: '#6b7280',
                                                fontSize: '13px',
                                                margin: '4px 0'
                                            }}>
                                                🚌 Bus: {trip.bus_plate || trip.bus?.plate || 'N/A'} | 👤 Chofer: {trip.driver_name || trip.driver?.name || 'Desconocido'}
                                            </p>
                                            <p style={{
                                                color: '#6b7280',
                                                fontSize: '13px',
                                                margin: 0
                                            }}>
                                                📅 {formatBoliviaDate(trip.inicio || trip.fecha)}
                                            </p>
                                        </div>
                                        <div style={{
                                            background: '#fee2e2',
                                            padding: '8px 16px',
                                            borderRadius: '8px',
                                            border: '1px solid #fecaca'
                                        }}>
                                            <p style={{
                                                color: '#dc2626',
                                                fontSize: '17px',
                                                fontWeight: '700',
                                                margin: 0
                                            }}>
                                                -{parseFloat(trip.fare || 0).toFixed(2)} Bs
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div style={{
                            textAlign: 'center',
                            padding: '40px 20px',
                            color: '#9ca3af'
                        }}>
                            <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '64px', height: '64px', margin: '0 auto 16px', opacity: 0.5 }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p style={{ fontSize: '16px', margin: 0 }}>No hay viajes registrados</p>
                            <p style={{ fontSize: '14px', margin: '8px 0 0 0' }}>Tus viajes aparecerán aquí</p>
                        </div>
                    )}
                </div>

            </div>
            )}

            {/* Modal de Solicitar Devolución */}
            {showRequestRefundModal && selectedTrip && (
                <div style={{
                    position: 'fixed',
                    inset: 0,
                    background: 'rgba(0,0,0,0.5)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 1000,
                    padding: '20px'
                }}>
                    <div style={{
                        background: 'white',
                        borderRadius: '16px',
                        padding: '24px',
                        maxWidth: '500px',
                        width: '100%',
                        boxShadow: '0 20px 50px rgba(0,0,0,0.3)'
                    }}>
                        <h3 style={{
                            color: '#1e293b',
                            fontSize: '20px',
                            fontWeight: '700',
                            marginBottom: '16px'
                        }}>Solicitar Devolución</h3>

                        <div style={{
                            background: '#f8fafc',
                            borderRadius: '10px',
                            padding: '16px',
                            marginBottom: '20px'
                        }}>
                            <p style={{ margin: '0 0 4px 0', color: '#6b7280', fontSize: '13px' }}>Monto del viaje</p>
                            <p style={{ margin: '0 0 12px 0', color: '#dc2626', fontSize: '24px', fontWeight: '700' }}>
                                {parseFloat(selectedTrip.fare || 0).toFixed(2)} Bs
                            </p>
                            <p style={{ margin: '0 0 4px 0', color: '#6b7280', fontSize: '13px' }}>Ruta</p>
                            <p style={{ margin: '0 0 12px 0', color: '#1e293b', fontSize: '15px' }}>
                                {selectedTrip.ruta.nombre}{selectedTrip.ruta.descripcion ? ` - ${selectedTrip.ruta.descripcion}` : ''}
                            </p>
                            <p style={{ margin: '0 0 4px 0', color: '#6b7280', fontSize: '13px' }}>Chofer</p>
                            <p style={{ margin: '0 0 8px 0', color: '#1e293b', fontSize: '15px', fontWeight: '600' }}>
                                {selectedTrip.driver_name || selectedTrip.driver?.name || 'Desconocido'}
                            </p>
                            <p style={{ margin: '0 0 4px 0', color: '#6b7280', fontSize: '13px' }}>Fecha del viaje</p>
                            <p style={{ margin: 0, color: '#1e293b', fontSize: '14px' }}>
                                {formatBoliviaDate(selectedTrip.inicio || selectedTrip.fecha)}
                            </p>
                        </div>

                        <div style={{ marginBottom: '20px' }}>
                            <label style={{
                                display: 'block',
                                color: '#475569',
                                fontSize: '14px',
                                fontWeight: '600',
                                marginBottom: '8px'
                            }}>
                                Motivo de la devolución *
                            </label>
                            <textarea
                                value={refundReason}
                                onChange={(e) => setRefundReason(e.target.value)}
                                placeholder="Ej: Cobro duplicado, me bajé antes de lo previsto, etc."
                                rows="3"
                                style={{
                                    width: '100%',
                                    padding: '12px',
                                    border: '2px solid #e2e8f0',
                                    borderRadius: '10px',
                                    fontSize: '14px',
                                    resize: 'vertical',
                                    minHeight: '80px',
                                    maxHeight: '120px',
                                    boxSizing: 'border-box'
                                }}
                            />
                        </div>

                        <div style={{ display: 'flex', gap: '12px' }}>
                            <button
                                onClick={() => {
                                    setShowRequestRefundModal(false);
                                    setRefundReason('');
                                    setSelectedTrip(null);
                                }}
                                disabled={refundActionLoading}
                                style={{
                                    flex: 1,
                                    padding: '14px',
                                    background: '#6b7280',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '10px',
                                    fontSize: '16px',
                                    fontWeight: '600',
                                    cursor: refundActionLoading ? 'not-allowed' : 'pointer',
                                    opacity: refundActionLoading ? 0.5 : 1
                                }}
                            >
                                Cancelar
                            </button>
                            <button
                                onClick={handleRequestRefund}
                                disabled={refundActionLoading || !refundReason.trim()}
                                style={{
                                    flex: 1,
                                    padding: '14px',
                                    background: (refundActionLoading || !refundReason.trim()) ? '#94a3b8' : 'linear-gradient(135deg, #f59e0b, #d97706)',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '10px',
                                    fontSize: '16px',
                                    fontWeight: '600',
                                    cursor: (refundActionLoading || !refundReason.trim()) ? 'not-allowed' : 'pointer'
                                }}
                            >
                                {refundActionLoading ? 'Enviando...' : '📤 Enviar Solicitud'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* QR Modal */}
            {showQr && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    width: '100%',
                    height: '100%',
                    background: 'rgba(0,0,0,0.7)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 1000,
                    padding: '20px',
                    backdropFilter: 'blur(4px)'
                }}>
                    <div style={{
                        background: 'white',
                        padding: '40px',
                        borderRadius: '20px',
                        textAlign: 'center',
                        maxWidth: '400px',
                        width: '100%',
                        boxShadow: '0 20px 60px rgba(0,0,0,0.3)'
                    }}>
                        <h3 style={{
                            color: '#1e293b',
                            fontSize: '24px',
                            fontWeight: '700',
                            margin: '0 0 10px 0'
                        }}>
                            Código QR de Recarga
                        </h3>
                        <p style={{
                            color: '#64748b',
                            fontSize: '14px',
                            marginBottom: '30px'
                        }}>
                            Muestra este código en el punto de recarga
                        </p>

                        <div ref={qrCodeRef} style={{
                            background: '#f9fafb',
                            padding: '30px',
                            borderRadius: '16px',
                            marginBottom: '20px',
                            border: '2px dashed #0891b2',
                            display: 'flex',
                            justifyContent: 'center',
                            alignItems: 'center'
                        }}>
                            <img
                                src="/img/QR_Interflow.jpg"
                                alt="QR Code Interflow"
                                style={{
                                    width: '200px',
                                    height: '200px',
                                    objectFit: 'contain',
                                    display: 'block'
                                }}
                            />
                        </div>

                        <div style={{
                            background: '#f0f9ff',
                            padding: '12px',
                            borderRadius: '8px',
                            marginBottom: '20px',
                            border: '1px solid #bae6fd'
                        }}>
                            <p style={{
                                color: '#0369a1',
                                fontSize: '12px',
                                margin: '0 0 4px 0',
                                fontWeight: '500'
                            }}>
                                ID de Tarjeta
                            </p>
                            <p style={{
                                color: '#0c4a6e',
                                fontSize: '18px',
                                fontWeight: '700',
                                margin: 0,
                                fontFamily: 'monospace'
                            }}>
                                {user.primary_card_id}
                            </p>
                        </div>

                        <div style={{ display: 'flex', gap: '10px' }}>
                            <button
                                onClick={downloadQR}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: 'linear-gradient(135deg, #0891b2, #06b6d4)',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontSize: '14px',
                                    fontWeight: '600',
                                    cursor: 'pointer',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    gap: '6px'
                                }}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px' }} viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                                Descargar
                            </button>
                            <button
                                onClick={() => setShowQr(false)}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: '#f1f5f9',
                                    color: '#475569',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontSize: '14px',
                                    fontWeight: '600',
                                    cursor: 'pointer'
                                }}
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Notification Toast */}
            {notification && (
                <div style={{
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: notification.type === 'success'
                        ? 'linear-gradient(135deg, #10b981, #059669)'
                        : notification.type === 'info'
                        ? 'linear-gradient(135deg, #0891b2, #06b6d4)'
                        : 'linear-gradient(135deg, #ef4444, #dc2626)',
                    color: 'white',
                    padding: '16px 24px',
                    borderRadius: '12px',
                    boxShadow: '0 10px 30px rgba(0,0,0,0.3)',
                    zIndex: 9999,
                    minWidth: '300px',
                    maxWidth: '400px',
                    animation: 'slideInRight 0.3s ease-out',
                    display: 'flex',
                    alignItems: 'start',
                    gap: '12px'
                }}>
                    <div style={{
                        width: '24px',
                        height: '24px',
                        flexShrink: 0
                    }}>
                        {notification.type === 'success' ? (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                        ) : notification.type === 'info' ? (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                            </svg>
                        ) : (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                            </svg>
                        )}
                    </div>
                    <div style={{ flex: 1 }}>
                        <p style={{
                            fontSize: '16px',
                            fontWeight: '700',
                            margin: '0 0 4px 0'
                        }}>
                            {notification.title}
                        </p>
                        <p style={{
                            fontSize: '14px',
                            margin: 0,
                            opacity: 0.95
                        }}>
                            {notification.message}
                        </p>
                    </div>
                    <button
                        onClick={() => setNotification(null)}
                        style={{
                            background: 'rgba(255,255,255,0.2)',
                            border: 'none',
                            borderRadius: '6px',
                            width: '24px',
                            height: '24px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer',
                            color: 'white',
                            flexShrink: 0
                        }}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '14px', height: '14px' }} viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                    </button>
                    <style>{`
                        @keyframes slideInRight {
                            from {
                                transform: translateX(400px);
                                opacity: 0;
                            }
                            to {
                                transform: translateX(0);
                                opacity: 1;
                            }
                        }
                    `}</style>
                </div>
            )}

            {/* Vista Fullscreen: Buscar Línea */}
            {showFindLineView && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: '80px',
                    background: '#f8fafc',
                    zIndex: 999,
                    display: 'flex',
                    flexDirection: 'column'
                }}>
                    {/* Header */}
                    <div style={{
                        background: 'linear-gradient(135deg, #10b981, #059669)',
                        padding: '16px 20px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
                    }}>
                        <h2 style={{
                            fontSize: '20px',
                            fontWeight: '700',
                            color: 'white',
                            margin: 0
                        }}>
                            🚍 Buscar Línea
                        </h2>
                        <button
                            onClick={() => {
                                setShowFindLineView(false);
                                setSelectedRouteId('');
                                setNearbyBuses([]);
                            }}
                            style={{
                                background: 'rgba(255,255,255,0.2)',
                                border: 'none',
                                borderRadius: '50%',
                                width: '36px',
                                height: '36px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                cursor: 'pointer',
                                color: 'white'
                            }}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {/* Selector de Línea */}
                    <div style={{
                        padding: '16px 20px',
                        background: 'white',
                        borderBottom: '1px solid #e5e7eb'
                    }}>
                        <label style={{
                            display: 'block',
                            fontSize: '14px',
                            fontWeight: '600',
                            color: '#1e293b',
                            marginBottom: '8px'
                        }}>
                            Selecciona una línea:
                        </label>
                        <select
                            value={selectedRouteId}
                            onChange={(e) => {
                                setSelectedRouteId(e.target.value);
                                loadNearbyBuses(e.target.value);
                            }}
                            style={{
                                width: '100%',
                                padding: '12px',
                                borderRadius: '10px',
                                border: '2px solid #e5e7eb',
                                fontSize: '15px',
                                fontWeight: '500',
                                color: '#1e293b',
                                background: 'white',
                                cursor: 'pointer'
                            }}
                        >
                            <option value="">-- Selecciona una línea --</option>
                            {availableRoutes.map(route => (
                                <option key={route.id} value={route.id}>
                                    {route.nombre} - {route.descripcion}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Mapa con OpenStreetMap + Leaflet */}
                    <div style={{
                        flex: 1,
                        background: '#e5e7eb',
                        position: 'relative',
                        overflow: 'hidden'
                    }}>
                        {loadingLocation ? (
                            <div style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                height: '100%',
                                flexDirection: 'column',
                                gap: '16px'
                            }}>
                                <div style={{
                                    border: '4px solid #f3f4f6',
                                    borderTop: '4px solid #10b981',
                                    borderRadius: '50%',
                                    width: '50px',
                                    height: '50px',
                                    animation: 'spin 1s linear infinite'
                                }}></div>
                                <p style={{ color: '#64748b', fontSize: '15px' }}>Obteniendo tu ubicación...</p>
                            </div>
                        ) : userLocation && selectedRouteId ? (
                            <>
                                {/* Mapa real con Google Maps */}
                                <BusMapGoogle
                                    buses={nearbyBuses}
                                    userLocation={userLocation}
                                    center={userLocation}
                                    zoom={14}
                                    userRadius={2000}
                                    onBusClick={(bus) => {
                                        setSelectedBus(bus);
                                        setShowBusModal(true);
                                    }}
                                    selectedBusId={selectedBus?.bus_id}
                                    height="100%"
                                    showUserCircle={true}
                                />

                                {/* Mensaje flotante de estado */}
                                {loadingBuses && (
                                    <div style={{
                                        position: 'absolute',
                                        top: '70px',
                                        left: '50%',
                                        transform: 'translateX(-50%)',
                                        background: 'white',
                                        padding: '12px 20px',
                                        borderRadius: '8px',
                                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                                        zIndex: 1000,
                                        fontSize: '13px',
                                        color: '#10b981',
                                        fontWeight: '600',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px'
                                    }}>
                                        <div style={{
                                            border: '2px solid #f3f4f6',
                                            borderTop: '2px solid #10b981',
                                            borderRadius: '50%',
                                            width: '16px',
                                            height: '16px',
                                            animation: 'spin 1s linear infinite'
                                        }}></div>
                                        Buscando buses cercanos...
                                    </div>
                                )}

                                {!loadingBuses && nearbyBuses.length === 0 && (
                                    <div style={{
                                        position: 'absolute',
                                        top: '70px',
                                        left: '50%',
                                        transform: 'translateX(-50%)',
                                        background: 'white',
                                        padding: '12px 20px',
                                        borderRadius: '8px',
                                        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                                        zIndex: 1000,
                                        fontSize: '13px',
                                        color: '#f59e0b',
                                        fontWeight: '600'
                                    }}>
                                        No hay buses activos cercanos en esta línea
                                    </div>
                                )}
                            </>
                        ) : userLocation ? (
                            <div style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                height: '100%',
                                flexDirection: 'column',
                                gap: '12px',
                                padding: '20px'
                            }}>
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '48px', height: '48px', color: '#64748b' }} viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                                <p style={{ color: '#64748b', fontSize: '15px', textAlign: 'center' }}>
                                    Selecciona una línea para ver buses cercanos
                                </p>
                            </div>
                        ) : (
                            <div style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                height: '100%',
                                flexDirection: 'column',
                                gap: '12px',
                                padding: '20px'
                            }}>
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '48px', height: '48px', color: '#64748b' }} viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                                </svg>
                                <p style={{ color: '#64748b', fontSize: '15px', textAlign: 'center' }}>
                                    No se pudo obtener tu ubicación
                                </p>
                                <button
                                    onClick={getUserLocation}
                                    style={{
                                        padding: '10px 20px',
                                        background: '#10b981',
                                        color: 'white',
                                        border: 'none',
                                        borderRadius: '8px',
                                        fontSize: '14px',
                                        fontWeight: '600',
                                        cursor: 'pointer'
                                    }}
                                >
                                    Intentar de nuevo
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Bottom Navigation Bar tipo Yape - SIEMPRE VISIBLE */}
            <div style={{
                position: 'fixed',
                bottom: 0,
                left: 0,
                right: 0,
                background: 'white',
                borderTop: '1px solid #e5e7eb',
                padding: '8px 0',
                display: 'flex',
                justifyContent: 'space-around',
                alignItems: 'center',
                boxShadow: '0 -2px 10px rgba(0,0,0,0.1)',
                zIndex: 1000
            }}>
                {/* 1. Buscar Línea */}
                <button
                    onClick={() => {
                        setShowFindLineView(true);
                        loadAvailableRoutes();
                        getUserLocation();
                    }}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        cursor: 'pointer',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: '4px',
                        padding: '8px 12px',
                        transition: 'all 0.3s',
                        color: showFindLineView ? '#0891b2' : '#64748b'
                    }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                    </svg>
                    <span style={{ fontSize: '10px', fontWeight: showFindLineView ? '700' : '500' }}>Buscar</span>
                </button>

                {/* 2. Movimientos */}
                <button
                    onClick={() => {
                        setShowFindLineView(false);
                        setActiveTab('movimientos');
                        loadTransactions();
                    }}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        cursor: 'pointer',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: '4px',
                        padding: '8px 12px',
                        transition: 'all 0.3s',
                        color: activeTab === 'movimientos' && !showFindLineView ? '#0891b2' : '#64748b'
                    }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fillRule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clipRule="evenodd" />
                    </svg>
                    <span style={{ fontSize: '10px', fontWeight: activeTab === 'movimientos' && !showFindLineView ? '700' : '500' }}>Movimientos</span>
                </button>

                {/* 3. Inicio (centro) */}
                <button
                    onClick={() => {
                        setShowFindLineView(false);
                        setActiveTab('inicio');
                    }}
                    style={{
                        background: 'linear-gradient(135deg, #0891b2, #06b6d4)',
                        border: 'none',
                        cursor: 'pointer',
                        width: '56px',
                        height: '56px',
                        borderRadius: '50%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        boxShadow: '0 4px 12px rgba(8,145,178,0.4)',
                        marginTop: '-28px',
                        transition: 'all 0.3s'
                    }}
                    onMouseEnter={(e) => e.target.style.transform = 'scale(1.05)'}
                    onMouseLeave={(e) => e.target.style.transform = 'scale(1)'}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '28px', height: '28px' }} viewBox="0 0 20 20" fill="white">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                </button>

                {/* 4. Devoluciones */}
                <button
                    onClick={() => {
                        setShowFindLineView(false);
                        setActiveTab('devoluciones');
                    }}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        cursor: 'pointer',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: '4px',
                        padding: '8px 12px',
                        transition: 'all 0.3s',
                        color: activeTab === 'devoluciones' && !showFindLineView ? '#0891b2' : '#64748b',
                        position: 'relative'
                    }}
                >
                    {refundRequests.filter(r => r.needs_verification).length > 0 && (
                        <span style={{
                            position: 'absolute',
                            top: '4px',
                            right: '8px',
                            background: '#ef4444',
                            color: 'white',
                            fontSize: '10px',
                            fontWeight: '700',
                            padding: '2px 6px',
                            borderRadius: '999px',
                            minWidth: '16px',
                            textAlign: 'center'
                        }}>
                            {refundRequests.filter(r => r.needs_verification).length}
                        </span>
                    )}
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clipRule="evenodd" />
                    </svg>
                    <span style={{ fontSize: '10px', fontWeight: activeTab === 'devoluciones' && !showFindLineView ? '700' : '500' }}>Devoluciones</span>
                </button>

                {/* 5. Quejas */}
                <button
                    onClick={() => {
                        setShowFindLineView(false);
                        setActiveTab('quejas');
                    }}
                    style={{
                        background: 'transparent',
                        border: 'none',
                        cursor: 'pointer',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: '4px',
                        padding: '8px 12px',
                        transition: 'all 0.3s',
                        color: activeTab === 'quejas' && !showFindLineView ? '#0891b2' : '#64748b'
                    }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px' }} viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                    <span style={{ fontSize: '10px', fontWeight: activeTab === 'quejas' && !showFindLineView ? '700' : '500' }}>Quejas</span>
                </button>
            </div>

            {/* Modal de información del bus */}
            <BusInfoModal
                isOpen={showBusModal}
                onClose={() => {
                    setShowBusModal(false);
                    setSelectedBus(null);
                }}
                bus={selectedBus}
                userLocation={userLocation}
            />
        </div>
    );
}

export default PassengerDashboard;
