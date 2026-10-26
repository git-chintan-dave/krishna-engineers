#Requires -Version 5.1

[CmdletBinding()]
param(
    [string]$InstallPath = 'C:\KrishnaEngineersApp'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$script:InstallStarted = $false
$script:InstallSucceeded = $false
$script:MarkerName = '.krishna-install-in-progress'

function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Cyan
}

function Stop-Install {
    param([string]$Message)
    throw $Message
}

function Invoke-CheckedCommand {
    param(
        [string]$FilePath,
        [string[]]$Arguments,
        [string]$Description
    )

    Write-Info $Description
    & $FilePath @Arguments

    if ($LASTEXITCODE -ne 0) {
        Stop-Install "$Description failed with exit code $LASTEXITCODE."
    }
}

function Get-CommandPath {
    param([string]$Name)
    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    return $null
}

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )
}

function Get-EnvValue {
    param([string]$FilePath, [string]$Key)

    $content = [System.IO.File]::ReadAllText($FilePath)
    $pattern = "(?m)^\s*$([regex]::Escape($Key))\s*=\s*(.*)$"
    $match = [regex]::Match($content, $pattern)

    if (-not $match.Success) { return '' }

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
    param([string]$FilePath, [string]$Key, [string]$Value)

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

try {
    Write-Host ''
    Write-Host 'Krishna Engineers - First-time Installer' -ForegroundColor Cyan
    Write-Host "Destination: $InstallPath"
    Write-Host ''

    if (-not (Test-Administrator)) {
        Stop-Install 'Run PowerShell as Administrator.'
    }

    $SourcePath = Split-Path -Parent $PSScriptRoot
    $SourceEnv = Join-Path $SourcePath '.env'

    foreach ($file in @(
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json'
    )) {
        if (-not (Test-Path (Join-Path $SourcePath $file))) {
            Stop-Install "Project file missing: $file. Extract the complete ZIP."
        }
    }

    if (-not (Test-Path $SourceEnv)) {
        Stop-Install 'The extracted project must contain your configured .env file.'
    }

    # Do not silently delete a completed installation.
    # An interrupted installation is automatically cleaned up on retry.
    if (Test-Path $InstallPath) {
        $marker = Join-Path $InstallPath $script:MarkerName

        if (Test-Path $marker) {
            Write-Info 'Removing an incomplete installation from a previous attempt.'
            Remove-Item -LiteralPath $InstallPath -Recurse -Force
        }
        else {
            Stop-Install (
                "Destination already exists: $InstallPath. " +
                'Back up any needed files and remove this folder manually before a new installation.'
            )
        }
    }

    # Validate prerequisites on this computer. A working development PC
    # does not guarantee these are installed on the client's PC.
    $phpPath = Get-CommandPath 'php'
    $composerPath = Get-CommandPath 'composer'
    $nodePath = Get-CommandPath 'node'
    $npmPath = Get-CommandPath 'npm'

    if (-not $phpPath) { Stop-Install 'PHP is missing or not on PATH.' }
    if (-not $composerPath) { Stop-Install 'Composer is missing or not on PATH.' }
    if (-not $nodePath) { Stop-Install 'Node.js is missing or not on PATH.' }
    if (-not $npmPath) { Stop-Install 'npm is missing or not on PATH.' }

    $phpVersion = (& $phpPath -r 'echo PHP_VERSION;' 2>$null | Out-String).Trim()
    if (-not $phpVersion -or [version]$phpVersion -lt [version]'8.3') {
        Stop-Install "PHP 8.3+ is required. Detected: $phpVersion"
    }

    $nodeVersion = (& $nodePath --version 2>$null | Out-String).Trim().TrimStart('v')
    if (-not $nodeVersion -or [version]$nodeVersion -lt [version]'22.0') {
        Stop-Install "Node.js 22+ is required. Detected: $nodeVersion"
    }

    Write-Info "PHP $phpVersion detected."
    Write-Info "Node.js $nodeVersion detected."

    # Begin installation and create a marker for safe retry cleanup.
    New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
    $script:InstallStarted = $true

    Set-Content -LiteralPath (Join-Path $InstallPath $script:MarkerName) `
        -Value 'Incomplete installation marker' -Encoding ASCII

    Write-Info "Copying project files to $InstallPath"

    & robocopy $SourcePath $InstallPath /E /COPY:DAT /DCOPY:DAT /R:2 /W:2 `
        /XD '.git' 'node_modules' 'vendor' `
        /XF '.env' `
        /NFL /NDL /NP

    if ($LASTEXITCODE -ge 8) {
        Stop-Install "File copy failed with robocopy exit code $LASTEXITCODE."
    }

    # Copy the configured .env exactly; do not replace DB or E-Time Office settings.
    Copy-Item -LiteralPath $SourceEnv `
        -Destination (Join-Path $InstallPath '.env') -Force

    Set-Location $InstallPath

    $envFile = Join-Path $InstallPath '.env'

    # Use production safety settings without changing the user's other .env values.
    Set-EnvValue -FilePath $envFile -Key 'APP_ENV' -Value 'production'
    Set-EnvValue -FilePath $envFile -Key 'APP_DEBUG' -Value 'false'

    $dbConnection = Get-EnvValue $envFile 'DB_CONNECTION'
    $dbName = Get-EnvValue $envFile 'DB_DATABASE'
    $dbHost = Get-EnvValue $envFile 'DB_HOST'
    $dbUser = Get-EnvValue $envFile 'DB_USERNAME'
    $dbPassword = Get-EnvValue $envFile 'DB_PASSWORD'

    if ($dbConnection -ne 'mysql') {
        Stop-Install 'Set DB_CONNECTION=mysql in .env.'
    }

    if (
        [string]::IsNullOrWhiteSpace($dbName) -or
        [string]::IsNullOrWhiteSpace($dbHost) -or
        [string]::IsNullOrWhiteSpace($dbUser) -or
        [string]::IsNullOrWhiteSpace($dbPassword)
    ) {
        Stop-Install 'Complete DB_HOST, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env.'
    }

    $appKey = Get-EnvValue $envFile 'APP_KEY'

    if ([string]::IsNullOrWhiteSpace($appKey)) {
        Invoke-CheckedCommand $phpPath @(
            'artisan', 'key:generate', '--force', '--no-interaction'
        ) 'Generating Laravel application key'
    }

    Invoke-CheckedCommand $composerPath @(
        'install', '--no-dev', '--prefer-dist',
        '--optimize-autoloader', '--no-interaction'
    ) 'Installing PHP dependencies'

    Invoke-CheckedCommand $npmPath @(
        'ci', '--no-audit', '--no-fund'
    ) 'Installing frontend dependencies'

    # The database must already exist. This command does not create it.
    # migrate is safe for retries; do not use migrate:fresh here.
    Invoke-CheckedCommand $phpPath @(
        'artisan', 'migrate', '--force', '--no-interaction'
    ) 'Running database migrations'

    # First-time installation only. AdminSeeder should not be run by the update script.
    Invoke-CheckedCommand $phpPath @(
        'artisan', 'db:seed', '--class=AdminSeeder',
        '--force', '--no-interaction'
    ) 'Creating administrator account'

    Invoke-CheckedCommand $phpPath @(
        'artisan', 'storage:link'
    ) 'Creating public storage link'

    Invoke-CheckedCommand $npmPath @(
        'run', 'build'
    ) 'Building production frontend assets'

    Invoke-CheckedCommand $phpPath @(
        'artisan', 'optimize:clear'
    ) 'Clearing Laravel caches'

    Invoke-CheckedCommand $phpPath @(
        'artisan', 'optimize'
    ) 'Optimizing Laravel'

    # Run the Laravel scheduler every minute.
    $taskName = 'KrishnaEngineers-Scheduler'
    $taskAction = New-ScheduledTaskAction `
        -Execute $phpPath `
        -Argument "`"$InstallPath\artisan`" schedule:run" `
        -WorkingDirectory $InstallPath

    $taskTrigger = New-ScheduledTaskTrigger `
        -Once -At (Get-Date).AddMinutes(1) `
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
        -Description 'Laravel scheduler for Krishna Engineers' `
        -User 'SYSTEM' `
        -RunLevel Highest `
        -Force | Out-Null

    Remove-Item -LiteralPath (Join-Path $InstallPath $script:MarkerName) -Force
    $script:InstallSucceeded = $true

    Write-Host ''
    Write-Host 'Installation completed successfully.' -ForegroundColor Green
    Write-Host "Installed at: $InstallPath"
    Write-Host ''
    Write-Host 'Important: configure the web server and LAN HTTPS separately.'
}
catch {
    Write-Host ''
    Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red

    # Remove only a directory marked as an incomplete installation.
    if ($script:InstallStarted -and -not $script:InstallSucceeded) {
        $marker = Join-Path $InstallPath $script:MarkerName

        if (Test-Path $marker) {
            Write-Info 'Cleaning up the incomplete installation so you can retry.'
            Remove-Item -LiteralPath $InstallPath -Recurse -Force -ErrorAction SilentlyContinue
        }
    }

    exit 1
}
