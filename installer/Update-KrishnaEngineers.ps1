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
    if ($command) { return $command.Source }
    return $null
}

function Invoke-CheckedCommand {
    param(
        [string]$FilePath,
        [string[]]$Arguments,
        [string]$Description
    )

    Write-Host "[INFO] $Description" -ForegroundColor Cyan
    & $FilePath @Arguments

    if ($LASTEXITCODE -ne 0) {
        Stop-Update "$Description failed with exit code $LASTEXITCODE."
    }
}

try {
    Write-Host ''
    Write-Host 'Krishna Engineers - Application Update' -ForegroundColor Cyan

    $SourcePath = Split-Path -Parent $PSScriptRoot

    if (-not (Test-Path (Join-Path $InstallPath 'artisan'))) {
        Stop-Update "Installed application not found at $InstallPath."
    }

    foreach ($file in @(
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json'
    )) {
        if (-not (Test-Path (Join-Path $SourcePath $file))) {
            Stop-Update "Update ZIP is missing $file."
        }
    }

    $phpPath = Get-CommandPath 'php'
    $composerPath = Get-CommandPath 'composer'
    $npmPath = Get-CommandPath 'npm'

    if (-not $phpPath) { Stop-Update 'PHP is missing or not on PATH.' }
    if (-not $composerPath) { Stop-Update 'Composer is missing or not on PATH.' }
    if (-not $npmPath) { Stop-Update 'npm is missing or not on PATH.' }

    # Never replace the installed .env with the .env from the update ZIP.
    $targetEnv = Join-Path $InstallPath '.env'

    if (-not (Test-Path $targetEnv)) {
        Stop-Update "Installed .env not found at $targetEnv."
    }

    # Copy application source while preserving environment and runtime dependencies.
    # Keep vendor and node_modules so a failed dependency installation can be retried.
    Write-Host '[INFO] Copying updated application files...' -ForegroundColor Cyan

    & robocopy $SourcePath $InstallPath /E /COPY:DAT /DCOPY:DAT /R:2 /W:2 `
        /XD '.git' 'node_modules' 'vendor' `
        /XF '.env' `
        /NFL /NDL /NP

    if ($LASTEXITCODE -ge 8) {
        Stop-Update "File copy failed with robocopy exit code $LASTEXITCODE."
    }

    Set-Location $InstallPath

    Invoke-CheckedCommand $composerPath @(
        'install', '--no-dev', '--prefer-dist',
        '--optimize-autoloader', '--no-interaction'
    ) 'Installing updated PHP dependencies'

    Invoke-CheckedCommand $npmPath @(
        'ci', '--no-audit', '--no-fund'
    ) 'Installing updated frontend dependencies'

    # Applies new migrations without dropping existing tables or data.
    Invoke-CheckedCommand $phpPath @(
        'artisan', 'migrate', '--force', '--no-interaction'
    ) 'Running new database migrations'

    Invoke-CheckedCommand $npmPath @(
        'run', 'build'
    ) 'Building production frontend assets'

    Invoke-CheckedCommand $phpPath @(
        'artisan', 'optimize:clear'
    ) 'Clearing application caches'

    Invoke-CheckedCommand $phpPath @(
        'artisan', 'optimize'
    ) 'Rebuilding application caches'

    Write-Host ''
    Write-Host 'Update completed successfully.' -ForegroundColor Green
    Write-Host "Installed at: $InstallPath"
}
catch {
    Write-Host ''
    Write-Host "[ERROR] Update failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host 'The existing .env and database were not intentionally deleted.'
    Write-Host 'Fix the reported issue and rerun the update script.'
    exit 1
}
