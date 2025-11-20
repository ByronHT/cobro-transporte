import React, { useState, useEffect } from 'react';
import axios from 'axios';
import CameraButton from './CameraButton';
import FullscreenView from './FullscreenView';

const ComplaintsSection = ({ apiClient }) => {
    const [complaints, setComplaints] = useState([]);
    const [showNewComplaintModal, setShowNewComplaintModal] = useState(false);

    // Estados para el nuevo flujo
    const [routes, setRoutes] = useState([]);
    const [drivers, setDrivers] = useState([]);
    const [selectedRoute, setSelectedRoute] = useState('');
    const [selectedDriver, setSelectedDriver] = useState('');
    const [complaintReason, setComplaintReason] = useState('');
    const [complaintPhoto, setComplaintPhoto] = useState(null);
    const [loading, setLoading] = useState(false);
    const [loadingDrivers, setLoadingDrivers] = useState(false);

    useEffect(() => {
        loadComplaints();
    }, []);

    // Cargar choferes cuando se selecciona una ruta
    useEffect(() => {
        if (selectedRoute) {
            loadDriversByRoute(selectedRoute);
        } else {
            setDrivers([]);
            setSelectedDriver('');
        }
    }, [selectedRoute]);

    const loadComplaints = async () => {
        try {
            const response = await apiClient.get('/api/passenger/my-complaints');
            setComplaints(response.data);
        } catch (error) {
            console.error('Error al cargar quejas:', error);
        }
    };

    const loadRoutes = async () => {
        try {
            setLoading(true);
            const response = await apiClient.get('/api/passenger/routes');
            setRoutes(response.data);
        } catch (error) {
            console.error('Error al cargar rutas:', error);
            alert('Error al cargar las líneas disponibles');
        } finally {
            setLoading(false);
        }
    };

    const loadDriversByRoute = async (routeId) => {
        try {
            setLoadingDrivers(true);
            const response = await apiClient.get(`/api/passenger/drivers-by-route/${routeId}`);
            setDrivers(response.data);

            if (response.data.length === 0) {
                alert('No hay choferes registrados en esta línea');
            }
        } catch (error) {
            console.error('Error al cargar choferes:', error);
            alert('Error al cargar los choferes de esta línea');
            setDrivers([]);
        } finally {
            setLoadingDrivers(false);
        }
    };

    const handleNewComplaint = async () => {
        setShowNewComplaintModal(true);
        await loadRoutes();
    };

    const handleSubmitComplaint = async () => {
        // Validaciones
        if (!selectedRoute) {
            alert('Por favor selecciona una línea');
            return;
        }

        if (!selectedDriver) {
            alert('Por favor selecciona un chofer');
            return;
        }

        if (!complaintReason.trim() || complaintReason.trim().length < 10) {
            alert('Por favor escribe un motivo de queja (mínimo 10 caracteres)');
            return;
        }

        try {
            setLoading(true);

            const formData = new FormData();
            formData.append('driver_id', selectedDriver);
            formData.append('ruta_id', selectedRoute);
            formData.append('reason', complaintReason);

            if (complaintPhoto) {
                formData.append('photo', complaintPhoto);
            }

            await apiClient.post('/api/passenger/complaints', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            alert('Queja registrada exitosamente');

            // Limpiar formulario y cerrar modal
            setShowNewComplaintModal(false);
            setSelectedRoute('');
            setSelectedDriver('');
            setComplaintReason('');
            setComplaintPhoto(null);
            setDrivers([]);

            // Recargar quejas
            loadComplaints();
        } catch (error) {
            console.error('Error al enviar queja:', error);
            alert(error.response?.data?.error || 'Error al enviar la queja');
        } finally {
            setLoading(false);
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'pending':
                return { bg: '#fef3c7', text: '#92400e', label: 'Pendiente' };
            case 'reviewed':
                return { bg: '#d1fae5', text: '#065f46', label: 'Atendida' };
            default:
                return { bg: '#f3f4f6', text: '#6b7280', label: status };
        }
    };

    const resetForm = () => {
        setShowNewComplaintModal(false);
        setSelectedRoute('');
        setSelectedDriver('');
        setComplaintReason('');
        setComplaintPhoto(null);
        setDrivers([]);
    };

    return (
        <div>
            <button
                onClick={handleNewComplaint}
                style={{
                    width: '100%',
                    padding: '14px',
                    background: '#10b981',
                    color: 'white',
                    border: 'none',
                    borderRadius: '10px',
                    fontSize: '15px',
                    fontWeight: '600',
                    cursor: 'pointer',
                    marginBottom: '20px',
                    transition: 'all 0.2s',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '8px'
                }}
            >
                <span style={{ fontSize: '18px' }}>➕</span>
                Nueva Queja
            </button>

            {complaints.length === 0 ? (
                <div style={{
                    textAlign: 'center',
                    padding: '40px 20px',
                    color: '#9ca3af'
                }}>
                    <svg xmlns="http://www.w3.org/2000/svg" style={{ width: '64px', height: '64px', margin: '0 auto 16px', opacity: 0.5 }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p style={{ fontSize: '16px', margin: 0 }}>No has registrado quejas aún</p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                    {complaints.map(complaint => {
                        const statusInfo = getStatusColor(complaint.status);
                        return (
                            <div key={complaint.id} style={{
                                background: '#f8fafc',
                                borderRadius: '12px',
                                padding: '16px',
                                border: '2px solid #e2e8f0'
                            }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '12px' }}>
                                    <div style={{ flex: 1 }}>
                                        <p style={{ margin: '0 0 8px 0', color: '#1e293b', fontSize: '16px', fontWeight: '600' }}>
                                            Queja contra: {complaint.driver_name}
                                        </p>
                                        <p style={{ margin: '0 0 8px 0', color: '#6b7280', fontSize: '14px' }}>
                                            <strong>Motivo:</strong> {complaint.reason}
                                        </p>
                                        <p style={{ margin: '0 0 4px 0', color: '#6b7280', fontSize: '13px' }}>
                                            🚌 {complaint.ruta_descripcion} | Bus: {complaint.bus_placa}
                                        </p>
                                        <p style={{ margin: '0 0 4px 0', color: '#6b7280', fontSize: '12px' }}>
                                            📅 {new Date(complaint.created_at).toLocaleString('es-ES')}
                                        </p>
                                        {complaint.photo_path && (
                                            <p style={{ margin: '4px 0 0 0', color: '#6b7280', fontSize: '12px' }}>
                                                📷 <a href={complaint.photo_path} target="_blank" rel="noopener noreferrer" style={{ color: '#3b82f6' }}>Ver foto adjunta</a>
                                            </p>
                                        )}
                                        {complaint.admin_response && (
                                            <div style={{
                                                marginTop: '12px',
                                                padding: '12px',
                                                background: '#f0f9ff',
                                                borderRadius: '8px',
                                                borderLeft: '4px solid #3b82f6'
                                            }}>
                                                <p style={{ margin: '0 0 4px 0', color: '#1e40af', fontSize: '12px', fontWeight: '600' }}>
                                                    Respuesta del administrador:
                                                </p>
                                                <p style={{ margin: 0, color: '#1e293b', fontSize: '13px' }}>
                                                    {complaint.admin_response}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                    <span style={{
                                        padding: '6px 12px',
                                        borderRadius: '999px',
                                        fontSize: '12px',
                                        fontWeight: '600',
                                        background: statusInfo.bg,
                                        color: statusInfo.text,
                                        marginLeft: '12px'
                                    }}>
                        {statusInfo.label}
                                    </span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Modal Nueva Queja - Fullscreen */}
            {showNewComplaintModal && (
                <FullscreenView
                    title="Nueva Queja"
                    onClose={resetForm}
                    headerColor="linear-gradient(135deg, #ef4444, #dc2626)"
                >
                    <div style={{ padding: '20px' }}>
                        <p style={{ color: '#6b7280', fontSize: '14px', marginBottom: '24px' }}>
                            Selecciona la línea donde tuviste el problema y luego el chofer correspondiente.
                        </p>

                        {/* Selector de Línea */}
                        <div style={{ marginBottom: '20px' }}>
                            <label style={{
                                display: 'block',
                                color: '#475569',
                                fontSize: '14px',
                                fontWeight: '600',
                                marginBottom: '8px'
                            }}>
                                Línea / Ruta *
                            </label>
                            <select
                                value={selectedRoute}
                                onChange={(e) => setSelectedRoute(e.target.value)}
                                disabled={loading}
                                style={{
                                    width: '100%',
                                    padding: '12px',
                                    border: '2px solid #e2e8f0',
                                    borderRadius: '10px',
                                    fontSize: '14px',
                                    boxSizing: 'border-box',
                                    cursor: loading ? 'not-allowed' : 'pointer',
                                    background: loading ? '#f8fafc' : 'white'
                                }}
                            >
                                <option value="">-- Selecciona una línea --</option>
                                {routes.map(route => (
                                    <option key={route.id} value={route.id}>
                                        {route.nombre} - {route.descripcion}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Selector de Chofer */}
                        <div style={{ marginBottom: '20px' }}>
                            <label style={{
                                display: 'block',
                                color: '#475569',
                                fontSize: '14px',
                                fontWeight: '600',
                                marginBottom: '8px'
                            }}>
                                Chofer *
                            </label>
                            <select
                                value={selectedDriver}
                                onChange={(e) => setSelectedDriver(e.target.value)}
                                disabled={!selectedRoute || loadingDrivers}
                                style={{
                                    width: '100%',
                                    padding: '12px',
                                    border: '2px solid #e2e8f0',
                                    borderRadius: '10px',
                                    fontSize: '14px',
                                    boxSizing: 'border-box',
                                    cursor: (!selectedRoute || loadingDrivers) ? 'not-allowed' : 'pointer',
                                    background: (!selectedRoute || loadingDrivers) ? '#f8fafc' : 'white'
                                }}
                            >
                                <option value="">
                                    {loadingDrivers ? 'Cargando choferes...' :
                                     !selectedRoute ? 'Primero selecciona una línea' :
                                     '-- Selecciona un chofer --'}
                                </option>
                                {drivers.map(driver => (
                                    <option key={driver.id} value={driver.id}>
                                        {driver.name}
                                    </option>
                                ))}
                            </select>
                            {selectedRoute && drivers.length === 0 && !loadingDrivers && (
                                <p style={{ margin: '8px 0 0 0', color: '#ef4444', fontSize: '12px' }}>
                                    No hay choferes disponibles en esta línea
                                </p>
                            )}
                        </div>

                        {/* Motivo de la queja */}
                        <div style={{ marginBottom: '20px' }}>
                            <label style={{
                                display: 'block',
                                color: '#475569',
                                fontSize: '14px',
                                fontWeight: '600',
                                marginBottom: '8px'
                            }}>
                                Motivo de la queja *
                            </label>
                            <textarea
                                value={complaintReason}
                                onChange={(e) => setComplaintReason(e.target.value)}
                                placeholder="Describe el problema con el servicio del chofer (mínimo 10 caracteres)..."
                                rows="4"
                                disabled={!selectedDriver}
                                style={{
                                    width: '100%',
                                    padding: '12px',
                                    border: '2px solid #e2e8f0',
                                    borderRadius: '10px',
                                    fontSize: '14px',
                                    resize: 'vertical',
                                    minHeight: '100px',
                                    boxSizing: 'border-box',
                                    cursor: !selectedDriver ? 'not-allowed' : 'text',
                                    background: !selectedDriver ? '#f8fafc' : 'white'
                                }}
                            />
                            <p style={{ margin: '4px 0 0 0', color: '#6b7280', fontSize: '12px' }}>
                                {complaintReason.length}/1000 caracteres
                            </p>
                        </div>

                        {/* Foto opcional */}
                        <div style={{ marginBottom: '24px' }}>
                            <label style={{
                                display: 'block',
                                color: '#475569',
                                fontSize: '14px',
                                fontWeight: '600',
                                marginBottom: '8px'
                            }}>
                                Foto (opcional)
                            </label>
                            <CameraButton
                                onPhotoTaken={setComplaintPhoto}
                                label="Tomar Foto de la Queja"
                                disabled={!selectedDriver}
                            />
                        </div>

                        {/* Botones */}
                        <div style={{ display: 'flex', gap: '12px' }}>
                            <button
                                onClick={resetForm}
                                disabled={loading}
                                style={{
                                    flex: 1,
                                    padding: '14px',
                                    background: '#6b7280',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '10px',
                                    fontSize: '15px',
                                    fontWeight: '600',
                                    cursor: loading ? 'not-allowed' : 'pointer',
                                    opacity: loading ? 0.5 : 1
                                }}
                            >
                                Cancelar
                            </button>
                            <button
                                onClick={handleSubmitComplaint}
                                disabled={loading || !selectedRoute || !selectedDriver || !complaintReason.trim()}
                                style={{
                                    flex: 1,
                                    padding: '14px',
                                    background: (!selectedRoute || !selectedDriver || !complaintReason.trim()) ? '#9ca3af' : '#10b981',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '10px',
                                    fontSize: '15px',
                                    fontWeight: '600',
                                    cursor: (loading || !selectedRoute || !selectedDriver || !complaintReason.trim()) ? 'not-allowed' : 'pointer',
                                    opacity: loading ? 0.5 : 1
                                }}
                            >
                                {loading ? 'Enviando...' : 'Enviar Queja'}
                            </button>
                        </div>
                    </div>
                </FullscreenView>
            )}
        </div>
    );
};

export default ComplaintsSection;
