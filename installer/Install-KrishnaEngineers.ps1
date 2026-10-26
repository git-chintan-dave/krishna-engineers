#requires -Version 5.1

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

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = [Security.Principal.WindowsPrincipal]::new($identity)

    return $principal.IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )
}

function Get-CommandPath {
    param([string]$Name)

    $command = Get-Command $Name -ErrorAction SilentlyContinue |
        Select-Object -First 1

    if ($null -eq $command) {
        return $null
    }

    if ($command.CommandType -in @('Application', 'ExternalScript')) {
        return $command.Source
    }

    return $null
}

function Invoke-CheckedCommand {
    param(
        [Parameter(Mandatory)]
        [string]$FilePath,

        [Parameter(Mandatory)]
        [string[]]$Arguments,

        [Parameter(Mandatory)]
        [string]$Description
    )

    Write-Host ''
    Write-Info $Description
    Write-Host "Command: $FilePath $($Arguments -join ' ')"

    & $FilePath @Arguments

    $exitCode = $LASTEXITCODE

    if ($null -eq $exitCode -or $exitCode -ne 0) {
        Stop-Install "$Description failed with exit code $exitCode."
    }

    Write-Info "$Description completed."
}

function Get-EnvValue {
    param(
        [string]$FilePath,
        [string]$Key
    )

    $content = [System.IO.File]::ReadAllText($FilePath)
    $pattern = "(?m)^\s*$([regex]::Escape($Key))\s*=\s*(.*)$"
    $match = [regex]::Match($content, $pattern)

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
        [string]$FilePath,
        [string]$Key,
        [string]$Value
    )

    $lines = [System.Collections.Generic.List[string]]::new()
    $found = $false
    $pattern = '^\s*' + [regex]::Escape($Key) + '\s*='

    foreach ($line in [System.IO.File]::ReadAllLines($FilePath)) {
        if ($line -match $pattern) {
            $lines.Add("$Key=$Value")
            $found = $true
        }
        else {
            $lines.Add($line)
        }
    }

    if (-not $found) {
        $lines.Add("$Key=$Value")
    }

    [System.IO.File]::WriteAllLines(
        $FilePath,
        $lines.ToArray(),
        [System.Text.UTF8Encoding]::new($false)
    )
}

try {
    Write-Host ''
    Write-Host '============================================================' -ForegroundColor Cyan
    Write-Host ' Krishna Engineers - First-time Installer' -ForegroundColor Cyan
    Write-Host '============================================================' -ForegroundColor Cyan
    Write-Host "Source:      $(Split-Path -Parent $PSScriptRoot)"
    Write-Host "Destination: $InstallPath"
    Write-Host ''

    if (-not (Test-Administrator)) {
        Stop-Install 'Administrator permissions are required.'
    }

    $SourcePath = Split-Path -Parent $PSScriptRoot
    $SourceEnv = Join-Path $SourcePath '.env'

    # Confirm the ZIP/project contains all required files before copying.
    foreach ($file in @(
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json'
    )) {
        if (-not (Test-Path -LiteralPath (Join-Path $SourcePath $file) -PathType Leaf)) {
            Stop-Install "Required project file is missing: $file. Ensure the complete project was extracted."
        }
    }

    if (-not (Test-Path -LiteralPath $SourceEnv -PathType Leaf)) {
        Stop-Install 'The project root must contain a configured .env file.'
    }

    # A completed installation must never be removed automatically.
    if (Test-Path -LiteralPath $InstallPath) {
        $marker = Join-Path $InstallPath $script:MarkerName

        if (Test-Path -LiteralPath $marker -PathType Leaf) {
            Write-Info 'An incomplete installation was found. Removing it before retrying.'
            Remove-Item -LiteralPath $InstallPath -Recurse -Force
        }
        else {
            Stop-Install (
                "The destination already exists: $InstallPath. " +
                'This installer will not delete an existing folder without its incomplete-install marker. ' +
                'Back up any needed files and remove the folder manually before a fresh installation.'
            )
        }
    }

    # Validate required runtimes before creating the destination.
    $phpPath = Get-CommandPath 'php'
    $composerPath = Get-CommandPath 'composer'
    $nodePath = Get-CommandPath 'node'
    $npmPath = Get-CommandPath 'npm'

    if (-not $phpPath) {
        Stop-Install 'PHP is missing or is not available on PATH.'
    }
    if (-not $composerPath) {
        Stop-Install 'Composer is missing or is not available on PATH.'
    }
    if (-not $nodePath) {
        Stop-Install 'Node.js is missing or is not available on PATH.'
    }
    if (-not $npmPath) {
        Stop-Install 'npm is missing or is not available on PATH.'
    }

    $phpVersion = (& $phpPath -r 'echo PHP_VERSION;' 2>$null | Out-String).Trim()
    if (-not $phpVersion) {
        Stop-Install 'Unable to determine the PHP version.'
    }
    if ([version]$phpVersion -lt [version]'8.3.0') {
        Stop-Install "PHP 8.3 or newer is required. Detected: $phpVersion"
    }

    $nodeVersion = (& $nodePath --version 2>$null | Out-String).Trim()
    $nodeVersion = $nodeVersion.TrimStart('v')

    if (-not $nodeVersion) {
        Stop-Install 'Unable to determine the Node.js version.'
    }
    if ([version]$nodeVersion -lt [version]'22.0.0') {
        Stop-Install "Node.js 22 or newer is required. Detected: $nodeVersion"
    }

    Write-Info "PHP $phpVersion detected."
    Write-Info "Node.js $nodeVersion detected."
    Write-Info 'Composer and npm commands were found.'

    # Create a marker so a failed first-time installation can be safely retried.
    New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
    $script:InstallStarted = $true

    $marker = Join-Path $InstallPath $script:MarkerName
    Set-Content -LiteralPath $marker `
        -Value 'Incomplete installation; safe to remove during retry.' `
        -Encoding ASCII

    Write-Host ''
    Write-Info "Copying project files to $InstallPath"

    # Do not copy development dependencies or the source .env.
    & robocopy $SourcePath $InstallPath /E /COPY:DAT /DCOPY:DAT /R:2 /W:2 `
        /XD '.git' 'node_modules' 'vendor' `
        /XF '.env'

    $copyExitCode = $LASTEXITCODE

    # Robocopy exit codes 0-7 are success/non-fatal statuses.
    if ($copyExitCode -ge 8) {
        Stop-Install "Project file copy failed with Robocopy exit code $copyExitCode."
    }

    # First install: copy the configured environment file into the destination.
    Copy-Item -LiteralPath $SourceEnv `
        -Destination (Join-Path $InstallPath '.env') -Force

    Set-Location -LiteralPath $InstallPath

    $envFile = Join-Path $InstallPath '.env'

    # Preserve the remaining .env values while enforcing production safety settings.
    Set-EnvValue -FilePath $envFile -Key 'APP_ENV' -Value 'production'
    Set-EnvValue -FilePath $envFile -Key 'APP_DEBUG' -Value 'false'

    $dbConnection = Get-EnvValue -FilePath $envFile -Key 'DB_CONNECTION'
    $dbName = Get-EnvValue -FilePath $envFile -Key 'DB_DATABASE'
    $dbHost = Get-EnvValue -FilePath $envFile -Key 'DB_HOST'
    $dbUser = Get-EnvValue -FilePath $envFile -Key 'DB_USERNAME'

    if ($dbConnection -ne 'mysql') {
        Stop-Install 'Set DB_CONNECTION=mysql in the configured .env file.'
    }

    if (
        [string]::IsNullOrWhiteSpace($dbName) -or
        [string]::IsNullOrWhiteSpace($dbHost) -or
        [string]::IsNullOrWhiteSpace($dbUser)
    ) {
        Stop-Install 'Complete DB_HOST, DB_DATABASE and DB_USERNAME in .env.'
    }

    # IMPORTANT: Artisan needs vendor/autoload.php. Install Composer dependencies first.
    Invoke-CheckedCommand -FilePath $composerPath -Arguments @(
        'install',
        '--no-dev',
        '--prefer-dist',
        '--optimize-autoloader',
        '--no-interaction'
    ) -Description 'Installing PHP dependencies with Composer'

    # This must run only after Composer has created vendor/autoload.php.
    $appKey = Get-EnvValue -FilePath $envFile -Key 'APP_KEY'

    if ([string]::IsNullOrWhiteSpace($appKey)) {
        Invoke-CheckedCommand -FilePath $phpPath -Arguments @(
            'artisan',
            'key:generate',
            '--force',
            '--no-interaction'
        ) -Description 'Generating Laravel application key'
    }
    else {
        Write-Info 'An APP_KEY is already configured; preserving it.'
    }

    Invoke-CheckedCommand -FilePath $npmPath -Arguments @(
        'ci',
        '--no-audit',
        '--no-fund'
    ) -Description 'Installing frontend dependencies with npm'

    # The MySQL database must already exist. This does not create the database.
    Invoke-CheckedCommand -FilePath $phpPath -Arguments @(
        'artisan',
        'migrate',
        '--force',
        '--no-interaction'
    ) -Description 'Running database migrations'

    # First-time installation only. Do not run AdminSeeder during updates.
    Invoke-CheckedCommand -FilePath $phpPath -Arguments @(
        'artisan',
        'db:seed',
        '--class=AdminSeeder',
        '--force',
        '--no-interaction'
    ) -Description 'Creating the initial administrator account'

    Invoke-CheckedCommand -FilePath $phpPath -Arguments @(
        'artisan',
        'storage:link'
    ) -Description 'Creating the public storage link'

    Invoke-CheckedCommand -FilePath $npmPath -Arguments @(
        'run',
        'build'
    ) -Description 'Building production frontend assets'

    Invoke-CheckedCommand -FilePath $phpPath -Arguments @(
        'artisan',
        'optimize:clear'
    ) -Description 'Clearing Laravel caches'

    Invoke-CheckedCommand -FilePath $phpPath -Arguments @(
        'artisan',
        'optimize'
    ) -Description 'Optimizing the Laravel application'

    # Register the Laravel scheduler to run every minute.
    Write-Host ''
    Write-Info 'Registering the Windows scheduled task.'

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
        -Description 'Laravel scheduler for Krishna Engineers' `
        -User 'SYSTEM' `
        -RunLevel Highest `
        -Force | Out-Null

    Remove-Item -LiteralPath $marker -Force
    $script:InstallSucceeded = $true

    Write-Host ''
    Write-Host '============================================================' -ForegroundColor Green
    Write-Host ' INSTALLATION COMPLETED SUCCESSFULLY' -ForegroundColor Green
    Write-Host '============================================================' -ForegroundColor Green
    Write-Host "Installed at: $InstallPath"
    Write-Host 'The .env file and database have been preserved.'
    Write-Host ''
    Write-Host 'Note: Web server configuration, HTTPS and access from other devices must be configured separately.'
    exit 0
}
catch {
    Write-Host ''
    Write-Host "INSTALLATION FAILED: $($_.Exception.Message)" -ForegroundColor Red

    if ($script:InstallStarted -and -not $script:InstallSucceeded) {
        $marker = Join-Path $InstallPath $script:MarkerName

        if (Test-Path -LiteralPath $marker -PathType Leaf) {
            Write-Info 'Removing the incomplete installation directory so the installation can be retried.'
            Remove-Item -LiteralPath $InstallPath -Recurse -Force -ErrorAction SilentlyContinue
        }
        else {
            Write-Info 'The incomplete-install marker is missing; leaving the destination untouched for safety.'
        }
    }

    exit 1
}
