@echo off
setlocal

cd /d "%~dp0"

title Krishna Engineers Launcher

set "SCRIPT_PATH=%~dp0Run-KrishnaEngineers.ps1"
set "PROJECT_ROOT=%~dp0"
set "PS_PATH=%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe"

echo ============================================
echo   Krishna Engineers Launcher
echo ============================================
echo.
echo Project folder: "%PROJECT_ROOT%"
echo.

if not exist "%SCRIPT_PATH%" (
    echo ERROR: Run-KrishnaEngineers.ps1 was not found.
    echo Expected path:
    echo "%SCRIPT_PATH%"
    echo.
    pause
    exit /b 1
)

if not exist "%PS_PATH%" (
    echo ERROR: Windows PowerShell was not found.
    echo "%PS_PATH%"
    echo.
    pause
    exit /b 1
)

echo Requesting administrator permission...
echo.

"%PS_PATH%" -NoLogo -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference = 'Stop'; try { $process = Start-Process -FilePath $env:PS_PATH -ArgumentList @('-NoLogo', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', ('""' + $env:SCRIPT_PATH + '""')) -WorkingDirectory $env:PROJECT_ROOT -Verb RunAs -Wait -PassThru; exit $process.ExitCode } catch { Write-Host ('Failed to launch: ' + $_.Exception.Message) -ForegroundColor Red; exit 1 }"

set "EXIT_CODE=%ERRORLEVEL%"

echo.
echo ============================================
echo Launcher exited with code: %EXIT_CODE%
echo ============================================
echo.
pause

exit /b %EXIT_CODE%
