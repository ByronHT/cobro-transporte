const fs = require('fs');
const path = require('path');

console.log('📦 Ejecutando post-build para Capacitor...');

// Obtener los nombres de los archivos CSS y JS generados
const assetsDir = path.join(__dirname, 'public', 'build', 'assets');
const files = fs.readdirSync(assetsDir);

const cssFiles = files.filter(f => f.endsWith('.css'));
const jsFile = files.find(f => f.endsWith('.js'));

console.log(`✅ CSS encontrados: ${cssFiles.join(', ')}`);
console.log(`✅ JS encontrado: ${jsFile}`);

// Crear index.html con todos los archivos CSS
const cssLinks = cssFiles.map(css => `    <link rel="stylesheet" href="/assets/${css}">`).join('\n');

const indexHtml = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Interflow - Sistema de Transporte</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" href="/img/logo_fondotrasnparente.png">
    <link rel="apple-touch-icon" href="/img/logo_fondotrasnparente.png">
${cssLinks}
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        #app {
            width: 100%;
            min-height: 100vh;
        }
    </style>
</head>
<body>
    <div id="app"></div>
    <script type="module" src="/assets/${jsFile}"></script>
</body>
</html>`;

// Escribir index.html
const indexPath = path.join(__dirname, 'public', 'build', 'index.html');
fs.writeFileSync(indexPath, indexHtml);
console.log('✅ index.html creado');

// Copiar imágenes
const imgSrc = path.join(__dirname, 'public', 'img');
const imgDest = path.join(__dirname, 'public', 'build', 'img');

// Crear directorio de destino si no existe
if (!fs.existsSync(imgDest)) {
    fs.mkdirSync(imgDest, { recursive: true });
}

// Copiar archivos
const imgFiles = fs.readdirSync(imgSrc);
imgFiles.forEach(file => {
    const srcPath = path.join(imgSrc, file);
    const destPath = path.join(imgDest, file);

    if (fs.statSync(srcPath).isFile()) {
        fs.copyFileSync(srcPath, destPath);
        console.log(`✅ Copiado: ${file}`);
    }
});

console.log('✅ Post-build completado exitosamente!');
console.log('💡 Ahora puedes ejecutar: npx cap sync android');
