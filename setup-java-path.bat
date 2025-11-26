@echo off
echo ========================================
echo CONFIGURANDO JAVA EN PATH
echo ========================================
echo.

REM Buscar Java instalado
set "JAVA_DIR="

if exist "C:\Program Files\Java\jdk-17" (
    set "JAVA_DIR=C:\Program Files\Java\jdk-17"
)

if exist "C:\Program Files\Java\jdk-21" (
    set "JAVA_DIR=C:\Program Files\Java\jdk-21"
)

if exist "C:\Program Files\Microsoft\jdk-17.*" (
    for /d %%i in ("C:\Program Files\Microsoft\jdk-17.*") do set "JAVA_DIR=%%i"
)

if exist "C:\Program Files\Eclipse Adoptium\jdk-17.*" (
    for /d %%i in ("C:\Program Files\Eclipse Adoptium\jdk-17.*") do set "JAVA_DIR=%%i"
)

if "%JAVA_DIR%"=="" (
    echo ERROR: No se encontro Java instalado en ubicaciones comunes
    echo.
    echo Busca manualmente en:
    echo - C:\Program Files\Java\
    echo - C:\Program Files\Microsoft\
    echo - C:\Program Files\Eclipse Adoptium\
    echo.
    pause
    exit /b
)

echo Java encontrado en: %JAVA_DIR%
echo.

REM Configurar JAVA_HOME para la sesion actual
set "JAVA_HOME=%JAVA_DIR%"
set "PATH=%JAVA_HOME%\bin;%PATH%"

echo JAVA_HOME configurado: %JAVA_HOME%
echo.

REM Configurar permanentemente (requiere reiniciar cmd)
echo Configurando JAVA_HOME permanentemente...
setx JAVA_HOME "%JAVA_DIR%" /M
setx PATH "%PATH%;%JAVA_DIR%\bin" /M

echo.
echo ========================================
echo CONFIGURACION COMPLETADA
echo ========================================
echo.
echo IMPORTANTE: Cierra y vuelve a abrir PowerShell/CMD
echo para que los cambios tomen efecto.
echo.
echo Luego ejecuta: java -version
echo.
pause
