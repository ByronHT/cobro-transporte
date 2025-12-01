import React, { useState, useEffect } from 'react';

const HorasModal = ({ isOpen, onClose, apiClient }) => {
    const [records, setRecords] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (isOpen) {
            fetchRecords();
        }
    }, [isOpen]);

    const fetchRecords = async () => {
        setLoading(true);
        setError(null);

        try {
            const response = await apiClient.get('/api/driver/time-records/turno');
            setRecords(response.data);
        } catch (err) {
            setError(err.message || 'Error al cargar registros de horas');
        } finally {
            setLoading(false);
        }
    };

    const formatDateTime = (datetime) => {
        if (!datetime) return '-';
        const date = new Date(datetime);
        return date.toLocaleString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatTime = (datetime) => {
        if (!datetime) return '-';
        const date = new Date(datetime);
        return date.toLocaleTimeString('es-BO', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getEstadoBadge = (estado) => {
        const badges = {
            'en_curso': { bg: 'bg-blue-100', text: 'text-blue-800', label: 'En Curso' },
            'normal': { bg: 'bg-green-100', text: 'text-green-800', label: 'Normal' },
            'retrasado': { bg: 'bg-red-100', text: 'text-red-800', label: 'Retrasado' }
        };

        const badge = badges[estado] || badges['normal'];

        return (
            <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${badge.bg} ${badge.text}`}>
                {badge.label}
            </span>
        );
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
                {/* Header */}
                <div className="bg-emerald-600 text-white px-6 py-4 flex justify-between items-center">
                    <h2 className="text-xl font-bold flex items-center gap-2">
                        📅 Registro de Horas
                    </h2>
                    <button
                        onClick={onClose}
                        className="text-white hover:text-gray-200 transition"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Body */}
                <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                    {loading && (
                        <div className="flex justify-center items-center py-8">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
                        </div>
                    )}

                    {error && (
                        <div className="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-4">
                            <p className="font-semibold">Error:</p>
                            <p>{error}</p>
                        </div>
                    )}

                    {!loading && !error && records.length === 0 && (
                        <div className="text-center py-8 text-gray-500">
                            <p className="text-lg">No hay registros de horas para hoy</p>
                            <p className="text-sm mt-2">Los registros aparecerán cuando inicies un viaje</p>
                        </div>
                    )}

                    {!loading && !error && records.length > 0 && (
                        <div className="overflow-x-auto">
                            <table className="min-w-full bg-white border border-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                            #
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                            Columna IDA
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                            Columna VUELTA
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                            Estado
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                                            Retraso
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {records.map((record, index) => (
                                        <tr key={record.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-b">
                                                {index + 1}
                                            </td>
                                            <td className="px-4 py-4 text-sm text-gray-900 border-b">
                                                {record.inicio_ida && (
                                                    <div>
                                                        <p className="font-semibold">Inicio:</p>
                                                        <p className="text-xs text-gray-600">{formatDateTime(record.inicio_ida)}</p>
                                                        {record.fin_ida && (
                                                            <>
                                                                <p className="font-semibold mt-2">Fin:</p>
                                                                <p className="text-xs text-gray-600">{formatDateTime(record.fin_ida)}</p>
                                                            </>
                                                        )}
                                                    </div>
                                                )}
                                                {!record.inicio_ida && <span className="text-gray-400">-</span>}
                                            </td>
                                            <td className="px-4 py-4 text-sm text-gray-900 border-b">
                                                {record.inicio_vuelta && (
                                                    <div>
                                                        <p className="font-semibold">Inicio:</p>
                                                        <p className="text-xs text-gray-600">{formatDateTime(record.inicio_vuelta)}</p>
                                                        {record.fin_vuelta_estimado && (
                                                            <>
                                                                <p className="font-semibold mt-2">Estimado:</p>
                                                                <p className="text-xs text-gray-600">{formatTime(record.fin_vuelta_estimado)}</p>
                                                            </>
                                                        )}
                                                        {record.fin_vuelta_real && (
                                                            <>
                                                                <p className="font-semibold mt-2">Real:</p>
                                                                <p className="text-xs text-gray-600">{formatDateTime(record.fin_vuelta_real)}</p>
                                                            </>
                                                        )}
                                                    </div>
                                                )}
                                                {!record.inicio_vuelta && <span className="text-gray-400">-</span>}
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap text-sm border-b">
                                                {getEstadoBadge(record.estado)}
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-b">
                                                {record.tiempo_retraso_minutos ? (
                                                    <span className="text-red-600 font-semibold">
                                                        +{record.tiempo_retraso_minutos} min
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-400">-</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Información adicional */}
                    {records.length > 0 && (
                        <div className="mt-6 bg-gray-50 rounded-lg p-4">
                            <h3 className="font-semibold text-gray-700 mb-2">Resumen del Turno</h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Total de viajes</p>
                                    <p className="text-2xl font-bold text-emerald-600">{records.length}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Viajes retrasados</p>
                                    <p className="text-2xl font-bold text-red-600">
                                        {records.filter(r => r.estado === 'retrasado').length}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Viajes normales</p>
                                    <p className="text-2xl font-bold text-green-600">
                                        {records.filter(r => r.estado === 'normal').length}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    );
};

export default HorasModal;
