import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import CameraButton from './CameraButton';
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
    const [isFinalizingTrip, setIsFinalizingTrip] = useState(false); // Para saber si es finalizar viaje o solo reporte

    const [searchCardUid, setSearchCardUid] = useState('');
    const [searchResults, setSearchResults] = useState(null);
    const [searchLoading, setSearchLoading] = useState(false);
    const [refundRequests, setRefundRequests] = useState([]);
    const [showRefundModal, setShowRefundModal] = useState(false);
    const [selectedTransaction, setSelectedTransaction] = useState(null);
    const [refundReason, setRefundReason] = useState('');

    const [showAllTransactions, setShowAllTransactions] = useState(false);
    const [allTransactions, setAllTransactions] = useState([]);
    const [loadingAllTransactions, setLoadingAllTransactions] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [filterType, setFilterType] = useState('all'); // all, fare, refund
    const [showRefundSection, setShowRefundSection] = useState(false);

    const [showFullHistoryView, setShowFullHistoryView] = useState(false);
    const [showRefundsView, setShowRefundsView] = useState(false);
    const [completedRefunds, setCompletedRefunds] = useState([]);
    const [loadingRefunds, setLoadingRefunds] = useState(false);
    const [reversalReason, setReversalReason] = useState('');
    const [selectedRefundForReversal, setSelectedRefundForReversal] = useState(null);

    const [turnoActivo, setTurnoActivo] = useState(null);
    const [busesDisponibles, setBusesDisponibles] = useState([]);
    const [selectedBusForTurno, setSelectedBusForTurno] = useState(null);
    const [showStartTurnoModal, setShowStartTurnoModal] = useState(false);
    const [showEndTurnoModal, setShowEndTurnoModal] = useState(false);
    const [horaFinProgramada, setHoraFinProgramada] = useState('18:00');
    const [turnoLoading, setTurnoLoading] = useState(false);

    const [tipoViaje, setTipoViaje] = useState('ida'); // 'ida' o 'vuelta'
    const [showStartTripModal, setShowStartTripModal] = useState(false);
    const [showEndTripModal, setShowEndTripModal] = useState(false);
    const [crearViajeVuelta, setCrearViajeVuelta] = useState(false);
    const [cambiarBus, setCambiarBus] = useState(false);
    const [nuevoBusId, setNuevoBusId] = useState(null);

    const gpsTracking = useGPSTracking({
        busId: busId,
        isTripActive: isTripActive,
        token: localStorage.getItem('driver_token'),
        apiBaseUrl: API_BASE_URL
    });

    useEffect(() => {
        let intervalId;
        let isMounted = true;

        const safeFetch = async () => {
            if (isMounted) {
                await fetchDriverData();
            }
        };

        safeFetch(); // Primera carga
        intervalId = setInterval(safeFetch, POLLING_INTERVAL); // 60 segundos para reducir recargas

        return () => {
            isMounted = false;
            clearInterval(intervalId);
        };
    }, []); // Sin dependencias externas para evitar recrear el efecto

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
        if (isFirstLoad) {
            setLoading(true);
        }
        if (isFirstLoad) {
            setError(null);
        }

        try {
            const statusResponse = await apiClient.get('/api/driver/current-trip-status');
            setDriverData(statusResponse.data);
            setIsTripActive(true);
            setBusId(statusResponse.data.trip.bus_id);
            setDriverId(statusResponse.data.trip.driver_id);

            const transactionsResponse = await apiClient.get('/api/driver/current-trip-transactions');
            const newTransactions = transactionsResponse.data;

            if (lastTransactionCount > 0 && newTransactions.length > lastTransactionCount) {
                const latestTransaction = newTransactions[0];
                showNotification({
                    type: 'success',
                    title: 'Pago realizado',
                    message: `${latestTransaction.passenger_name || 'Cliente'} - ${parseFloat(latestTransaction.amount).toFixed(2)} Bs`
                });
            }

            setTransactions(newTransactions);
            setLastTransactionCount(newTransactions.length);

            const eventsResponse = await apiClient.get(`/api/driver/current-trip-payment-events?last_event_id=${lastEventId}`);
            const newEvents = eventsResponse.data;

            console.log('🔔 [DRIVER] Eventos recibidos:', newEvents.length);
            console.log('🔔 [DRIVER] isInitialLoad:', isInitialLoadRef.current);
            console.log('🔔 [DRIVER] lastEventId:', lastEventId);

            const wasInitialLoad = isInitialLoadRef.current;
            if (isInitialLoadRef.current) {
                isInitialLoadRef.current = false;
                console.log('🔔 [DRIVER] Marcando isInitialLoad como false');
            }

            if (newEvents.length > 0) {
                console.log('🔔 [DRIVER] Procesando eventos. wasInitialLoad:', wasInitialLoad);
                if (!wasInitialLoad) {
                    console.log('🔔 [DRIVER] Mostrando notificaciones para', newEvents.length, 'eventos');
                    newEvents.forEach(event => {
                        if (notifiedEventsRef.current.has(event.id)) {
                            console.log('🔔 [DRIVER] Evento ya notificado, omitiendo:', event.id);
                            return; // Skip este evento
                        }

                        console.log('🔔 [DRIVER] Evento:', event.event_type, event.message);

                        notifiedEventsRef.current.add(event.id);

                        if (event.event_type === 'success') {
                            showNotification({
                                type: 'success',
                                title: '✅ Pago realizado',
                                message: `${event.passenger_name} - ${parseFloat(event.amount).toFixed(2)} Bs`
                            });
                        } else if (event.event_type === 'refund_requested') {
                            showNotification({
                                type: 'info',
                                title: '💰 Solicitud de devolución',
                                message: `${event.passenger_name} solicita devolver ${parseFloat(event.amount).toFixed(2)} Bs`
                            });
                        } else if (event.event_type === 'refund_approved') {
                            showNotification({
                                type: 'success',
                                title: '✅ Devolución realizada',
                                message: `Devolución aprobada: ${parseFloat(event.amount).toFixed(2)} Bs - ${event.passenger_name}`
                            });
                        } else if (event.event_type === 'refund_rejected') {
                            showNotification({
                                type: 'warning',
                                title: '❌ Devolución rechazada',
                                message: `Devolución rechazada: ${parseFloat(event.amount).toFixed(2)} Bs - ${event.passenger_name}`
                            });
                        } else if (event.event_type === 'insufficient_balance') {
                            showNotification({
                                type: 'warning',
                                title: '⚠️ Saldo insuficiente',
                                message: `${event.passenger_name} - Saldo: ${parseFloat(event.amount || 0).toFixed(2)} Bs / Requiere: ${parseFloat(event.required_amount).toFixed(2)} Bs`
                            });
                        } else if (event.event_type === 'invalid_card') {
                            showNotification({
                                type: 'error',
                                title: '❌ Tarjeta inválida',
                                message: `Tarjeta no registrada (UID: ${event.card_uid})`
                            });
                        } else if (event.event_type === 'inactive_card') {
                            showNotification({
                                type: 'error',
                                title: '❌ Tarjeta inactiva',
                                message: `${event.passenger_name} - Tarjeta bloqueada`
                            });
                        } else if (event.event_type === 'error') {
                            showNotification({
                                type: 'error',
                                title: '❌ Error del servidor',
                                message: 'No se pudo procesar el pago. Intente nuevamente.'
                            });
                        }
                    });
                }

                const maxId = Math.max(...newEvents.map(e => e.id));
                setLastEventId(maxId);
                sessionStorage.setItem(`driver_last_event_${sessionIdRef.current}`, maxId.toString());
            }
        } catch (err) {
            if (err.response && err.response.status === 404 && err.response.data.message === 'No hay viaje activo.') {
                setIsTripActive(false);
                setDriverData(null);
                setTransactions([]);
                setLastTransactionCount(0);

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
            } else if (err.response && err.response.status === 401) {
                const errorMessage = err.response.data?.message || '';

                if (errorMessage.toLowerCase().includes('unauthenticated') ||
                    errorMessage.toLowerCase().includes('token')) {
                    console.error('Token inválido. Cerrando sesión...');
                    localStorage.removeItem('driver_token');
                    localStorage.removeItem('driver_role');
                    localStorage.removeItem('driver_name');
                    sessionStorage.clear();
                    navigate('/login');
                } else {
                    console.warn('Error 401 temporal. Reintentando en siguiente ciclo...');
                }
            } else {
                if (isFirstLoad) {
                    setError('Error al cargar datos del chofer.');
                }
            }
        } finally {
            if (isFirstLoad) {
                setLoading(false);
            }
        }
    };

    const handleTripAction = async (action) => {
        setError(null);

        if (action === 'end') {
            setIsFinalizingTrip(true);
            setShowReportModal(true);
            return;
        }

        setIsActionLoading(true);
        try {
            const endpoint = '/api/driver/request-trip-start';

            if (!busId) {
                setError('Por favor, selecciona un bus para iniciar el viaje.');
                setIsActionLoading(false);
                return;
            }
            const payload = { bus_id: busId };

            await apiClient.post(endpoint, payload);

            setRefundRequests([]);
            setCompletedRefunds([]);

            await fetchDriverData();
        } catch (err) {
            const errorMessage = err.response?.data?.message || 'Error en la acción del viaje.';
            setError(errorMessage);
            console.error("Error en acción de viaje:", err);
        } finally {
            setIsActionLoading(false);
        }
    };

    const handleEndTripWithReport = async () => {
        setIsActionLoading(true);
        try {
            const payload = {
                bus_id: busId,
                reporte: tripReport || undefined  // Si está vacío, el backend usa el default
            };

            await apiClient.post('/api/driver/request-trip-end', payload);
            setShowReportModal(false);
            setTripReport('');
            setIsFinalizingTrip(false);
            await fetchDriverData();
            showNotification({
                type: 'success',
                title: 'Viaje finalizado',
                message: 'El viaje ha sido finalizado correctamente'
            });
        } catch (err) {
            const errorMessage = err.response?.data?.message || 'Error al finalizar el viaje.';
            setError(errorMessage);
            console.error("Error al finalizar viaje:", err);
        } finally {
            setIsActionLoading(false);
        }
    };

    const handleSaveReport = async () => {
        if (!tripReport.trim()) {
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'Por favor, escribe un reporte antes de guardar'
            });
            return;
        }

        setIsActionLoading(true);
        try {
            const formData = new FormData();
            formData.append('trip_id', driverData.trip.id);
            formData.append('reporte', tripReport);

            if (tripReportPhoto) {
                formData.append('photo', tripReportPhoto);
            }

            const response = await apiClient.post('/api/driver/update-trip-report', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            if (response.data.success) {
                setShowReportModal(false);
                setTripReport('');
                setTripReportPhoto(null);
                setIsFinalizingTrip(false);
                await fetchDriverData();
                showNotification({
                    type: 'success',
                    title: 'Reporte guardado',
                    message: 'El incidente ha sido registrado correctamente'
                });
            }
        } catch (err) {
            const errorMessage = err.response?.data?.message || 'Error al guardar el reporte.';
            setError(errorMessage);
            console.error("Error al guardar reporte:", err);
        } finally {
            setIsActionLoading(false);
        }
    };

    const loadRefundRequests = async () => {
        try {
            const response = await apiClient.get('/api/driver/refund-requests');
            if (response.data.success) {
                const requests = response.data.refund_requests;

                if (driverData?.trip?.id) {
                    const currentTripRequests = requests.filter(req => req.trip_id === driverData.trip.id);
                    setRefundRequests(currentTripRequests);
                } else {
                    setRefundRequests([]);
                }
            }
        } catch (err) {
            console.error('Error cargando solicitudes de devolución:', err);
        }
    };

    const loadAllTransactions = async () => {
        if (!driverData?.trip?.id) return;

        setLoadingAllTransactions(true);
        try {
            const response = await apiClient.get('/api/driver/current-trip-transactions');
            setAllTransactions(response.data || []);
        } catch (err) {
            console.error('Error loading all transactions:', err);
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'No se pudieron cargar todas las transacciones'
            });
        } finally {
            setLoadingAllTransactions(false);
        }
    };

    const loadCompletedRefunds = async () => {
        if (!driverData?.trip?.id) return;

        setLoadingRefunds(true);
        try {
            const response = await apiClient.get('/api/driver/refund-requests');
            if (response.data.success) {
                const completed = response.data.refund_requests.filter(
                    r => r.status === 'completed' && r.trip_id === driverData.trip.id
                );
                setCompletedRefunds(completed);
            }
        } catch (err) {
            console.error('Error loading completed refunds:', err);
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'No se pudieron cargar las devoluciones'
            });
        } finally {
            setLoadingRefunds(false);
        }
    };

    const handleReverseRefund = async () => {
        if (!selectedRefundForReversal) return;
        if (!reversalReason.trim() || reversalReason.trim().length < 10) {
            alert('Por favor ingresa un motivo de reversión (mínimo 10 caracteres)');
            return;
        }

        if (!confirm('¿Estás seguro de revertir esta devolución? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const response = await apiClient.post('/api/driver/reverse-refund', {
                refund_request_id: selectedRefundForReversal.id,
                reversal_reason: reversalReason
            });

            showNotification({
                type: 'success',
                title: 'Devolución revertida',
                message: response.data.message
            });

            await loadCompletedRefunds();
            await fetchDriverData();

            setSelectedRefundForReversal(null);
            setReversalReason('');
        } catch (err) {
            console.error('Error reversing refund:', err);
            showNotification({
                type: 'error',
                title: 'Error',
                message: err.response?.data?.error || 'No se pudo revertir la devolución'
            });
        }
    };

    const searchTransactionsByUid = async () => {
        if (!searchCardUid || !driverData) return;

        setSearchLoading(true);
        try {
            const response = await apiClient.get('/api/driver/search-transactions', {
                params: {
                    card_uid: searchCardUid,
                    trip_id: driverData.trip.id
                }
            });

            if (response.data.success) {
                setSearchResults(response.data);
                showNotification({
                    type: 'success',
                    title: 'Búsqueda exitosa',
                    message: `${response.data.transactions.length} transacciones encontradas`
                });
            }
        } catch (err) {
            console.error('Error buscando transacciones:', err);
            showNotification({
                type: 'error',
                title: 'Error de búsqueda',
                message: err.response?.data?.message || 'Error al buscar transacciones. Verifica el UID.'
            });
        } finally {
            setSearchLoading(false);
        }
    };

    const requestRefund = async () => {
        if (!selectedTransaction || !refundReason) {
            showNotification({
                type: 'warning',
                title: 'Datos incompletos',
                message: 'Debes escribir el motivo de la devolución'
            });
            return;
        }

        try {
            const response = await apiClient.post('/api/driver/refund-requests', {
                transaction_id: selectedTransaction.id,
                reason: refundReason
            });

            if (response.data.success) {
                showNotification({
                    type: 'success',
                    title: 'Solicitud enviada',
                    message: 'Se ha enviado un email al pasajero para verificación'
                });
                setShowRefundModal(false);
                setRefundReason('');
                setSelectedTransaction(null);
                setSearchResults(null);
                setSearchCardUid('');
                loadRefundRequests();
            }
        } catch (err) {
            console.error('Error creando solicitud:', err);
            showNotification({
                type: 'error',
                title: 'Error',
                message: err.response?.data?.message || 'Error al crear solicitud'
            });
        }
    };

    const approveOrRejectRefund = async (refundId, action, comments = '') => {
        const actionText = action === 'approve' ? 'aprobar' : 'rechazar';
        const confirmText = action === 'approve'
            ? '¿Confirmas aprobar esta devolución? El monto será devuelto al pasajero y descontado de tu balance.'
            : '¿Confirmas rechazar esta solicitud de devolución?';

        if (!confirm(confirmText)) return;

        try {
            const response = await apiClient.post(`/api/driver/approve-refund/${refundId}`, {
                action,
                comments
            });

            if (response.data.success) {
                showNotification({
                    type: 'success',
                    title: action === 'approve' ? '✅ Devolución aprobada' : '❌ Solicitud rechazada',
                    message: response.data.message
                });
                loadRefundRequests();
                fetchDriverData(); // Actualizar balance del chofer
            }
        } catch (err) {
            console.error(`Error al ${actionText} devolución:`, err);
            showNotification({
                type: 'error',
                title: 'Error',
                message: err.response?.data?.message || `Error al ${actionText} la solicitud`
            });
        }
    };

    useEffect(() => {
        if (isTripActive) {
            loadRefundRequests();
            const interval = setInterval(loadRefundRequests, 10000); // Cada 10 segundos
            return () => clearInterval(interval);
        }
    }, [isTripActive]);

    const handleLogout = () => {
        localStorage.removeItem('driver_token');
        localStorage.removeItem('driver_role');
        localStorage.removeItem('driver_name');
        sessionStorage.clear(); // Limpiar toda la sesión
        navigate('/login');
    };

    const showNotification = (notif) => {
        console.log('🔔 [DRIVER] showNotification llamado:', notif);
        setNotification(notif);
        setTimeout(() => {
            setNotification(null);
        }, 5000);
    };


    useEffect(() => {
        const fetchTurnoActivo = async () => {
            try {
                const response = await apiClient.get('/api/driver/turno/active');
                if (response.data.turno) {
                    setTurnoActivo(response.data.turno);
                }
            } catch (err) {
                if (err.response?.status !== 404) {
                    console.error('Error cargando turno activo:', err);
                }
            }
        };

        if (!loading) {
            fetchTurnoActivo();
        }
    }, [loading]);

    const fetchBusesDisponibles = async () => {
        try {
            const response = await apiClient.get('/api/driver/buses/disponibles');
            setBusesDisponibles(response.data.buses);
        } catch (err) {
            console.error('Error cargando buses disponibles:', err);
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'No se pudieron cargar los buses disponibles'
            });
        }
    };

    const handleIniciarTurno = async () => {
        if (!selectedBusForTurno) {
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'Debes seleccionar un bus'
            });
            return;
        }

        setTurnoLoading(true);
        try {
            const response = await apiClient.post('/api/driver/turno/start', {
                bus_id: selectedBusForTurno.id,
                hora_fin_programada: horaFinProgramada
            });

            setTurnoActivo(response.data.turno);
            setShowStartTurnoModal(false);
            setSelectedBusForTurno(null);

            showNotification({
                type: 'success',
                title: 'Turno Iniciado',
                message: `Turno iniciado con bus ${response.data.turno.busInicial.plate}`
            });
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Error al iniciar turno';
            showNotification({
                type: 'error',
                title: 'Error',
                message: errorMsg
            });
        } finally {
            setTurnoLoading(false);
        }
    };

    const handleFinalizarTurno = async () => {
        if (isTripActive) {
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'Debes finalizar el viaje actual antes de terminar el turno'
            });
            return;
        }

        setTurnoLoading(true);
        try {
            const response = await apiClient.post('/api/driver/turno/finish');

            showNotification({
                type: 'success',
                title: 'Turno Finalizado',
                message: `Total recaudado: ${response.data.turno.total_recaudado} Bs`
            });

            setTurnoActivo(null);
            setShowEndTurnoModal(false);
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Error al finalizar turno';
            showNotification({
                type: 'error',
                title: 'Error',
                message: errorMsg
            });
        } finally {
            setTurnoLoading(false);
        }
    };


    const handleIniciarViajeConTurno = async () => {
        if (!turnoActivo) {
            showNotification({
                type: 'error',
                title: 'Error',
                message: 'Debes iniciar un turno primero'
            });
            return;
        }

        setIsActionLoading(true);
        try {
            const busIdToUse = cambiarBus ? nuevoBusId : (busId || turnoActivo.bus_inicial_id);

            const response = await apiClient.post('/api/driver/trip/start-with-turno', {
                bus_id: busIdToUse,
                tipo_viaje: tipoViaje,
                cambio_bus: cambiarBus,
                nuevo_bus_id: cambiarBus ? nuevoBusId : null
            });

            setIsTripActive(true);
            setBusId(busIdToUse);
            setDriverData(response.data);
            setShowStartTripModal(false);
            setCambiarBus(false);
            setNuevoBusId(null);

            showNotification({
                type: 'success',
                title: 'Viaje Iniciado',
                message: `Viaje de ${tipoViaje.toUpperCase()} iniciado`
            });
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Error al iniciar viaje';
            showNotification({
                type: 'error',
                title: 'Error',
                message: errorMsg
            });
        } finally {
            setIsActionLoading(false);
        }
    };

    const handleFinalizarViajeConVuelta = async () => {
        setIsActionLoading(true);
        try {
            const response = await apiClient.post('/api/driver/trip/finish', {
                trip_id: driverData?.trip?.id,
                crear_viaje_vuelta: crearViajeVuelta
            });

            if (crearViajeVuelta && response.data.nuevo_viaje) {
                showNotification({
                    type: 'success',
                    title: 'Viaje Finalizado',
                    message: 'Viaje de vuelta creado automáticamente'
                });
                setIsTripActive(true);
                setDriverData({ trip: response.data.nuevo_viaje });
            } else {
                showNotification({
                    type: 'success',
                    title: 'Viaje Finalizado',
                    message: `Total recaudado: ${response.data.trip.total_recaudado || 0} Bs`
                });
                setIsTripActive(false);
                setDriverData(null);
            }

            setShowEndTripModal(false);
            setCrearViajeVuelta(false);
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Error al finalizar viaje';
            showNotification({
                type: 'error',
                title: 'Error',
                message: errorMsg
            });
        } finally {
            setIsActionLoading(false);
        }
    };

    if (loading && !driverData && !isTripActive && !driverId) {
        return (
            <div style={{
                minHeight: '100vh',
                background: 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)',
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
                    <p style={{ fontSize: '18px' }}>Cargando panel del chofer...</p>
                    <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                </div>
            </div>
        );
    }

    return (
        <div style={{
            minHeight: '100vh',
            background: 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)',
            fontFamily: 'system-ui, -apple-system, sans-serif',
            padding: '0'
        }}>
            <div style={{
                maxWidth: '800px',
                margin: '0 auto',
                padding: '20px'
            }}>
                        <div style={{
                            background: 'white',
                            borderRadius: '12px',
                            padding: '16px',
                            marginBottom: '16px',
                            boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                            border: '1px solid #e5e7eb'
                        }}>
                            {gpsTracking.isTracking && (
                                <div style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '6px',
                                    padding: '6px 10px',
                                    background: gpsTracking.error ? '#fef2f2' : '#ecfdf5',
                                    borderRadius: '6px',
                                    marginBottom: '12px',
                                    border: `1px solid ${gpsTracking.error ? '#fecaca' : '#a7f3d0'}`
                                }}>
                                    <div style={{
                                        width: '6px',
                                        height: '6px',
                                        background: gpsTracking.error ? '#ef4444' : '#10b981',
                                        borderRadius: '50%',
                                        animation: gpsTracking.error ? 'none' : 'pulse 2s infinite'
                                    }}></div>
                                    <span style={{
                                        fontSize: '11px',
                                        color: gpsTracking.error ? '#991b1b' : '#065f46',
                                        fontWeight: '500'
                                    }}>
                                        {gpsTracking.error ? (
                                            `Error GPS: ${gpsTracking.error}`
                                        ) : (
                                            <>
                                                GPS Activo • {gpsTracking.locationCount} ubicaciones enviadas
                                                {gpsTracking.lastLocation && ` • ${gpsTracking.isMoving ? '🚶 En movimiento' : '⏸️ Estacionado'}`}
                                            </>
                                        )}
                                    </span>
                                </div>
                            )}

                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' }}>
                                <div style={{
                                    background: 'linear-gradient(135deg, #059669, #10b981)',
                                    borderRadius: '8px',
                                    padding: '10px',
                                    textAlign: 'center'
                                }}>
                                    <p style={{ color: 'rgba(255,255,255,0.85)', fontSize: '10px', margin: '0 0 4px 0', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                        Viaje Actual
                                    </p>
                                    <p style={{ color: 'white', fontSize: '20px', fontWeight: '700', margin: 0 }}>
                                        {driverData.trip_earnings || '0.00'} Bs
                                    </p>
                                </div>
                                <div style={{
                                    background: 'linear-gradient(135deg, #1e3a8a, #3b82f6)',
                                    borderRadius: '8px',
                                    padding: '10px',
                                    textAlign: 'center'
                                }}>
                                    <p style={{ color: 'rgba(255,255,255,0.85)', fontSize: '10px', margin: '0 0 4px 0', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                                        Saldo Total
                                    </p>
                                    <p style={{ color: 'white', fontSize: '20px', fontWeight: '700', margin: 0 }}>
                                        {driverData.driver_balance} Bs
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px', marginBottom: '16px' }}>
                                <button
                                    onClick={() => {
                                        setShowFullHistoryView(true);
                                        loadAllTransactions();
                                    }}
                                    style={{
                                        width: '100%',
                                        padding: '12px',
                                        background: 'linear-gradient(135deg, #06b6d4, #0891b2)',
                                        color: 'white',
                                        border: 'none',
                                        borderRadius: '10px',
                                        fontSize: '14px',
                                        fontWeight: '700',
                                        cursor: 'pointer',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        gap: '8px',
                                        transition: 'all 0.3s',
                                        boxShadow: '0 4px 12px rgba(6, 182, 212, 0.3)'
                                    }}
                                    onMouseEnter={(e) => {
                                        e.target.style.transform = 'translateY(-2px)';
                                        e.target.style.boxShadow = '0 6px 16px rgba(6, 182, 212, 0.4)';
                                    }}
                                    onMouseLeave={(e) => {
                                        e.target.style.transform = 'translateY(0)';
                                        e.target.style.boxShadow = '0 4px 12px rgba(6, 182, 212, 0.3)';
                                    }}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px' }} viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                        <path fillRule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clipRule="evenodd" />
                                    </svg>
                                    Historial
                                </button>

                                <button
                                    onClick={() => {
                                        setShowRefundsView(true);
                                        loadCompletedRefunds();
                                    }}
                                    style={{
                                        width: '100%',
                                        padding: '12px',
                                        background: 'linear-gradient(135deg, #8b5cf6, #7c3aed)', // Morado que resalta
                                        color: 'white',
                                        border: 'none',
                                        borderRadius: '10px',
                                        fontSize: '14px',
                                        fontWeight: '700',
                                        cursor: 'pointer',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        gap: '8px',
                                        transition: 'all 0.3s',
                                        boxShadow: '0 4px 12px rgba(139, 92, 246, 0.3)'
                                    }}
                                    onMouseEnter={(e) => {
                                        e.target.style.transform = 'translateY(-2px)';
                                        e.target.style.boxShadow = '0 6px 16px rgba(139, 92, 246, 0.4)';
                                    }}
                                    onMouseLeave={(e) => {
                                        e.target.style.transform = 'translateY(0)';
                                        e.target.style.boxShadow = '0 4px 12px rgba(139, 92, 246, 0.3)';
                                    }}
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '18px', height: '18px' }} viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clipRule="evenodd" />
                                    </svg>
                                    Devoluciones
                                </button>
                        </div>

                        <div style={{
                            background: 'white',
                            borderRadius: '16px',
                            padding: '24px',
                            boxShadow: '0 10px 30px rgba(0,0,0,0.1)',
                            marginTop: '20px'
                        }}>
                            <div style={{
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center',
                                marginBottom: '20px'
                            }}>
                                <h3 style={{
                                    color: '#1e293b',
                                    fontSize: '18px',
                                    fontWeight: '700',
                                    margin: 0,
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '10px'
                                }}>
                                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '24px', height: '24px', color: '#f59e0b' }} viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clipRule="evenodd" />
                                    </svg>
                                    Sistema de Devoluciones
                                </h3>
                                <button
                                    onClick={() => setShowRefundSection(!showRefundSection)}
                                    style={{
                                        padding: '8px 16px',
                                        background: showRefundSection ? '#ef4444' : '#3b82f6',
                                        color: 'white',
                                        border: 'none',
                                        borderRadius: '8px',
                                        fontSize: '14px',
                                        fontWeight: '600',
                                        cursor: 'pointer',
                                        transition: 'all 0.3s',
                                        position: 'relative',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px'
                                    }}
                                >
                                    {showRefundSection ? 'Ocultar' : 'Mostrar'}
                                    {!showRefundSection && refundRequests.length > 0 && (
                                        <span style={{
                                            background: '#ef4444',
                                            color: 'white',
                                            fontSize: '11px',
                                            fontWeight: '700',
                                            padding: '2px 6px',
                                            borderRadius: '999px',
                                            minWidth: '18px',
                                            textAlign: 'center',
                                            border: '2px solid white'
                                        }}>
                                            {refundRequests.length}
                                        </span>
                                    )}
                                </button>
                            </div>

                            {showRefundSection && (
                                <>
                                    <div>
                                        <h4 style={{
                                            color: '#1e293b',
                                            fontSize: '16px',
                                            fontWeight: '600',
                                            marginBottom: '16px'
                                        }}>📋 Solicitudes de Devolución</h4>

                                        {refundRequests.length === 0 ? (
                                            <p style={{ color: '#6b7280', textAlign: 'center', padding: '20px' }}>
                                                No hay solicitudes de devolución
                                            </p>
                                        ) : (
                                            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                                                {refundRequests.map(request => (
                                                    <div key={request.id} style={{
                                                        background: '#f8fafc',
                                                        borderRadius: '12px',
                                                        padding: '16px',
                                                        border: '2px solid #e2e8f0'
                                                    }}>
                                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '12px' }}>
                                                            <div>
                                                                <p style={{ margin: '0 0 4px 0', color: '#1e293b', fontSize: '16px', fontWeight: '600' }}>
                                                                    {request.passenger_name} - {request.amount} Bs
                                                                </p>
                                                                <p style={{ margin: '0 0 8px 0', color: '#6b7280', fontSize: '14px' }}>
                                                                    {request.reason}
                                                                </p>
                                                                <p style={{ margin: 0, color: '#6b7280', fontSize: '12px' }}>
                                                                    Creado: {request.created_at}
                                                                </p>
                                                            </div>
                                                            <span style={{
                                                                padding: '6px 12px',
                                                                borderRadius: '999px',
                                                                fontSize: '12px',
                                                                fontWeight: '600',
                                                                background:
                                                                    request.status === 'pending' ? '#fef3c7' :
                                                                    request.status === 'verified' ? '#d1fae5' :
                                                                    request.status === 'completed' ? '#dbeafe' : '#fee2e2',
                                                                color:
                                                                    request.status === 'pending' ? '#92400e' :
                                                                    request.status === 'verified' ? '#065f46' :
                                                                    request.status === 'completed' ? '#1e40af' : '#991b1b'
                                                            }}>
                                                                {request.status}
                                                            </span>
                                                        </div>

                                                        {request.status === 'pending' && (
                                                            <>
                                                                <p style={{
                                                                    margin: '12px 0',
                                                                    padding: '12px',
                                                                    background: '#fef3c7',
                                                                    borderRadius: '8px',
                                                                    color: '#92400e',
                                                                    fontSize: '13px'
                                                                }}>
                                                                    ⏳ Solicitud del pasajero - Revisa y decide
                                                                </p>
                                                                <div style={{ display: 'flex', gap: '12px' }}>
                                                                    <button
                                                                        onClick={() => approveOrRejectRefund(request.id, 'approve')}
                                                                        style={{
                                                                            flex: 1,
                                                                            padding: '12px',
                                                                            background: 'linear-gradient(135deg, #22c55e, #16a34a)',
                                                                            color: 'white',
                                                                            border: 'none',
                                                                            borderRadius: '10px',
                                                                            fontSize: '15px',
                                                                            fontWeight: '600',
                                                                            cursor: 'pointer'
                                                                        }}
                                                                    >
                                                                        ✅ Aprobar
                                                                    </button>
                                                                    <button
                                                                        onClick={() => approveOrRejectRefund(request.id, 'reject')}
                                                                        style={{
                                                                            flex: 1,
                                                                            padding: '12px',
                                                                            background: '#ef4444',
                                                                            color: 'white',
                                                                            border: 'none',
                                                                            borderRadius: '10px',
                                                                            fontSize: '15px',
                                                                            fontWeight: '600',
                                                                            cursor: 'pointer'
                                                                        }}
                                                                    >
                                                                        ❌ Rechazar
                                                                    </button>
                                                                </div>
                                                            </>
                                                        )}

                                                        {request.status === 'completed' && (
                                                            <p style={{
                                                                margin: '12px 0 0 0',
                                                                padding: '12px',
                                                                background: '#dbeafe',
                                                                borderRadius: '8px',
                                                                color: '#1e40af',
                                                                fontSize: '13px'
                                                            }}>
                                                                ✅ Devolución completada
                                                            </p>
                                                        )}

                                                        {request.status === 'rejected' && (
                                                            <p style={{
                                                                margin: '12px 0 0 0',
                                                                padding: '12px',
                                                                background: '#fee2e2',
                                                                borderRadius: '8px',
                                                                color: '#991b1b',
                                                                fontSize: '13px'
                                                            }}>
                                                                ❌ Solicitud rechazada
                                                            </p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    </>
                )}
            </div>

            {notification && (
                <div style={{
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: notification.type === 'success'
                        ? 'linear-gradient(135deg, #10b981, #059669)'
                        : notification.type === 'warning'
                        ? 'linear-gradient(135deg, #f59e0b, #d97706)'
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
                        ) : notification.type === 'warning' ? (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
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

                        <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            marginBottom: '24px',
                            paddingBottom: '16px',
                            borderBottom: '2px solid #e5e7eb'
                        }}>
                            <h3 style={{
                                color: '#1e293b',
                                fontSize: '24px',
                                fontWeight: '700',
                                margin: 0,
                                display: 'flex',
                                alignItems: 'center',
                                gap: '10px'
                            }}>
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '28px', height: '28px', color: '#3b82f6' }} viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fillRule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clipRule="evenodd" />
                                </svg>
                                Historial Completo ({(() => {
                                    let filtered = allTransactions;
                                    if (filterType !== 'all') {
                                        filtered = filtered.filter(tx => tx.type === filterType);
                                    }
                                    if (searchQuery.trim()) {
                                        filtered = filtered.filter(tx =>
                                            tx.passenger_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
                                            tx.description?.toLowerCase().includes(searchQuery.toLowerCase())
                                        );
                                    }
                                    return filtered.length;
                                })()})
                            </h3>
                            <button
                                onClick={() => {
                                    setShowAllTransactions(false);
                                    setSearchQuery('');
                                    setFilterType('all');
                                }}
                                style={{
                                    background: '#f1f5f9',
                                    border: 'none',
                                    borderRadius: '8px',
                                    width: '36px',
                                    height: '36px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    cursor: 'pointer',
                                    color: '#64748b',
                                    transition: 'all 0.2s'
                                }}
                                onMouseEnter={(e) => {
                                    e.target.style.background = '#e2e8f0';
                                    e.target.style.color = '#1e293b';
                                }}
                                onMouseLeave={(e) => {
                                    e.target.style.background = '#f1f5f9';
                                    e.target.style.color = '#64748b';
                                }}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '20px', height: '20px' }} viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </button>
                        </div>

                            <div style={{ flex: '1 1 300px', position: 'relative' }}>
                                <input
                                    type="text"
                                    placeholder="Buscar por pasajero o descripción..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    style={{
                                        width: '100%',
                                        padding: '12px 12px 12px 40px',
                                        border: '2px solid #e5e7eb',
                                        borderRadius: '10px',
                                        fontSize: '14px',
                                        outline: 'none',
                                        transition: 'all 0.3s',
                                        boxSizing: 'border-box'
                                    }}
                                    onFocus={(e) => e.target.style.borderColor = '#3b82f6'}
                                    onBlur={(e) => e.target.style.borderColor = '#e5e7eb'}
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" style={{
                                    position: 'absolute',
                                    left: '12px',
                                    top: '50%',
                                    transform: 'translateY(-50%)',
                                    width: '18px',
                                    height: '18px',
                                    color: '#9ca3af'
                                }} viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                                </svg>
                            </div>

                        {loadingAllTransactions ? (
                            <div style={{
                                textAlign: 'center',
                                padding: '60px 20px',
                                color: '#6b7280'
                            }}>
                                <div style={{
                                    width: '60px',
                                    height: '60px',
                                    border: '4px solid #e5e7eb',
                                    borderTop: '4px solid #3b82f6',
                                    borderRadius: '50%',
                                    margin: '0 auto 20px',
                                    animation: 'spin 1s linear infinite'
                                }}></div>
                                <p style={{ fontSize: '16px', margin: 0 }}>Cargando historial completo...</p>
                            </div>
                        ) : (
                            <div style={{ flex: 1, overflowY: 'auto' }}>
                                {(() => {
                                    let filtered = allTransactions;

                                    if (filterType !== 'all') {
                                        filtered = filtered.filter(tx => tx.type === filterType);
                                    }

                                    if (searchQuery.trim()) {
                                        filtered = filtered.filter(tx =>
                                            tx.passenger_name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
                                            tx.description?.toLowerCase().includes(searchQuery.toLowerCase())
                                        );
                                    }

                                    if (filtered.length === 0) {
                                        return (
                                            <div style={{
                                                textAlign: 'center',
                                                padding: '60px 20px',
                                                color: '#9ca3af'
                                            }}>
                                                <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '64px', height: '64px', margin: '0 auto 16px', opacity: 0.5 }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p style={{ fontSize: '16px', margin: 0 }}>
                                                    {searchQuery.trim() ? 'No se encontraron resultados' : 'No hay transacciones registradas'}
                                                </p>
                                            </div>
                                        );
                                    }

                                    return filtered.map((tx, index) => (
                                        <div
                                            key={tx.id}
                                            style={{
                                                display: 'flex',
                                                justifyContent: 'space-between',
                                                alignItems: 'center',
                                                padding: '16px',
                                                borderBottom: index < filtered.length - 1 ? '1px solid #e5e7eb' : 'none',
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
                                                    {tx.type === 'refund' ? (
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '16px', height: '16px', color: '#dc2626' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clipRule="evenodd" />
                                                        </svg>
                                                    ) : (
                                                        <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '16px', height: '16px', color: '#3b82f6' }} viewBox="0 0 20 20" fill="currentColor">
                                                            <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                                                        </svg>
                                                    )}
                                                    {tx.type === 'refund' ? 'Devolución - ' : ''}{tx.passenger_name || 'Cliente'}
                                                </p>
                                                <p style={{
                                                    color: '#6b7280',
                                                    fontSize: '12px',
                                                    margin: '0 0 2px 0'
                                                }}>
                                                    {tx.description}
                                                </p>
                                                <p style={{
                                                    color: '#6b7280',
                                                    fontSize: '12px',
                                                    margin: 0
                                                }}>
                                                    {new Date(tx.created_at).toLocaleString('es-BO')}
                                                </p>
                                            </div>
                                            <div style={{
                                                background: tx.type === 'refund' ? '#fee2e2' : (tx.type === 'refund_reversal' ? '#fff7ed' : '#ecfdf5'),
                                                padding: '8px 16px',
                                                borderRadius: '8px',
                                                border: tx.type === 'refund' ? '1px solid #fca5a5' : (tx.type === 'refund_reversal' ? '1px solid #fed7aa' : '1px solid #86efac')
                                            }}>
                                                <p style={{
                                                    color: tx.type === 'refund' ? '#dc2626' : (tx.type === 'refund_reversal' ? '#f97316' : '#16a34a'),
                                                    fontSize: '17px',
                                                    fontWeight: '700',
                                                    margin: 0
                                                }}>
                                                    {(() => {
                                                        const amount = parseFloat(tx.amount);
                                                        const isFare = tx.type === 'fare';
                                                        const isRefund = tx.type === 'refund';
                                                        const isReversal = tx.type === 'refund_reversal';


                                                        if (isFare || isReversal) {
                                                            return `+${Math.abs(amount).toFixed(2)} Bs`;
                                                        } else if (isRefund) {
                                                            return `-${Math.abs(amount).toFixed(2)} Bs`;
                                                        } else {
                                                            return `${amount.toFixed(2)} Bs`;
                                                        }
                                                    })()}
                                                </p>
                                            </div>
                                        </div>
                                    ));
                                })()}
                            </div>
                        )}
                    </div>
                </div>
            )}

                    <div style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        marginBottom: '20px'
                    }}>
                        <h2 style={{
                            fontSize: '24px',
                            fontWeight: '700',
                            color: 'white',
                            margin: 0
                        }}>
                            Historial Completo
                        </h2>
                        <button
                            onClick={() => setShowFullHistoryView(false)}
                            style={{
                                background: 'rgba(255,255,255,0.2)',
                                border: 'none',
                                borderRadius: '50%',
                                width: '40px',
                                height: '40px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                cursor: 'pointer',
                                color: 'white'
                            }}
                        >
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                                            <div style={{
                                                width: '44px',
                                                height: '44px',
                                                borderRadius: '12px',
                                                background: isReversal ? 'linear-gradient(135deg, #f97316, #ea580c)' : isRefund ? 'linear-gradient(135deg, #8b5cf6, #7c3aed)' : 'linear-gradient(135deg, #10b981, #059669)',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                marginRight: '14px',
                                                flexShrink: 0
                                            }}>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2">
                                                    {isReversal ? (
                                                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                                    ) : isRefund ? (
                                                        <path d="M9 14l-5-5 5-5M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/>
                                                    ) : (
                                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                                    )}
                                                </svg>
                                            </div>

                                            <div style={{ textAlign: 'right', marginLeft: '12px' }}>
                                                <p style={{
                                                    color: isReversal ? '#f97316' : isRefund ? '#8b5cf6' : '#10b981',
                                                    fontSize: '17px',
                                                    fontWeight: '700',
                                                    margin: 0
                                                }}>
                                                    {(() => {
                                                        const amount = parseFloat(tx.amount);
                                                        const isFare = tx.type === 'fare';


                                                        if (isFare || isReversal) {
                                                            return `+${Math.abs(amount).toFixed(2)} Bs`;
                                                        } else if (isRefund) {
                                                            return `-${Math.abs(amount).toFixed(2)} Bs`;
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
                        )}
                    </div>
                </div>
            )}

                    <div style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        marginBottom: '20px'
                    }}>
                        <h2 style={{
                            fontSize: '24px',
                            fontWeight: '700',
                            color: 'white',
                            margin: 0
                        }}>
                            Devoluciones
                        </h2>
                        <button
                            onClick={() => {
                                setShowRefundsView(false);
                                setSelectedRefundForReversal(null);
                                setReversalReason('');
                            }}
                            style={{
                                background: 'rgba(255,255,255,0.2)',
                                border: 'none',
                                borderRadius: '50%',
                                width: '40px',
                                height: '40px',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                cursor: 'pointer',
                                color: 'white'
                            }}
                        >
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                                        <div style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            marginBottom: '12px'
                                        }}>
                                            <div>
                                                <p style={{
                                                    fontSize: '16px',
                                                    fontWeight: '700',
                                                    color: '#1e293b',
                                                    margin: '0 0 4px 0'
                                                }}>
                                                    {refund.passenger?.name || 'Pasajero'}
                                                </p>
                                                <p style={{
                                                    fontSize: '13px',
                                                    color: '#64748b',
                                                    margin: 0
                                                }}>
                                                    {new Date(refund.created_at).toLocaleString('es-BO', {
                                                        day: '2-digit',
                                                        month: 'short',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </p>
                                            </div>
                                            <p style={{
                                                fontSize: '20px',
                                                fontWeight: '700',
                                                color: refund.is_reversed ? '#f59e0b' : '#8b5cf6',
                                                margin: 0
                                            }}>
                                                {parseFloat(refund.amount).toFixed(2)} Bs
                                            </p>
                                        </div>

                                        {refund.is_reversed && (
                                            <div style={{
                                                background: '#fff7ed',
                                                borderRadius: '8px',
                                                padding: '12px',
                                                border: '1px solid #fed7aa',
                                                marginBottom: '12px'
                                            }}>
                                                <p style={{
                                                    fontSize: '13px',
                                                    color: '#f59e0b',
                                                    margin: '0 0 6px 0',
                                                    fontWeight: '700',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: '6px'
                                                }}>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                                    </svg>
                                                    REVERTIDA
                                                </p>
                                                <p style={{
                                                    fontSize: '12px',
                                                    color: '#92400e',
                                                    margin: '0 0 8px 0'
                                                }}>
                                                    {new Date(refund.reversed_at).toLocaleString('es-BO')}
                                                </p>
                                                <p style={{
                                                    fontSize: '13px',
                                                    color: '#78350f',
                                                    margin: 0
                                                }}>
                                                    {refund.reversal_reason}
                                                </p>
                                            </div>
                                        )}

                    {selectedRefundForReversal && (
                        <div style={{
                            position: 'fixed',
                            top: 0,
                            left: 0,
                            right: 0,
                            bottom: 0,
                            background: 'rgba(0,0,0,0.6)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            padding: '20px',
                            zIndex: 10000
                        }}>
                            <div style={{
                                background: 'white',
                                borderRadius: '16px',
                                padding: '24px',
                                maxWidth: '500px',
                                width: '100%',
                                boxShadow: '0 20px 60px rgba(0,0,0,0.3)'
                            }}>
                                <h3 style={{
                                    fontSize: '20px',
                                    fontWeight: '700',
                                    color: '#1e293b',
                                    margin: '0 0 16px 0'
                                }}>
                                    Revertir Devolución
                                </h3>

                                <div style={{ marginBottom: '20px' }}>
                                    <label style={{
                                        display: 'block',
                                        fontSize: '14px',
                                        fontWeight: '600',
                                        color: '#334155',
                                        marginBottom: '8px'
                                    }}>
                                        Motivo de la reversión (mínimo 10 caracteres):
                                    </label>
                                    <textarea
                                        value={reversalReason}
                                        onChange={(e) => setReversalReason(e.target.value)}
                                        placeholder="Ej: Error en el cobro, devolución incorrecta..."
                                        rows={4}
                                        style={{
                                            width: '100%',
                                            padding: '12px',
                                            fontSize: '14px',
                                            border: '2px solid #e2e8f0',
                                            borderRadius: '8px',
                                            outline: 'none',
                                            resize: 'vertical',
                                            boxSizing: 'border-box',
                                            fontFamily: 'inherit'
                                        }}
                                    />
                                    <p style={{
                                        fontSize: '12px',
                                        color: reversalReason.trim().length < 10 ? '#dc2626' : '#10b981',
                                        margin: '6px 0 0 0'
                                    }}>
                                        {reversalReason.trim().length}/10 caracteres mínimos
                                    </p>
                                </div>

            {showStartTurnoModal && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0,0,0,0.7)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 1000,
                    padding: '20px'
                }}>
                    <div style={{
                        background: 'white',
                        borderRadius: '16px',
                        padding: '30px',
                        maxWidth: '500px',
                        width: '100%'
                    }}>
                        <h3 style={{ fontSize: '22px', fontWeight: '700', marginBottom: '20px' }}>
                            🚀 Iniciar Turno
                        </h3>

                        <label style={{ display: 'block', marginBottom: '10px', fontWeight: '600' }}>
                            Selecciona un Bus:
                        </label>
                        <select
                            value={selectedBusForTurno?.id || ''}
                            onChange={(e) => {
                                const bus = busesDisponibles.find(b => b.id === parseInt(e.target.value));
                                setSelectedBusForTurno(bus);
                            }}
                            style={{
                                width: '100%',
                                padding: '12px',
                                border: '2px solid #e2e8f0',
                                borderRadius: '8px',
                                marginBottom: '16px',
                                fontSize: '15px'
                            }}
                        >
                            <option value="">-- Selecciona un bus --</option>
                            {busesDisponibles.map(bus => (
                                <option key={bus.id} value={bus.id}>
                                    {bus.plate} - {bus.ruta?.nombre || 'Sin ruta'}
                                </option>
                            ))}
                        </select>

                        <label style={{ display: 'block', marginBottom: '10px', fontWeight: '600' }}>
                            Hora de Fin Programada:
                        </label>
                        <input
                            type="time"
                            value={horaFinProgramada}
                            onChange={(e) => setHoraFinProgramada(e.target.value)}
                            style={{
                                width: '100%',
                                padding: '12px',
                                border: '2px solid #e2e8f0',
                                borderRadius: '8px',
                                marginBottom: '20px',
                                fontSize: '15px'
                            }}
                        />

                        <div style={{ display: 'flex', gap: '12px' }}>
                            <button
                                onClick={() => setShowStartTurnoModal(false)}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: '#f3f4f6',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontWeight: '600',
                                    cursor: 'pointer'
                                }}
                            >
                                Cancelar
                            </button>
                            <button
                                onClick={handleIniciarTurno}
                                disabled={!selectedBusForTurno || turnoLoading}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontWeight: '600',
                                    cursor: 'pointer',
                                    opacity: (!selectedBusForTurno || turnoLoading) ? 0.5 : 1
                                }}
                            >
                                {turnoLoading ? 'Iniciando...' : 'Iniciar Turno'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {showStartTripModal && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0,0,0,0.7)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 1000,
                    padding: '20px'
                }}>
                    <div style={{
                        background: 'white',
                        borderRadius: '16px',
                        padding: '30px',
                        maxWidth: '500px',
                        width: '100%'
                    }}>
                        <h3 style={{ fontSize: '22px', fontWeight: '700', marginBottom: '20px' }}>
                            Iniciar Viaje {tipoViaje === 'ida' ? 'de IDA 🔵' : 'de VUELTA 🟢'}
                        </h3>

                        <label style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px',
                            marginBottom: '16px',
                            padding: '12px',
                            background: '#f3f4f6',
                            borderRadius: '8px',
                            cursor: 'pointer'
                        }}>
                            <input
                                type="checkbox"
                                checked={cambiarBus}
                                onChange={(e) => setCambiarBus(e.target.checked)}
                                style={{ width: '18px', height: '18px' }}
                            />
                            <span style={{ fontWeight: '600' }}>Cambiar de bus</span>
                        </label>

                        {cambiarBus && (
                            <>
                                <label style={{ display: 'block', marginBottom: '10px', fontWeight: '600' }}>
                                    Nuevo Bus:
                                </label>
                                <select
                                    value={nuevoBusId || ''}
                                    onChange={(e) => setNuevoBusId(parseInt(e.target.value))}
                                    style={{
                                        width: '100%',
                                        padding: '12px',
                                        border: '2px solid #e2e8f0',
                                        borderRadius: '8px',
                                        marginBottom: '16px',
                                        fontSize: '15px'
                                    }}
                                >
                                    <option value="">-- Selecciona un bus --</option>
                                    {busesDisponibles.map(bus => (
                                        <option key={bus.id} value={bus.id}>
                                            {bus.plate} - {bus.ruta?.nombre || 'Sin ruta'}
                                        </option>
                                    ))}
                                </select>
                            </>
                        )}

                        <div style={{ display: 'flex', gap: '12px' }}>
                            <button
                                onClick={() => { setShowStartTripModal(false); setCambiarBus(false); setNuevoBusId(null); }}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: '#f3f4f6',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontWeight: '600',
                                    cursor: 'pointer'
                                }}
                            >
                                Cancelar
                            </button>
                            <button
                                onClick={handleIniciarViajeConTurno}
                                disabled={isActionLoading || (cambiarBus && !nuevoBusId)}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: 'linear-gradient(135deg, #22c55e, #16a34a)',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '8px',
                                    fontWeight: '600',
                                    cursor: 'pointer',
                                    opacity: (isActionLoading || (cambiarBus && !nuevoBusId)) ? 0.5 : 1
                                }}
                            >
                                {isActionLoading ? 'Iniciando...' : 'Iniciar Viaje'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

