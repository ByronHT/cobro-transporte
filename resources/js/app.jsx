import './bootstrap';
import React, { lazy, Suspense } from 'react';
import ReactDOM from 'react-dom/client';
import {
    BrowserRouter as Router,
    Routes,
    Route,
    Navigate
} from 'react-router-dom';

// Importar Capacitor
import { Capacitor } from '@capacitor/core';

// Code Splitting: Lazy loading de componentes pesados
const PassengerDashboard = lazy(() => import('./components/PassengerDashboard')); // Panel del Pasajero
const DriverDashboard = lazy(() => import('./components/DriverDashboard')); // Panel del Chofer
const LoginUnificado = lazy(() => import('./components/LoginUnificado')); // Login Unificado

// Componente ligero que se carga inmediatamente
import NoInternetModal from './components/NoInternetModal'; // Modal de Sin Conexion

// Loading component para mostrar mientras carga
const LoadingScreen = () => (
    <div style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        height: '100vh',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        color: 'white',
        fontFamily: 'system-ui'
    }}>
        <div style={{ textAlign: 'center' }}>
            <div style={{
                width: '50px',
                height: '50px',
                border: '4px solid rgba(255,255,255,0.3)',
                borderTop: '4px solid white',
                borderRadius: '50%',
                animation: 'spin 1s linear infinite',
                margin: '0 auto 20px'
            }} />
            <p>Cargando...</p>
            <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
        </div>
    </div>
);

// Error Boundary para capturar errores
class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Error capturado:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div style={{
                    padding: '20px',
                    textAlign: 'center',
                    fontFamily: 'system-ui'
                }}>
                    <h2>Error en la aplicación</h2>
                    <p>{this.state.error?.message || 'Error desconocido'}</p>
                    <button onClick={() => window.location.reload()}>
                        Recargar App
                    </button>
                </div>
            );
        }
        return this.props.children;
    }
}

// Componente para proteger rutas de CHOFERES
function DriverProtectedRoute({ children }) {
    const token = localStorage.getItem('driver_token');
    const role = localStorage.getItem('driver_role');

    if (!token || role !== 'driver') {
        return <Navigate to="/login" />;
    }
    return children;
}

// Componente para proteger rutas de PASAJEROS
function PassengerProtectedRoute({ children }) {
    const token = localStorage.getItem('passenger_token');
    const role = localStorage.getItem('passenger_role');

    if (!token || role !== 'passenger') {
        return <Navigate to="/login" />;
    }
    return children;
}

// Componente principal de la aplicación
function App() {
    return (
        <Router future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
            {/* Modal de Sin Conexion - siempre visible */}
            <NoInternetModal />
            <Suspense fallback={<LoadingScreen />}>
                <Routes>
                    {/* Login Unificado */}
                    <Route path="/login" element={<LoginUnificado />} />

                    {/* Dashboard del Pasajero (protegido) */}
                    <Route
                        path="/passenger/dashboard"
                        element={
                            <PassengerProtectedRoute>
                                <PassengerDashboard />
                            </PassengerProtectedRoute>
                        }
                    />

                    {/* Dashboard del Chofer (protegido) */}
                    <Route
                        path="/driver/dashboard"
                        element={
                            <DriverProtectedRoute>
                                <DriverDashboard />
                            </DriverProtectedRoute>
                        }
                    />

                    {/* Ruta raíz - detecta si hay sesión activa */}
                    <Route
                        path="/"
                        element={(() => {
                            const driverToken = localStorage.getItem('driver_token');
                            const passengerToken = localStorage.getItem('passenger_token');

                            if (driverToken) {
                                return <Navigate to="/driver/dashboard" />;
                            } else if (passengerToken) {
                                return <Navigate to="/passenger/dashboard" />;
                            } else {
                                return <Navigate to="/login" />;
                            }
                        })()}
                    />
                </Routes>
            </Suspense>
        </Router>
    );
}

const root = ReactDOM.createRoot(document.getElementById('app'));
root.render(
    <ErrorBoundary>
        <React.StrictMode>
            <App />
        </React.StrictMode>
    </ErrorBoundary>
);
