import React, { useState, useEffect } from 'react';

function NoInternetModal() {
    const [isOffline, setIsOffline] = useState(!navigator.onLine);
    const [isChecking, setIsChecking] = useState(false);

    useEffect(() => {
        const handleOnline = () => setIsOffline(false);
        const handleOffline = () => setIsOffline(true);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const handleRetry = async () => {
        setIsChecking(true);

        try {
            // Intentar hacer un ping al servidor
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            await fetch('https://cobro-transporte-production-dac4.up.railway.app/api/health', {
                method: 'HEAD',
                mode: 'no-cors',
                signal: controller.signal
            });

            clearTimeout(timeoutId);
            setIsOffline(false);
            window.location.reload();
        } catch (error) {
            setIsOffline(true);
        } finally {
            setIsChecking(false);
        }
    };

    if (!isOffline) return null;

    return (
        <div style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(0, 0, 0, 0.85)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 99999,
            padding: '20px'
        }}>
            <div style={{
                backgroundColor: 'white',
                borderRadius: '20px',
                padding: '40px 30px',
                maxWidth: '350px',
                width: '100%',
                textAlign: 'center',
                boxShadow: '0 20px 60px rgba(0, 0, 0, 0.3)'
            }}>
                {/* Icono de Sin Conexion */}
                <div style={{
                    width: '80px',
                    height: '80px',
                    backgroundColor: '#fee2e2',
                    borderRadius: '50%',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    margin: '0 auto 24px'
                }}>
                    <svg
                        width="40"
                        height="40"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="#dc2626"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    >
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"></path>
                        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"></path>
                        <path d="M10.71 5.05A16 16 0 0 1 22.58 9"></path>
                        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"></path>
                        <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                        <line x1="12" y1="20" x2="12.01" y2="20"></line>
                    </svg>
                </div>

                {/* Titulo */}
                <h2 style={{
                    fontSize: '24px',
                    fontWeight: '700',
                    color: '#1e293b',
                    marginBottom: '12px'
                }}>
                    Sin Conexion
                </h2>

                {/* Mensaje */}
                <p style={{
                    fontSize: '15px',
                    color: '#64748b',
                    marginBottom: '28px',
                    lineHeight: '1.5'
                }}>
                    No hay conexion a internet. Por favor verifica tu conexion WiFi o datos moviles e intenta nuevamente.
                </p>

                {/* Boton Reintentar */}
                <button
                    onClick={handleRetry}
                    disabled={isChecking}
                    style={{
                        width: '100%',
                        padding: '14px 24px',
                        fontSize: '16px',
                        fontWeight: '600',
                        color: 'white',
                        background: isChecking
                            ? '#94a3b8'
                            : 'linear-gradient(135deg, #0891b2, #06b6d4)',
                        border: 'none',
                        borderRadius: '12px',
                        cursor: isChecking ? 'not-allowed' : 'pointer',
                        boxShadow: isChecking
                            ? 'none'
                            : '0 4px 12px rgba(6, 182, 212, 0.4)',
                        transition: 'all 0.2s',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '8px'
                    }}
                >
                    {isChecking ? (
                        <>
                            <div style={{
                                width: '18px',
                                height: '18px',
                                border: '2px solid rgba(255,255,255,0.3)',
                                borderTop: '2px solid white',
                                borderRadius: '50%',
                                animation: 'spin 1s linear infinite'
                            }}></div>
                            Verificando...
                        </>
                    ) : (
                        <>
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                            >
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                            </svg>
                            Reintentar
                        </>
                    )}
                </button>

                <style>{`
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `}</style>
            </div>
        </div>
    );
}

export default NoInternetModal;
