
# installer/Install-KrishnaEngineers.ps1
# Run PowerShell as Administrator.
# Intended for a fresh Windows installation from an extracted project ZIP.

#Requires -Version 5.1

[CmdletBinding()]
param(
    [string]$InstallPath = 'C:\KrishnaEngineers'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Cyan
}

function Write-Ok {
    param([string]$Message)
    Write-Host "[ OK ] $Message" -ForegroundColor Green
}

function Stop-Install {
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

function Invoke-CheckedCommand {
    param(
        [Parameter(Mandatory)][string]$FilePath,
        [Parameter(Mandatory)][string[]]$Arguments,
        [Parameter(Mandatory)][string]$Description
    )

    Write-Info $Description

    & $FilePath @Arguments

    if ($LASTEXITCODE -ne 0) {
        Stop-Install "$Description failed with exit code $LASTEXITCODE."
    }
}

function Get-EnvValue {
    param(
        [Parameter(Mandatory)][string]$FilePath,
        [Parameter(Mandatory)][string]$Key
    )

    $pattern = "(?m)^\s*$([regex]::Escape($Key))\s*=\s*(.*)$"
    $match = [regex]::Match(
        [System.IO.File]::ReadAllText($FilePath),
        $pattern
    )

    if (-not $match.Success) {
        return ''
    }

    $value = $match.Groups[1].Value.Trim()

    if (
        $value.Length -ge 2 -and
        (
            ($value.StartsWith('"') -and $value.EndsWith('"')) -or
            ($value.StartsWith("'") -and $value.EndsWith("'"))
        )
    ) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

function Set-EnvValue {
    param(
        [Parameter(Mandatory)][string]$FilePath,
        [Parameter(Mandatory)][string]$Key,
        [Parameter(Mandatory)][string]$Value
    )

    $content = [System.IO.File]::ReadAllText($FilePath)
    $pattern = "(?m)^\s*$([regex]::Escape($Key))\s*=.*$"
    $replacement = "$Key=$Value"

    if ([regex]::IsMatch($content, $pattern)) {
        $content = [regex]::Replace(
            $content,
            $pattern,
            [System.Text.RegularExpressions.MatchEvaluator]{
                param($match)
                return $replacement
            }
        )
    }
    else {
        $content += "`r`n$replacement`r`n"
    }

    [System.IO.File]::WriteAllText(
        $FilePath,
        $content,
        [System.Text.UTF8Encoding]::new($false)
    )
}

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)

    return $principal.IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )
}

try {
    Write-Host ''
    Write-Host 'Krishna Engineers - Installer' -ForegroundColor Cyan
    Write-Host '----------------------------------------'

    if (-not (Test-Administrator)) {
        Stop-Install 'Open PowerShell as Administrator and run the installer again.'
    }

    # The installer is inside the extracted project ZIP.
    $SourcePath = Split-Path -Parent $PSScriptRoot
    $SourceEnv = Join-Path $SourcePath '.env'
    $TargetEnv = Join-Path $InstallPath '.env'

    $requiredFiles = @(
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json'
    )

    foreach ($file in $requiredFiles) {
        if (-not (Test-Path (Join-Path $SourcePath $file))) {
            Stop-Install "Project file not found: $file. Extract the complete ZIP first."
        }
    }

    if (-not (Test-Path $SourceEnv)) {
        Stop-Install (
            "The project .env file was not found at $SourceEnv. " +
            'Copy .env.example to .env, configure it, and rerun the installer.'
        )
    }

    # Validate runtimes.
    $phpPath = Get-CommandPath 'php'
    $composerPath = Get-CommandPath 'composer'
    $npmPath = Get-CommandPath 'npm'
    $nodePath = Get-CommandPath 'node'

    if (-not $phpPath) { Stop-Install 'PHP is not installed or not on PATH.' }
    if (-not $composerPath) { Stop-Install 'Composer is not installed or not on PATH.' }
    if (-not $npmPath) { Stop-Install 'npm is not installed or not on PATH.' }
    if (-not $nodePath) { Stop-Install 'Node.js is not installed or not on PATH.' }

    $phpVersionText = (& $phpPath -r 'echo PHP_VERSION;' 2>$null | Out-String).Trim()

    if (-not $phpVersionText -or [version]$phpVersionText -lt [version]'8.3') {
        Stop-Install "PHP 8.3 or later is required. Detected: $phpVersionText"
    }

    $nodeVersionText = (& $nodePath --version 2>$null | Out-String).Trim().TrimStart('v')

    if (-not $nodeVersionText -or [version]$nodeVersionText -lt [version]'22.0') {
        Stop-Install "Node.js 22 or later is required. Detected: $nodeVersionText"
    }

    Write-Ok "PHP $phpVersionText detected."
    Write-Ok "Node.js $nodeVersionText detected."

    # Ensure the destination will not overwrite an existing installation.
    if (Test-Path $InstallPath) {
        $items = @(Get-ChildItem -LiteralPath $InstallPath -Force)

        if ($items.Count -gt 0) {
            Stop-Install (
                "Installation directory is not empty: $InstallPath. " +
                'This installer is for a fresh installation and will not overwrite it.'
            )
        }
    }
    else {
        New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
    }

    # Copy project files without developer dependencies or local secrets.
    Write-Info "Copying project files to $InstallPath"

    & robocopy $SourcePath $InstallPath /E /COPY:DAT /DCOPY:DAT /R:2 /W:2 `
        /XD '.git' 'node_modules' 'vendor' `
        /XF '.env' '.env.*' `
        /NFL /NDL /NP

    if ($LASTEXITCODE -ge 8) {
        Stop-Install "Project copy failed with robocopy exit code $LASTEXITCODE."
    }

    # Explicitly copy the .env the client configured in the extracted ZIP.
    Copy-Item -LiteralPath $SourceEnv -Destination $TargetEnv -Force

    Set-Location $InstallPath

    # Configure production safety settings while preserving DB and E-Time Office values.
    Set-EnvValue -FilePath $TargetEnv -Key 'APP_ENV' -Value 'production'
    Set-EnvValue -FilePath $TargetEnv -Key 'APP_DEBUG' -Value 'false'

    # Ensure the database connection is configured before running Artisan.
    $dbConnection = Get-EnvValue -FilePath $TargetEnv -Key 'DB_CONNECTION'
    $dbName = Get-EnvValue -FilePath $TargetEnv -Key 'DB_DATABASE'
    $dbHost = Get-EnvValue -FilePath $TargetEnv -Key 'DB_HOST'
    $dbUser = Get-EnvValue -FilePath $TargetEnv -Key 'DB_USERNAME'
    $dbPassword = Get-EnvValue -FilePath $TargetEnv -Key 'DB_PASSWORD'

    if ($dbConnection -ne 'mysql') {
        Stop-Install "Expected DB_CONNECTION=mysql in .env. Found '$dbConnection'."
    }

    if (
        [string]::IsNullOrWhiteSpace($dbName) -or
        [string]::IsNullOrWhiteSpace($dbHost) -or
        [string]::IsNullOrWhiteSpace($dbUser) -or
        [string]::IsNullOrWhiteSpace($dbPassword)
    ) {
        Stop-Install 'Complete DB_HOST, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env.'
    }

    # Install dependencies from the committed lock files.
    Invoke-CheckedCommand -FilePath $composerPath `
        -Arguments @(
            'install',
            '--no-dev',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-interaction'
        ) `
        -Description 'Installing PHP dependencies'

    Invoke-CheckedCommand -FilePath $npmPath `
        -Arguments @('ci', '--no-audit', '--no-fund') `
        -Description 'Installing frontend dependencies'

    # Generate an app key only when .env does not already contain one.
    $appKey = Get-EnvValue -FilePath $TargetEnv -Key 'APP_KEY'

    if ([string]::IsNullOrWhiteSpace($appKey)) {
        Invoke-CheckedCommand -FilePath $phpPath `
            -Arguments @('artisan', 'key:generate', '--force', '--no-interaction') `
            -Description 'Generating Laravel application key'
    }
    else {
        Write-Ok 'Existing APP_KEY preserved.'
    }

    # WARNING: This drops all existing tables and data in the configured database.
    Write-Host ''
    Write-Host "Database: $dbName on $dbHost" -ForegroundColor Yellow
    Write-Host 'migrate:fresh will drop existing tables and data in this database.' -ForegroundColor Yellow
    $confirmation = Read-Host 'Type FRESH to confirm this is a new/ disposable application database'

    if ($confirmation -cne 'FRESH') {
        Stop-Install 'Fresh database operation cancelled.'
    }

    # Explicitly seed AdminSeeder only; do not run DatabaseSeeder and other seeders.
    Invoke-CheckedCommand -FilePath $phpPath `
        -Arguments @(
            'artisan',
            'migrate:fresh',
            '--force',
            '--no-interaction',
            '--seed',
            '--seeder=AdminSeeder'
        ) `
        -Description 'Running fresh migrations and AdminSeeder'

    Invoke-CheckedCommand -FilePath $phpPath `
        -Arguments @('artisan', 'storage:link') `
        -Description 'Creating public storage link'

    Invoke-CheckedCommand -FilePath $npmPath `
        -Arguments @('run', 'build') `
        -Description 'Building production frontend assets'

    Invoke-CheckedCommand -FilePath $phpPath `
        -Arguments @('artisan', 'optimize:clear') `
        -Description 'Clearing Laravel caches'

    Invoke-CheckedCommand -FilePath $phpPath `
        -Arguments @('artisan', 'optimize') `
        -Description 'Optimizing Laravel for production'

    # Register the Laravel scheduler to run every minute.
    $taskName = 'KrishnaEngineers-Scheduler'

    $taskAction = New-ScheduledTaskAction `
        -Execute $phpPath `
        -Argument "`"$InstallPath\artisan`" schedule:run" `
        -WorkingDirectory $InstallPath

    $taskTrigger = New-ScheduledTaskTrigger `
        -Once `
        -At (Get-Date).AddMinutes(1) `
        -RepetitionInterval (New-TimeSpan -Minutes 1) `
        -RepetitionDuration (New-TimeSpan -Days 3650)

    $taskSettings = New-ScheduledTaskSettingsSet `
        -StartWhenAvailable `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 10) `
        -MultipleInstances IgnoreNew

    Register-ScheduledTask `
        -TaskName $taskName `
        -Action $taskAction `
        -Trigger $taskTrigger `
        -Settings $taskSettings `
        -Description 'Runs scheduled tasks for Krishna Engineers.' `
        -User 'SYSTEM' `
        -RunLevel Highest `
        -Force | Out-Null

    Write-Ok 'Laravel scheduler registered.'

    Write-Host ''
    Write-Host 'Installation steps completed.' -ForegroundColor Green
    Write-Host "Application directory: $InstallPath"
    Write-Host "Configured database:   $dbName"
    Write-Host ''
    Write-Host 'Next steps:' -ForegroundColor Yellow
    Write-Host '1. Verify the AdminSeeder login credentials.'
    Write-Host '2. Configure and start a production web server.'
    Write-Host '3. Configure LAN HTTPS with a certificate trusted by employee devices.'
    Write-Host '4. Confirm the E-Time Office scheduled command exists in routes/console.php.'
    Write-Host '5. Test login, attendance sync, and PWA installation from another LAN device.'
    Write-Host ''
}
catch {
    Write-Host ''
    Write-Host '[ERROR] Installation failed.' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ''
    exit 1
}
