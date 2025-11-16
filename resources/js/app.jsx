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
import LoginDriver from './components/LoginDriver'; // Login para Choferes
import LoginPassenger from './components/LoginPassenger'; // Login para Pasajeros
import DriverDashboard from './components/DriverDashboard'; // Panel del Chofer
import WelcomeScreen from './components/WelcomeScreen'; // Pantalla de Bienvenida

// Componente para proteger rutas de CHOFERES
function DriverProtectedRoute({ children }) {
    const token = localStorage.getItem('driver_token');
    const role = localStorage.getItem('driver_role');

    if (!token || role !== 'driver') {
        return <Navigate to="/login-driver" />;
    }
    return children;
}

// Componente para proteger rutas de PASAJEROS
function PassengerProtectedRoute({ children }) {
    const token = localStorage.getItem('passenger_token');
    const role = localStorage.getItem('passenger_role');

    if (!token || role !== 'passenger') {
        return <Navigate to="/login-passenger" />;
    }
    return children;
}

// Componente principal de la aplicación
function App() {
    return (
        <Router future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
            <Routes>
                {/* Login para Choferes */}
                <Route path="/login-driver" element={<LoginDriver />} />

                {/* Login para Pasajeros */}
                <Route path="/login-passenger" element={<LoginPassenger />} />

                {/* Dashboard del Pasajero (protegido) */}
                <Route
                    path="/passenger/dashboard"
                    element={
                        <PassengerProtectedRoute>
                            <PassengerDashboard />
                        </PassengerProtectedRoute>
                    }
                />

                {/* Ruta legacy /dashboard redirige a /passenger/dashboard */}
                <Route path="/dashboard" element={<Navigate to="/passenger/dashboard" />} />

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
                            // Mostrar pantalla de bienvenida si no hay sesión
                            return <WelcomeScreen />;
                        }
                    })()}
                />

                {/* Compatibilidad con ruta antigua /login - redirige al welcome */}
                <Route path="/login" element={<WelcomeScreen />} />
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
