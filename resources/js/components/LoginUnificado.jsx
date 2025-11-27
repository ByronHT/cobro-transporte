import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import { API_BASE_URL } from '../config';

function LoginUnificado() {
    const navigate = useNavigate();
    const [loginMode, setLoginMode] = useState('code'); // 'code' o 'email'
    const [code, setCode] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleLoginWithCode = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const response = await axios.post(`${API_BASE_URL}/api/auth/login-code`, {
                code: code
            });

            const { user, token, role } = response.data;

            if (role === 'driver') {
                localStorage.setItem('driver_token', token);
                localStorage.setItem('driver_role', role);
                localStorage.setItem('driver_user', JSON.stringify(user));
                navigate('/driver/dashboard');
            } else if (role === 'passenger') {
                localStorage.setItem('passenger_token', token);
                localStorage.setItem('passenger_role', role);
                localStorage.setItem('passenger_user', JSON.stringify(user));
                navigate('/passenger/dashboard');
            } else {
                setError('Tipo de usuario no válido. Solo choferes y pasajeros pueden usar esta app.');
                setLoading(false);
            }
        } catch (err) {
            const errorMessage = err.response?.data?.error || 'Código inválido o usuario inactivo.';
            setError(errorMessage);
            setLoading(false);
        }
    };

    const handleLoginWithEmail = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const response = await axios.post(`${API_BASE_URL}/api/cliente/login`, {
                email,
                password,
            });

            const { user, access_token } = response.data;
            const role = user.role;

            if (role === 'driver') {
                localStorage.setItem('driver_token', access_token);
                localStorage.setItem('driver_role', role);
                localStorage.setItem('driver_user', JSON.stringify(user));
                navigate('/driver/dashboard');
            } else if (role === 'passenger') {
                localStorage.setItem('passenger_token', access_token);
                localStorage.setItem('passenger_role', role);
                localStorage.setItem('passenger_user', JSON.stringify(user));
                navigate('/passenger/dashboard');
            } else {
                setError('Tipo de usuario no válido. Solo choferes y pasajeros pueden usar esta app.');
                setLoading(false);
            }
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
            background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
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
                        background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
                        WebkitBackgroundClip: 'text',
                        WebkitTextFillColor: 'transparent',
                        backgroundClip: 'text',
                        marginBottom: '10px',
                        letterSpacing: '-0.5px'
                    }}>
                        Iniciar Sesión
                    </h1>
                    <p style={{
                        color: '#64748b',
                        fontSize: '15px',
                        fontWeight: '500'
                    }}>
                        Ingresa tus credenciales para continuar
                    </p>
                </div>

                {/* Login Mode Tabs */}
                <div style={{
                    display: 'flex',
                    gap: '8px',
                    marginBottom: '24px',
                    borderBottom: '2px solid #e2e8f0'
                }}>
                    <button
                        type="button"
                        onClick={() => { setLoginMode('code'); setError(null); }}
                        style={{
                            flex: 1,
                            padding: '12px',
                            fontSize: '15px',
                            fontWeight: '600',
                            background: 'none',
                            border: 'none',
                            borderBottom: loginMode === 'code' ? '3px solid #06b6d4' : '3px solid transparent',
                            color: loginMode === 'code' ? '#06b6d4' : '#64748b',
                            cursor: 'pointer',
                            transition: 'all 0.2s',
                            marginBottom: '-2px'
                        }}
                    >
                        Código PIN
                    </button>
                    <button
                        type="button"
                        onClick={() => { setLoginMode('email'); setError(null); }}
                        style={{
                            flex: 1,
                            padding: '12px',
                            fontSize: '15px',
                            fontWeight: '600',
                            background: 'none',
                            border: 'none',
                            borderBottom: loginMode === 'email' ? '3px solid #06b6d4' : '3px solid transparent',
                            color: loginMode === 'email' ? '#06b6d4' : '#64748b',
                            cursor: 'pointer',
                            transition: 'all 0.2s',
                            marginBottom: '-2px'
                        }}
                    >
                        Correo
                    </button>
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

                {/* Form - Login con Código */}
                {loginMode === 'code' ? (
                    <form onSubmit={handleLoginWithCode}>
                        <div style={{ marginBottom: '24px' }}>
                            <label style={{
                                display: 'block',
                                fontSize: '14px',
                                fontWeight: '600',
                                color: '#334155',
                                marginBottom: '8px'
                            }}>
                                Código PIN (4 dígitos)
                            </label>
                            <input
                                type="text"
                                value={code}
                                onChange={(e) => {
                                    const value = e.target.value.replace(/\D/g, '').slice(0, 4);
                                    setCode(value);
                                }}
                                required
                                maxLength="4"
                                pattern="\d{4}"
                                style={{
                                    width: '100%',
                                    padding: '16px',
                                    fontSize: '24px',
                                    fontWeight: '700',
                                    letterSpacing: '8px',
                                    textAlign: 'center',
                                    border: '2px solid #e2e8f0',
                                    borderRadius: '8px',
                                    outline: 'none',
                                    transition: 'border-color 0.2s',
                                    boxSizing: 'border-box'
                                }}
                                onFocus={(e) => e.target.style.borderColor = '#06b6d4'}
                                onBlur={(e) => e.target.style.borderColor = '#e2e8f0'}
                                placeholder="••••"
                            />
                            <p style={{
                                fontSize: '13px',
                                color: '#64748b',
                                marginTop: '8px',
                                textAlign: 'center'
                            }}>
                                Ingresa tu código de 4 dígitos
                            </p>
                        </div>

                        <button
                            type="submit"
                            disabled={loading || code.length !== 4}
                            style={{
                                width: '100%',
                                padding: '14px',
                                fontSize: '16px',
                                fontWeight: '600',
                                color: 'white',
                                background: (loading || code.length !== 4) ? '#94a3b8' : 'linear-gradient(135deg, #0891b2, #06b6d4)',
                                border: 'none',
                                borderRadius: '8px',
                                cursor: (loading || code.length !== 4) ? 'not-allowed' : 'pointer',
                                transition: 'opacity 0.2s',
                                boxShadow: '0 4px 12px rgba(6, 182, 212, 0.4)',
                                marginBottom: '12px'
                            }}
                            onMouseEnter={(e) => {
                                if (!loading && code.length === 4) e.target.style.opacity = '0.9';
                            }}
                            onMouseLeave={(e) => {
                                if (!loading) e.target.style.opacity = '1';
                            }}
                        >
                            {loading ? 'Ingresando...' : 'Ingresar'}
                        </button>
                    </form>
                ) : (
                    /* Form - Login con Email */
                    <form onSubmit={handleLoginWithEmail}>
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
                            onFocus={(e) => e.target.style.borderColor = '#06b6d4'}
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
                            onFocus={(e) => e.target.style.borderColor = '#06b6d4'}
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
                            background: loading ? '#94a3b8' : 'linear-gradient(135deg, #0891b2, #06b6d4)',
                            border: 'none',
                            borderRadius: '8px',
                            cursor: loading ? 'not-allowed' : 'pointer',
                            transition: 'opacity 0.2s',
                            boxShadow: '0 4px 12px rgba(6, 182, 212, 0.4)',
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
                </form>
                )}

                {/* Footer */}
                <div style={{
                    textAlign: 'center',
                    marginTop: '24px',
                    paddingTop: '24px',
                    borderTop: '1px solid #e2e8f0'
                }}>
                    <p style={{ fontSize: '14px', color: '#64748b', margin: 0 }}>
                        Sistema de Transporte Público Interflow
                    </p>
                </div>
            </div>
        </div>
    );
}

export default LoginUnificado;
