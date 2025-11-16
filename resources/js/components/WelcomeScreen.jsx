import React from 'react';
import { useNavigate } from 'react-router-dom';

function WelcomeScreen() {
    const navigate = useNavigate();

    return (
        <div style={{
            minHeight: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
            padding: '20px',
            position: 'relative',
            overflow: 'hidden'
        }}>
            {/* Decorative elements */}
            <div style={{
                position: 'absolute',
                top: '10%',
                left: '5%',
                width: '200px',
                height: '200px',
                background: 'rgba(255, 255, 255, 0.1)',
                borderRadius: '50%',
                filter: 'blur(60px)'
            }} />
            <div style={{
                position: 'absolute',
                bottom: '15%',
                right: '8%',
                width: '300px',
                height: '300px',
                background: 'rgba(255, 255, 255, 0.1)',
                borderRadius: '50%',
                filter: 'blur(80px)'
            }} />

            <div style={{
                maxWidth: '500px',
                width: '100%',
                position: 'relative',
                zIndex: 1
            }}>
                {/* Logo and Welcome Section */}
                <div style={{
                    backgroundColor: 'white',
                    borderRadius: '24px',
                    boxShadow: '0 25px 70px rgba(0,0,0,0.3)',
                    padding: '50px 40px',
                    marginBottom: '30px',
                    textAlign: 'center'
                }}>
                    {/* Logo */}
                    <div style={{
                        marginBottom: '30px',
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center'
                    }}>
                        <img
                            src="/img/logo_fondotrasnparente.png"
                            alt="Interflow Logo"
                            style={{
                                width: '220px',
                                height: 'auto',
                                filter: 'drop-shadow(0 6px 12px rgba(0, 0, 0, 0.15))'
                            }}
                        />
                    </div>

                    {/* Welcome Title */}
                    <h1 style={{
                        fontSize: '38px',
                        fontWeight: '900',
                        background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
                        WebkitBackgroundClip: 'text',
                        WebkitTextFillColor: 'transparent',
                        backgroundClip: 'text',
                        marginBottom: '12px',
                        letterSpacing: '-1px'
                    }}>
                        ¡Bienvenido!
                    </h1>

                    <p style={{
                        color: '#64748b',
                        fontSize: '16px',
                        fontWeight: '500',
                        marginBottom: '0',
                        lineHeight: '1.6'
                    }}>
                        Sistema de Cobro de Transporte Público
                    </p>
                </div>

                {/* Role Selection Section */}
                <div style={{
                    backgroundColor: 'white',
                    borderRadius: '24px',
                    boxShadow: '0 25px 70px rgba(0,0,0,0.3)',
                    padding: '40px',
                }}>
                    <h2 style={{
                        fontSize: '22px',
                        fontWeight: '700',
                        color: '#1e293b',
                        marginBottom: '24px',
                        textAlign: 'center'
                    }}>
                        Selecciona tu tipo de acceso
                    </h2>

                    {/* Passenger Button */}
                    <button
                        onClick={() => navigate('/login-passenger')}
                        style={{
                            width: '100%',
                            padding: '20px',
                            fontSize: '18px',
                            fontWeight: '700',
                            color: 'white',
                            background: 'linear-gradient(135deg, #0891b2, #06b6d4)',
                            border: 'none',
                            borderRadius: '14px',
                            cursor: 'pointer',
                            transition: 'all 0.3s',
                            boxShadow: '0 8px 20px rgba(6, 182, 212, 0.4)',
                            marginBottom: '16px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: '12px'
                        }}
                        onMouseEnter={(e) => {
                            e.target.style.transform = 'translateY(-2px)';
                            e.target.style.boxShadow = '0 12px 28px rgba(6, 182, 212, 0.5)';
                        }}
                        onMouseLeave={(e) => {
                            e.target.style.transform = 'translateY(0)';
                            e.target.style.boxShadow = '0 8px 20px rgba(6, 182, 212, 0.4)';
                        }}
                    >
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Ingresar como Pasajero</span>
                    </button>

                    {/* Driver Button */}
                    <button
                        onClick={() => navigate('/login-driver')}
                        style={{
                            width: '100%',
                            padding: '20px',
                            fontSize: '18px',
                            fontWeight: '700',
                            color: '#0891b2',
                            background: 'white',
                            border: '3px solid #0891b2',
                            borderRadius: '14px',
                            cursor: 'pointer',
                            transition: 'all 0.3s',
                            boxShadow: '0 4px 12px rgba(8, 145, 178, 0.15)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: '12px'
                        }}
                        onMouseEnter={(e) => {
                            e.target.style.background = 'linear-gradient(135deg, #0891b2, #06b6d4)';
                            e.target.style.color = 'white';
                            e.target.style.transform = 'translateY(-2px)';
                            e.target.style.boxShadow = '0 12px 28px rgba(6, 182, 212, 0.3)';
                        }}
                        onMouseLeave={(e) => {
                            e.target.style.background = 'white';
                            e.target.style.color = '#0891b2';
                            e.target.style.transform = 'translateY(0)';
                            e.target.style.boxShadow = '0 4px 12px rgba(8, 145, 178, 0.15)';
                        }}
                    >
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                        <span>Ingresar como Chofer</span>
                    </button>
                </div>

                {/* Footer */}
                <div style={{
                    textAlign: 'center',
                    marginTop: '30px',
                    color: 'white',
                    fontSize: '14px',
                    fontWeight: '500',
                    textShadow: '0 2px 4px rgba(0,0,0,0.2)'
                }}>
                    <p style={{ margin: 0 }}>
                        Interflow © 2025 - Sistema de Transporte Inteligente
                    </p>
                </div>
            </div>
        </div>
    );
}

export default WelcomeScreen;
