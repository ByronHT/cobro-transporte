import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

function LoginDriver() {
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const response = await axios.post('/api/cliente/login', {
                email,
                password,
            });

            // Verificar que sea un chofer
            if (response.data.user.role !== 'driver') {
                setError('Este usuario no es un chofer. Por favor use el login correcto.');
                setLoading(false);
                return;
            }

            localStorage.setItem('driver_token', response.data.access_token);
            localStorage.setItem('driver_role', response.data.user.role);
            localStorage.setItem('driver_user', JSON.stringify(response.data.user));

            navigate('/driver/dashboard');
        } catch (err) {
            const errorMessage = err.response?.data?.error || 'Ocurrió un error al intentar iniciar sesión.';
            setError(errorMessage);
            setLoading(false);
        }
    };

    return (
        <div style={{
            minHeight: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)',
            padding: '20px'
        }}>
            <div style={{
                maxWidth: '450px',
                width: '100%',
                backgroundColor: 'white',
                borderRadius: '16px',
                boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
                padding: '40px',
            }}>
                {/* Header */}
                <div style={{ textAlign: 'center', marginBottom: '35px' }}>
                    <div style={{
                        marginBottom: '24px',
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center'
                    }}>
                        <img
                            src="/img/logo_fondotrasnparente.png"
                            alt="Interflow Logo"
                            style={{
                                width: '180px',
                                height: 'auto',
                                filter: 'drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1))'
                            }}
                        />
                    </div>
                    <h1 style={{
                        fontSize: '32px',
                        fontWeight: '800',
                        background: 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)',
                        WebkitBackgroundClip: 'text',
                        WebkitTextFillColor: 'transparent',
                        backgroundClip: 'text',
                        marginBottom: '10px',
                        letterSpacing: '-0.5px'
                    }}>
                        Panel de Choferes
                    </h1>
                    <p style={{
                        color: '#64748b',
                        fontSize: '15px',
                        fontWeight: '500'
                    }}>
                        Ingresa tus credenciales para continuar
                    </p>
                </div>

                {/* Error Message */}
                {error && (
                    <div style={{
                        backgroundColor: '#fee2e2',
                        color: '#dc2626',
                        padding: '12px 16px',
                        borderRadius: '8px',
                        marginBottom: '20px',
                        fontSize: '14px',
                        border: '1px solid #fca5a5'
                    }}>
                        <strong>Error:</strong> {error}
                    </div>
                )}

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <div style={{ marginBottom: '20px' }}>
                        <label style={{
                            display: 'block',
                            fontSize: '14px',
                            fontWeight: '600',
                            color: '#334155',
                            marginBottom: '8px'
                        }}>
                            Correo Electrónico
                        </label>
                        <input
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                            style={{
                                width: '100%',
                                padding: '12px 16px',
                                fontSize: '16px',
                                border: '2px solid #e2e8f0',
                                borderRadius: '8px',
                                outline: 'none',
                                transition: 'border-color 0.2s',
                                boxSizing: 'border-box'
                            }}
                            onFocus={(e) => e.target.style.borderColor = '#3b82f6'}
                            onBlur={(e) => e.target.style.borderColor = '#e2e8f0'}
                            placeholder="correo@ejemplo.com"
                        />
                    </div>

                    <div style={{ marginBottom: '24px' }}>
                        <label style={{
                            display: 'block',
                            fontSize: '14px',
                            fontWeight: '600',
                            color: '#334155',
                            marginBottom: '8px'
                        }}>
                            Contraseña
                        </label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            style={{
                                width: '100%',
                                padding: '12px 16px',
                                fontSize: '16px',
                                border: '2px solid #e2e8f0',
                                borderRadius: '8px',
                                outline: 'none',
                                transition: 'border-color 0.2s',
                                boxSizing: 'border-box'
                            }}
                            onFocus={(e) => e.target.style.borderColor = '#3b82f6'}
                            onBlur={(e) => e.target.style.borderColor = '#e2e8f0'}
                            placeholder="••••••••"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        style={{
                            width: '100%',
                            padding: '14px',
                            fontSize: '16px',
                            fontWeight: '600',
                            color: 'white',
                            background: loading ? '#94a3b8' : 'linear-gradient(135deg, #1e3a8a, #3b82f6)',
                            border: 'none',
                            borderRadius: '8px',
                            cursor: loading ? 'not-allowed' : 'pointer',
                            transition: 'opacity 0.2s',
                            boxShadow: '0 4px 12px rgba(59, 130, 246, 0.4)',
                            marginBottom: '12px'
                        }}
                        onMouseEnter={(e) => {
                            if (!loading) e.target.style.opacity = '0.9';
                        }}
                        onMouseLeave={(e) => {
                            if (!loading) e.target.style.opacity = '1';
                        }}
                    >
                        {loading ? 'Ingresando...' : 'Iniciar Sesión'}
                    </button>

                    {/* Botón Regresar */}
                    <button
                        type="button"
                        onClick={() => navigate('/')}
                        style={{
                            width: '100%',
                            padding: '14px',
                            fontSize: '16px',
                            fontWeight: '600',
                            color: '#64748b',
                            background: 'white',
                            border: '2px solid #e2e8f0',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            transition: 'all 0.2s'
                        }}
                        onMouseEnter={(e) => {
                            e.target.style.borderColor = '#3b82f6';
                            e.target.style.color = '#3b82f6';
                        }}
                        onMouseLeave={(e) => {
                            e.target.style.borderColor = '#e2e8f0';
                            e.target.style.color = '#64748b';
                        }}
                    >
                        Regresar
                    </button>
                </form>

                {/* Footer */}
                <div style={{
                    textAlign: 'center',
                    marginTop: '24px',
                    paddingTop: '24px',
                    borderTop: '1px solid #e2e8f0'
                }}>
                    <p style={{ fontSize: '14px', color: '#64748b', margin: 0 }}>
                        ¿Eres pasajero?{' '}
                        <a
                            href="/login-passenger"
                            style={{
                                color: '#3b82f6',
                                textDecoration: 'none',
                                fontWeight: '600'
                            }}
                            onMouseEnter={(e) => e.target.style.textDecoration = 'underline'}
                            onMouseLeave={(e) => e.target.style.textDecoration = 'none'}
                        >
                            ¡Ingresa aquí!
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
}

export default LoginDriver;
