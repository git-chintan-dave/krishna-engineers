#Requires -Version 5.1

[CmdletBinding()]
param(
    [string]$InstallPath = 'C:\KrishnaEngineersApp'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Stop-Update {
    param([string]$Message)
    throw $Message
}

function Get-CommandPath {
    param([string]$Name)

    $command = Get-Command $Name -ErrorAction SilentlyContinue

    if ($command) {
        return $command.Source
    }

    return $null
}

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)

    return $principal.IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )
}

function Invoke-CheckedCommand {
    param(
        [string]$FilePath,
        [string[]]$Arguments,
        [string]$Description
    )

    Write-Host ''
    Write-Host "[INFO] $Description" -ForegroundColor Cyan
    Write-Host "Command: $FilePath $($Arguments -join ' ')"

    & $FilePath @Arguments

    if ($LASTEXITCODE -ne 0) {
        Stop-Update "$Description failed with exit code $LASTEXITCODE."
    }
}

try {
    Write-Host ''
    Write-Host '============================================' -ForegroundColor Cyan
    Write-Host '  Krishna Engineers - Application Updater' -ForegroundColor Cyan
    Write-Host '============================================' -ForegroundColor Cyan
    Write-Host "Source:      $((Split-Path -Parent $PSScriptRoot))"
    Write-Host "Destination: $InstallPath"
    Write-Host ''

    if (-not (Test-Administrator)) {
        Stop-Update 'Administrator permissions are required. Run the launcher as Administrator.'
    }

    $SourcePath = Split-Path -Parent $PSScriptRoot

    # Validate the installed application.
    if (-not (Test-Path -LiteralPath (Join-Path $InstallPath 'artisan') -PathType Leaf)) {
        Stop-Update "Installed application not found at $InstallPath."
    }

    if (-not (Test-Path -LiteralPath (Join-Path $InstallPath '.env') -PathType Leaf)) {
        Stop-Update "Installed .env file not found at $InstallPath."
    }

    # Validate the update source and lock files.
    foreach ($file in @(
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json'
    )) {
        $sourceFile = Join-Path $SourcePath $file

        if (-not (Test-Path -LiteralPath $sourceFile -PathType Leaf)) {
            Stop-Update "Update source is missing '$file'. Ensure the complete project is present."
        }
    }

    # Validate required tools.
    $phpPath = Get-CommandPath 'php'
    $composerPath = Get-CommandPath 'composer'
    $nodePath = Get-CommandPath 'node'
    $npmPath = Get-CommandPath 'npm'

    if (-not $phpPath) {
        Stop-Update 'PHP is missing or not available on PATH.'
    }

    if (-not $composerPath) {
        Stop-Update 'Composer is missing or not available on PATH.'
    }

    if (-not $nodePath) {
        Stop-Update 'Node.js is missing or not available on PATH.'
    }

    if (-not $npmPath) {
        Stop-Update 'npm is missing or not available on PATH.'
    }

    $phpVersion = (& $phpPath -r 'echo PHP_VERSION;' 2>$null | Out-String).Trim()

    if (-not $phpVersion -or [version]$phpVersion -lt [version]'8.3') {
        Stop-Update "PHP 8.3 or later is required. Detected: $phpVersion"
    }

    $nodeVersion = (& $nodePath --version 2>$null | Out-String).Trim().TrimStart('v')

    if (-not $nodeVersion -or [version]$nodeVersion -lt [version]'22.0') {
        Stop-Update "Node.js 22 or later is required. Detected: $nodeVersion"
    }

    Write-Host "[INFO] PHP $phpVersion detected." -ForegroundColor Green
    Write-Host "[INFO] Node.js $nodeVersion detected." -ForegroundColor Green

    # Preserve the installed .env, vendor, node_modules, and database.
    # Do not delete files that are absent from the update source.
    Write-Host ''
    Write-Host '[INFO] Copying updated application files...' -ForegroundColor Cyan

    & robocopy $SourcePath $InstallPath /E /COPY:DAT /DCOPY:DAT /R:2 /W:2 `
        /XD '.git' 'node_modules' 'vendor' `
        /XF '.env' `
        /NFL /NDL /NP

    $copyExitCode = $LASTEXITCODE

    # Robocopy exit codes 0-7 indicate success or success with differences.
    if ($copyExitCode -ge 8) {
        Stop-Update "Copying application files failed with robocopy exit code $copyExitCode."
    }

    Set-Location -LiteralPath $InstallPath

    # Install/update PHP dependencies using composer.lock.
    Invoke-CheckedCommand $composerPath @(
        'install',
        '--no-dev',
        '--prefer-dist',
        '--optimize-autoloader',
        '--no-interaction'
    ) 'Installing PHP dependencies'

    # Install/update frontend dependencies using package-lock.json.
    Invoke-CheckedCommand $npmPath @(
        'ci',
        '--no-audit',
        '--no-fund'
    ) 'Installing frontend dependencies'

    # Apply migrations without dropping existing tables or data.
    Invoke-CheckedCommand $phpPath @(
        'artisan',
        'migrate',
        '--force',
        '--no-interaction'
    ) 'Running database migrations'

    # Compile production assets.
    Invoke-CheckedCommand $npmPath @(
        'run',
        'build'
    ) 'Building production frontend assets'

    # Refresh Laravel caches.
    Invoke-CheckedCommand $phpPath @(
        'artisan',
        'optimize:clear'
    ) 'Clearing Laravel caches'

    Invoke-CheckedCommand $phpPath @(
        'artisan',
        'optimize'
    ) 'Rebuilding Laravel caches'

    Write-Host ''
    Write-Host '============================================' -ForegroundColor Green
    Write-Host '  UPDATE COMPLETED SUCCESSFULLY' -ForegroundColor Green
    Write-Host '============================================' -ForegroundColor Green
    Write-Host "Installed at: $InstallPath"
    Write-Host ''
    Write-Host 'The installed .env and existing database were preserved.'
    Write-Host ''

    exit 0
}
catch {
    Write-Host ''
    Write-Host '============================================' -ForegroundColor Red
    Write-Host '  UPDATE FAILED' -ForegroundColor Red
    Write-Host '============================================' -ForegroundColor Red
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ''
    Write-Host 'The updater does not intentionally delete the installed .env or database.'
    Write-Host 'Review the error above, resolve it, and rerun the updater.'

    exit 1
}
