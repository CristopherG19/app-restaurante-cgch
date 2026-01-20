@echo off
echo ========================================
echo   Over Chef POS - Iniciando Frontend
echo ========================================
echo.

REM Verificar si npm esta disponible
where npm >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] npm no encontrado. Asegurate de tener Node.js instalado.
    echo Descarga Node.js desde: https://nodejs.org/
    pause
    exit /b 1
)

REM Cambiar al directorio del frontend
cd /d c:\dev\apprestaurante\frontend
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] No se pudo acceder al directorio frontend
    pause
    exit /b 1
)

REM Verificar si existe package.json
if not exist "package.json" (
    echo [ERROR] package.json no encontrado en el directorio frontend
    pause
    exit /b 1
)

REM Verificar si node_modules existe
if not exist "node_modules" (
    echo [ADVERTENCIA] node_modules no encontrado. Instalando dependencias...
    echo.
    call npm install
    if %ERRORLEVEL% NEQ 0 (
        echo [ERROR] Fallo la instalacion de dependencias
        pause
        exit /b 1
    )
)

echo.
echo Frontend Vue corriendo en: http://localhost:5173
echo Presiona Ctrl+C para detener
echo.

REM Ejecutar el servidor de desarrollo
call npm run dev

REM Si el comando falla, mostrar error
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Fallo al iniciar el servidor de desarrollo
    pause
    exit /b 1
)
