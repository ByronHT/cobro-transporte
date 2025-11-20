import React, { useState } from 'react';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';

/**
 * Componente reutilizable para capturar fotos con la cámara del dispositivo
 *
 * @param {Function} onPhotoTaken - Callback que recibe el objeto File de la foto capturada
 * @param {string} label - Texto del botón (opcional)
 * @param {boolean} disabled - Si el botón está deshabilitado (opcional)
 */
function CameraButton({ onPhotoTaken, label = "Tomar Foto", disabled = false }) {
    const [loading, setLoading] = useState(false);
    const [preview, setPreview] = useState(null);

    const takePicture = async () => {
        setLoading(true);
        try {
            // Solicitar permisos y capturar foto
            const image = await Camera.getPhoto({
                quality: 80,
                allowEditing: false,
                resultType: CameraResultType.DataUrl,
                source: CameraSource.Camera, // Forzar uso de cámara
                saveToGallery: false,
                correctOrientation: true
            });

            // Convertir DataURL a Blob y luego a File
            const response = await fetch(image.dataUrl);
            const blob = await response.blob();
            const file = new File([blob], `photo_${Date.now()}.jpg`, { type: 'image/jpeg' });

            setPreview(image.dataUrl);
            onPhotoTaken(file);
        } catch (error) {
            if (error.message !== 'User cancelled photos app') {
                console.error('Error al capturar foto:', error);
                alert('Error al capturar foto. Intenta nuevamente.');
            }
        } finally {
            setLoading(false);
        }
    };

    const selectFromGallery = async () => {
        setLoading(true);
        try {
            const image = await Camera.getPhoto({
                quality: 80,
                allowEditing: false,
                resultType: CameraResultType.DataUrl,
                source: CameraSource.Photos, // Seleccionar de galería
                saveToGallery: false
            });

            const response = await fetch(image.dataUrl);
            const blob = await response.blob();
            const file = new File([blob], `photo_${Date.now()}.jpg`, { type: 'image/jpeg' });

            setPreview(image.dataUrl);
            onPhotoTaken(file);
        } catch (error) {
            if (error.message !== 'User cancelled photos app') {
                console.error('Error al seleccionar foto:', error);
                alert('Error al seleccionar foto. Intenta nuevamente.');
            }
        } finally {
            setLoading(false);
        }
    };

    const clearPhoto = () => {
        setPreview(null);
        onPhotoTaken(null);
    };

    return (
        <div style={{ marginTop: '12px' }}>
            {!preview ? (
                <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                    {/* Botón Tomar Foto */}
                    <button
                        type="button"
                        onClick={takePicture}
                        disabled={disabled || loading}
                        style={{
                            flex: '1',
                            minWidth: '140px',
                            padding: '12px 16px',
                            fontSize: '14px',
                            fontWeight: '600',
                            color: 'white',
                            background: disabled || loading
                                ? '#94a3b8'
                                : 'linear-gradient(135deg, #10b981, #059669)',
                            border: 'none',
                            borderRadius: '8px',
                            cursor: disabled || loading ? 'not-allowed' : 'pointer',
                            transition: 'all 0.2s',
                            boxShadow: '0 2px 8px rgba(16, 185, 129, 0.3)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: '8px'
                        }}
                    >
                        {loading ? (
                            <>
                                <div style={{
                                    width: '16px',
                                    height: '16px',
                                    border: '2px solid rgba(255,255,255,0.3)',
                                    borderTop: '2px solid white',
                                    borderRadius: '50%',
                                    animation: 'spin 1s linear infinite'
                                }}></div>
                                Capturando...
                            </>
                        ) : (
                            <>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                {label}
                            </>
                        )}
                    </button>

                    {/* Botón Galería */}
                    <button
                        type="button"
                        onClick={selectFromGallery}
                        disabled={disabled || loading}
                        style={{
                            flex: '1',
                            minWidth: '140px',
                            padding: '12px 16px',
                            fontSize: '14px',
                            fontWeight: '600',
                            color: disabled || loading ? '#94a3b8' : '#0891b2',
                            background: 'white',
                            border: `2px solid ${disabled || loading ? '#94a3b8' : '#0891b2'}`,
                            borderRadius: '8px',
                            cursor: disabled || loading ? 'not-allowed' : 'pointer',
                            transition: 'all 0.2s',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: '8px'
                        }}
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        Galería
                    </button>
                </div>
            ) : (
                <div>
                    {/* Preview de la imagen */}
                    <div style={{
                        position: 'relative',
                        width: '100%',
                        maxWidth: '300px',
                        margin: '0 auto',
                        borderRadius: '8px',
                        overflow: 'hidden',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
                    }}>
                        <img
                            src={preview}
                            alt="Preview"
                            style={{
                                width: '100%',
                                height: 'auto',
                                display: 'block'
                            }}
                        />
                        <button
                            type="button"
                            onClick={clearPhoto}
                            style={{
                                position: 'absolute',
                                top: '8px',
                                right: '8px',
                                width: '32px',
                                height: '32px',
                                padding: '0',
                                background: 'rgba(239, 68, 68, 0.9)',
                                border: 'none',
                                borderRadius: '50%',
                                cursor: 'pointer',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                color: 'white',
                                fontSize: '18px',
                                fontWeight: 'bold',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.2)'
                            }}
                        >
                            ×
                        </button>
                    </div>
                    <p style={{
                        textAlign: 'center',
                        marginTop: '12px',
                        color: '#10b981',
                        fontSize: '14px',
                        fontWeight: '600'
                    }}>
                        ✓ Foto capturada correctamente
                    </p>
                </div>
            )}

            <style>{`
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `}</style>
        </div>
    );
}

export default CameraButton;
