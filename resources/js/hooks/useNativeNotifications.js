import { useEffect, useCallback } from 'react';
import { LocalNotifications } from '@capacitor/local-notifications';
import { Capacitor } from '@capacitor/core';

/**
 * Hook personalizado para notificaciones nativas de Android
 *
 * Características:
 * - Solicita permisos automáticamente al montar
 * - Detecta si la app está en background o foreground
 * - Muestra notificaciones nativas solo en background
 * - Fallback a notificaciones in-app en foreground
 * - Manejo de errores robusto
 *
 * @returns {Object} { showNotification, hasPermission }
 */
export function useNativeNotifications() {
    const isNativePlatform = Capacitor.isNativePlatform();

    /**
     * Solicitar permisos de notificaciones al montar el componente
     */
    useEffect(() => {
        if (!isNativePlatform) {
            console.log('🌐 [Notificaciones] Plataforma web - notificaciones nativas no disponibles');
            return;
        }

        const requestPermissions = async () => {
            try {
                console.log('🔔 [Notificaciones] Solicitando permisos...');
                const result = await LocalNotifications.requestPermissions();

                if (result.display === 'granted') {
                    console.log('✅ [Notificaciones] Permisos concedidos');

                    // Crear canal de notificaciones para Android
                    await createNotificationChannel();
                } else {
                    console.warn('⚠️ [Notificaciones] Permisos denegados');
                }
            } catch (error) {
                console.error('❌ [Notificaciones] Error solicitando permisos:', error);
            }
        };

        requestPermissions();
    }, [isNativePlatform]);

    /**
     * Crear canal de notificaciones (Android 8.0+)
     */
    const createNotificationChannel = async () => {
        try {
            await LocalNotifications.createChannel({
                id: 'interflow-payments',
                name: 'Pagos Interflow',
                description: 'Notificaciones de pagos y eventos del sistema',
                importance: 4, // Alta prioridad
                visibility: 1, // Público
                sound: 'notification.wav',
                vibration: true,
                lights: true,
                lightColor: '#0891b2' // Color cyan del tema
            });

            console.log('📢 [Notificaciones] Canal creado: interflow-payments');
        } catch (error) {
            console.error('❌ [Notificaciones] Error creando canal:', error);
        }
    };

    /**
     * Mostrar notificación nativa
     *
     * @param {Object} options - Opciones de la notificación
     * @param {string} options.title - Título de la notificación
     * @param {string} options.body - Cuerpo del mensaje
     * @param {string} options.type - Tipo (success, error, warning, info)
     * @param {number} options.id - ID único (opcional)
     */
    const showNotification = useCallback(async ({ title, body, type = 'info', id = null }) => {
        // Si no es plataforma nativa, no hacer nada (usar fallback in-app)
        if (!isNativePlatform) {
            console.log('🌐 [Notificaciones] Plataforma web - usar notificación in-app');
            return false;
        }

        // Verificar si la app está en background
        const isAppInBackground = document.hidden || document.visibilityState === 'hidden';

        // Solo mostrar notificaciones nativas si está en background
        // En foreground, el componente debe usar notificaciones in-app
        if (!isAppInBackground) {
            console.log('👁️ [Notificaciones] App en foreground - omitir notificación nativa');
            return false;
        }

        try {
            // Generar ID único si no se proporcionó
            const notificationId = id || Date.now();

            // Determinar icono según tipo
            let smallIcon = 'ic_stat_notification';

            // Programar notificación
            await LocalNotifications.schedule({
                notifications: [
                    {
                        title: title,
                        body: body,
                        id: notificationId,
                        schedule: {
                            at: new Date(Date.now() + 100) // Mostrar inmediatamente
                        },
                        sound: 'notification.wav',
                        smallIcon: smallIcon,
                        largeIcon: 'ic_launcher',
                        iconColor: '#0891b2',
                        channelId: 'interflow-payments',
                        autoCancel: true, // Se cierra al tocar
                        extra: {
                            type: type,
                            timestamp: Date.now()
                        }
                    }
                ]
            });

            console.log(`✅ [Notificaciones] Notificación enviada: ${title}`);
            return true;

        } catch (error) {
            console.error('❌ [Notificaciones] Error enviando notificación:', error);
            return false;
        }
    }, [isNativePlatform]);

    /**
     * Cancelar todas las notificaciones pendientes
     */
    const cancelAllNotifications = useCallback(async () => {
        if (!isNativePlatform) return;

        try {
            await LocalNotifications.cancel({ notifications: [] });
            console.log('🗑️ [Notificaciones] Todas las notificaciones canceladas');
        } catch (error) {
            console.error('❌ [Notificaciones] Error cancelando notificaciones:', error);
        }
    }, [isNativePlatform]);

    return {
        showNotification,
        cancelAllNotifications,
        isNativePlatform
    };
}
