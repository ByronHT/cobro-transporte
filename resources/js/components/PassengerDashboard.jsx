import React, { useEffect, useState, useRef } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import ComplaintsSection from './ComplaintsSection';
import BusMapGoogle from './BusMapGoogle';
import BusInfoModal from './BusInfoModal';
import { API_BASE_URL, POLLING_INTERVAL } from '../config';

const apiClient = axios.create({ baseURL: API_BASE_URL });
apiClient.interceptors.request.use(config => {
    const token = localStorage.getItem('passenger_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
}, error => Promise.reject(error));

const formatBoliviaDate = (dateString, options = {}) => {
    if (!dateString) return 'Fecha no disponible';
    try {
        const date = new Date(dateString.endsWith('Z') ? dateString : dateString.replace(' ', 'T'));
        if (isNaN(date.getTime())) throw new Error('Invalid date');
        return date.toLocaleString('es-BO', { timeZone: 'America/La_Paz', ...options });
    } catch (error) {
        console.error('❌ Error formateando fecha:', dateString, error);
        return 'Fecha inválida';
    }
};

const ScreenHeader = ({ icon, title }) => (
    <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '24px' }}>
        <div style={{ width: '44px', height: '44px', background: 'rgba(255, 255, 255, 0.1)', borderRadius: '12px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '24px', color: 'white' }}>
            {icon}
        </div>
        <h2 style={{ color: 'white', fontSize: '24px', fontWeight: '700', margin: 0 }}>{title}</h2>
    </div>
);

function PassengerDashboard() {
    const navigate = useNavigate();
    const [user, setUser] = useState(null);
    const [trips, setTrips] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [showQr, setShowQr] = useState(false);
    const [showBalance, setShowBalance] = useState(true);
    const [transactions, setTransactions] = useState([]);
    const [notification, setNotification] = useState(null);
    const [lastEventId, setLastEventId] = useState(() => parseInt(sessionStorage.getItem('passenger_last_event_id') || '0'));
    const qrCodeRef = useRef(null);
    const [refundRequests, setRefundRequests] = useState([]);
    const [selectedTrip, setSelectedTrip] = useState(null);
    const [showRequestRefundModal, setShowRequestRefundModal] = useState(false);
    const [refundReason, setRefundReason] = useState('');
    const [refundActionLoading, setRefundActionLoading] = useState(false);
    const [allTrips, setAllTrips] = useState([]);
    const [loadingAllTrips, setLoadingAllTrips] = useState(false);
    const [activeTab, setActiveTab] = useState('inicio');
    // ... (resto de los estados sin cambios)

    // ... (todos los hooks y funciones de fetch se mantienen igual)
    
    // ... (funciones para renderizar las pantallas)
    const renderMovimientosScreen = () => (
        <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
            <ScreenHeader icon="💸" title="Historial de Movimientos" />
            <div style={{ background: 'white', borderRadius: '16px', boxShadow: '0 10px 30px rgba(0,0,0,0.1)' }}>
                {/* ... (lógica de la tabla de movimientos) ... */}
            </div>
        </div>
    );

    const renderViajesScreen = () => (
        <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
            <ScreenHeader icon="🚌" title="Historial de Viajes" />
            <div style={{ background: 'white', borderRadius: '16px', padding: '24px', boxShadow: '0 10px 30px rgba(0,0,0,0.1)' }}>
                {loadingAllTrips ? <p>Cargando viajes...</p> : allTrips.map(trip => (
                    <div key={trip.id} style={{ padding: '16px', borderBottom: '1px solid #e5e7eb' }}>
                        <p style={{ fontWeight: '600' }}>{trip.ruta?.nombre || 'Ruta desconocida'}</p>
                        <p style={{ fontSize: '14px', color: '#64748b' }}>{formatBoliviaDate(trip.inicio)}</p>
                        <p style={{ fontWeight: '700', color: '#dc2626' }}>-{parseFloat(trip.fare || 0).toFixed(2)} Bs</p>
                    </div>
                ))}
            </div>
        </div>
    );
    
    const renderDevolucionesScreen = () => (
        <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
            <ScreenHeader icon="↩️" title="Mis Devoluciones" />
            <div style={{ background: 'white', borderRadius: '16px', padding: '24px', boxShadow: '0 10px 30px rgba(0,0,0,0.1)' }}>
                 {refundRequests.map(request => (
                    <div key={request.id} style={{ padding: '16px', borderBottom: '1px solid #e5e7eb' }}>
                        <p>Devolución de {request.amount} Bs</p>
                        <p>Estado: {request.status}</p>
                        <p>Razón: {request.reason}</p>
                    </div>
                ))}
            </div>
        </div>
    );

    const renderQuejasScreen = () => (
         <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
            <ScreenHeader icon="🗣️" title="Quejas y Reclamos" />
            <div style={{ background: 'white', borderRadius: '16px', padding: '20px', boxShadow: '0 4px 12px rgba(0,0,0,0.1)' }}>
                <ComplaintsSection apiClient={apiClient} />
            </div>
        </div>
    );

    return (
        <div style={{ minHeight: '100vh', background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)', fontFamily: 'system-ui', padding: '0', paddingBottom: '80px' }}>
            {/* Header */}
            <div style={{ background: 'transparent', padding: '20px 20px 30px 20px' }}>
                <div style={{ maxWidth: '800px', margin: '0 auto', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <h2 style={{ color: 'white', fontSize: '24px', fontWeight: '600', margin: 0 }}>Hola, {user?.name?.split(' ')[0] || 'Usuario'}</h2>
                    </div>
                    <button onClick={() => activeTab !== 'inicio' ? setActiveTab('inicio') : handleLogout()} style={{ width: '40px', height: '40px', background: 'rgba(255,255,255,0.2)', color: 'white', border: 'none', borderRadius: '50%', cursor: 'pointer' }}>
                        {activeTab !== 'inicio' ? '←' : 'Salir'}
                    </button>
                </div>
            </div>

            {/* Main Content */}
            {activeTab === 'inicio' ? (
                <div style={{ maxWidth: '800px', margin: '-20px auto 0', padding: '0 16px' }}>
                    {/* ... (Contenido de la pantalla de inicio) ... */}
                </div>
            ) : activeTab === 'movimientos' ? renderMovimientosScreen()
              : activeTab === 'viajes' ? renderViajesScreen()
              : activeTab === 'devoluciones' ? renderDevolucionesScreen()
              : activeTab === 'quejas' ? renderQuejasScreen()
              : null}
            
            {/* ... (Barra de navegación inferior y modales) ... */}
        </div>
    );
}

export default PassengerDashboard;