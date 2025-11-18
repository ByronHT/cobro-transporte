<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

        <title>Interflow - Sistema de Transporte</title>

        <!-- PWA Meta Tags -->
        <meta name="description" content="Sistema de cobro de transporte público con tecnología RFID">
        <meta name="theme-color" content="#0891b2">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Interflow">

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.json">

        <!-- Favicons and Icons -->
        <link rel="icon" type="image/png" href="/img/logo_fondotrasnparente.png">
        <link rel="apple-touch-icon" href="/img/logo_fondotrasnparente.png">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])

        <!-- Registrar Service Worker -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/service-worker.js')
                        .then(function(registration) {
                            console.log('ServiceWorker registrado con éxito:', registration.scope);
                        })
                        .catch(function(error) {
                            console.log('Error al registrar ServiceWorker:', error);
                        });
                });
            }
        </script>
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>