@echo off
setlocal
title Krishna Engineers - Installer and Updater
cd /d "%~dp0"

:: Request administrator privileges if needed
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo Requesting administrator permission...

    set "VBS_FILE=%TEMP%\KrishnaEngineersAdmin_%RANDOM%.vbs"

    (
        echo Set UAC = CreateObject^("Shell.Application"^)
        echo UAC.ShellExecute "cmd.exe", "/c ""%~f0""", "%~dp0", "runas", 1
    ) > "%VBS_FILE%"

    cscript //nologo "%VBS_FILE%"
    del "%VBS_FILE%" >nul 2>&1
    exit /b
)

:MENU
cls
echo ==================================================
echo              KRISHNA ENGINEERS
echo             Application Setup
echo ==================================================
echo.
echo   1. Install application - First-time setup
echo   2. Update existing application
echo   3. Exit
echo.
choice /c 123 /n /m "Select an option [1-3]: "

if errorlevel 3 goto END
if errorlevel 2 goto UPDATE
if errorlevel 1 goto INSTALL

:INSTALL
cls
echo ==================================================
echo             INSTALLING APPLICATION
echo ==================================================
echo.

if not exist "%~dp0installer\Install-KrishnaEngineers.ps1" (
    echo ERROR: Installation script was not found.
    echo Expected: installer\Install-KrishnaEngineers.ps1
    goto ERROR
)

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0installer\Install-KrishnaEngineers.ps1"
set "RESULT=%ERRORLEVEL%"
goto RESULT

:UPDATE
cls
echo ==================================================
echo              UPDATING APPLICATION
echo ==================================================
echo.

if not exist "%~dp0installer\Update-KrishnaEngineers.ps1" (
    echo ERROR: Update script was not found.
    echo Expected: installer\Update-KrishnaEngineers.ps1
    goto ERROR
)

powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0installer\Update-KrishnaEngineers.ps1"
set "RESULT=%ERRORLEVEL%"
goto RESULT

:RESULT
echo.
echo ==================================================
if "%RESULT%"=="0" (
    echo Operation completed successfully.
) else (
    echo Operation failed. Exit code: %RESULT%
    echo Review the errors above before retrying.
)
echo ==================================================
echo.
pause
goto END

:ERROR
echo.
pause
goto END

:END
endlocal
exit /b
