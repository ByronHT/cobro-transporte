@echo off
echo ========================================
echo OBTENIENDO SHA-1 PARA ANDROID
echo ========================================
echo.

REM Buscar keytool en Android Studio
set "KEYTOOL="

if exist "C:\Program Files\Android\Android Studio\jbr\bin\keytool.exe" (
    set "KEYTOOL=C:\Program Files\Android\Android Studio\jbr\bin\keytool.exe"
)

if exist "C:\Program Files\Android\Android Studio\jre\bin\keytool.exe" (
    set "KEYTOOL=C:\Program Files\Android\Android Studio\jre\bin\keytool.exe"
)

if exist "%LOCALAPPDATA%\Android\Sdk\build-tools\34.0.0\keytool.exe" (
    set "KEYTOOL=%LOCALAPPDATA%\Android\Sdk\build-tools\34.0.0\keytool.exe"
)

if "%KEYTOOL%"=="" (
    echo ERROR: No se encontro keytool.exe
    echo.
    echo Busca manualmente en:
    echo - C:\Program Files\Android\Android Studio\jbr\bin\
    echo - %LOCALAPPDATA%\Android\Sdk\
    echo.
    echo O usa este SHA-1 temporal para desarrollo:
    echo SHA1: B7:0F:2C:0B:32:A1:2D:37:2A:28:F1:1D:8A:30:29:0A:32:38:4C:1E
    echo.
    pause
    exit /b
)

echo Encontrado keytool en: %KEYTOOL%
echo.
echo Leyendo debug.keystore...
echo.

"%KEYTOOL%" -list -v -keystore "%USERPROFILE%\.android\debug.keystore" -alias androiddebugkey -storepass android -keypass android | findstr "SHA1"

echo.
echo ========================================
echo COPIA EL SHA1 DE ARRIBA ^
echo ========================================
echo.
pause
