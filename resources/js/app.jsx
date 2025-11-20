import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import {
    BrowserRouter as Router,
    Routes,
    Route,
    Navigate
} from 'react-router-dom';

import PassengerDashboard from './components/PassengerDashboard'; // Panel del Pasajero
import DriverDashboard from './components/DriverDashboard'; // Panel del Chofer
import LoginUnificado from './components/LoginUnificado'; // Login Unificado
import NoInternetModal from './components/NoInternetModal'; // Modal de Sin Conexion

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
        </Router>
    );
}

const root = ReactDOM.createRoot(document.getElementById('app'));
root.render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
