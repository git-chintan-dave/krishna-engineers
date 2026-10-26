#requires -Version 5.1

$ErrorActionPreference = 'Stop'

# ------------------------------------------------------------
# Startup diagnostics
# ------------------------------------------------------------

$script:StartupLog = Join-Path $env:TEMP 'KrishnaEngineers-Launcher.log'

function Write-StartupLog {
    param([string]$Message)

    try {
        $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
        Add-Content -LiteralPath $script:StartupLog -Value "[$timestamp] $Message" -ErrorAction SilentlyContinue
    }
    catch {
        # Startup logging must never prevent the launcher from opening.
    }
}

try {
    Write-StartupLog 'Launcher starting.'

    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing

    [System.Windows.Forms.Application]::EnableVisualStyles()

    # --------------------------------------------------------
    # Administrator elevation
    # --------------------------------------------------------

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)

    if (-not $principal.IsInRole(
        [Security.Principal.WindowsBuiltInRole]::Administrator
    )) {
        Write-StartupLog 'Requesting administrator elevation.'

        $arguments = '-NoLogo -NoProfile -ExecutionPolicy Bypass -File "{0}"' -f $PSCommandPath

        Start-Process `
            -FilePath 'powershell.exe' `
            -ArgumentList $arguments `
            -Verb RunAs `
            -WindowStyle Hidden

        exit
    }

    # --------------------------------------------------------
    # Paths
    # --------------------------------------------------------

    $script:ProjectRoot = $PSScriptRoot
    $script:InstallerPath = Join-Path $PSScriptRoot 'installer'

    $script:InstallScript = Join-Path `
        $script:InstallerPath `
        'Install-KrishnaEngineers.ps1'

    $script:UpdateScript = Join-Path `
        $script:InstallerPath `
        'Update-KrishnaEngineers.ps1'

    # --------------------------------------------------------
    # Theme - app.css values
    # --------------------------------------------------------

    $script:Theme = @{
        Background = '#FFFFFF'
        Foreground = '#0A0A0A'
        Card       = '#FDFDFD'
        Primary    = '#171717'
        Secondary  = '#FAFAFA'
        Hover      = '#EEEEEE'
        Muted      = '#737373'
        Border     = '#E5E5E5'
        Success    = '#166534'
        Danger     = '#B91C1C'
    }

    function Get-ThemeColor {
        param([string]$Name)

        return [System.Drawing.ColorTranslator]::FromHtml(
            $script:Theme[$Name]
        )
    }

    # --------------------------------------------------------
    # Shared state
    # --------------------------------------------------------

    $script:CurrentProcess = $null
    $script:CurrentOperation = ''
    $script:IsRunning = $false
    $script:ProcessTimer = $null

    $script:StdOutPath = $null
    $script:StdErrPath = $null
    $script:StdOutLines = 0
    $script:StdErrLines = 0

    # --------------------------------------------------------
    # UI helpers
    # --------------------------------------------------------

    function New-Label {
        param(
            [Parameter(Mandatory)]
            [string]$Text,

            [int]$X = 0,
            [int]$Y = 0,
            [int]$Width = 200,
            [int]$Height = 24,
            [float]$FontSize = 10,
            [string]$Color = 'Foreground',

            [System.Drawing.FontStyle]$FontStyle =
                [System.Drawing.FontStyle]::Regular
        )

        $label = New-Object System.Windows.Forms.Label
        $label.Text = $Text
        $label.Location = New-Object System.Drawing.Point($X, $Y)
        $label.Size = New-Object System.Drawing.Size($Width, $Height)
        $label.ForeColor = Get-ThemeColor $Color
        $label.BackColor = [System.Drawing.Color]::Transparent
        $label.Font = New-Object System.Drawing.Font(
            'Segoe UI',
            $FontSize,
            $FontStyle
        )
        $label.AutoEllipsis = $true

        return $label
    }

    function Add-UiLabel {
        param(
            [Parameter(Mandatory)]
            [System.Windows.Forms.Control]$Parent,

            [Parameter(Mandatory)]
            [string]$Text,

            [int]$X = 0,
            [int]$Y = 0,
            [int]$Width = 200,
            [int]$Height = 24,
            [float]$FontSize = 10,
            [string]$Color = 'Foreground',

            [System.Drawing.FontStyle]$FontStyle =
                [System.Drawing.FontStyle]::Regular
        )

        $label = New-Label `
            -Text $Text `
            -X $X `
            -Y $Y `
            -Width $Width `
            -Height $Height `
            -FontSize $FontSize `
            -Color $Color `
            -FontStyle $FontStyle

        [void]$Parent.Controls.Add($label)
        return $label
    }

    function New-Button {
        param(
            [Parameter(Mandatory)]
            [string]$Text,

            [int]$X = 0,
            [int]$Y = 0,
            [int]$Width = 120,
            [int]$Height = 38,
            [string]$Background = 'Primary',
            [string]$Foreground = 'Background'
        )

        $button = New-Object System.Windows.Forms.Button
        $button.Text = $Text
        $button.Location = New-Object System.Drawing.Point($X, $Y)
        $button.Size = New-Object System.Drawing.Size($Width, $Height)

        $button.BackColor = Get-ThemeColor $Background
        $button.ForeColor = Get-ThemeColor $Foreground
        $button.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
        $button.FlatAppearance.BorderSize = 0
        $button.FlatAppearance.MouseOverBackColor = Get-ThemeColor 'Hover'
        $button.FlatAppearance.MouseDownBackColor = Get-ThemeColor 'Border'

        $button.Font = New-Object System.Drawing.Font(
            'Segoe UI',
            9.5,
            [System.Drawing.FontStyle]::Bold
        )

        $button.Cursor = [System.Windows.Forms.Cursors]::Hand
        $button.UseVisualStyleBackColor = $false

        return $button
    }

    function Add-Log {
        param(
            [AllowEmptyString()]
            [string]$Message
        )

        if ([string]::IsNullOrWhiteSpace($Message)) {
            return
        }

        $timestamp = Get-Date -Format 'HH:mm:ss'
        $line = "[$timestamp] $Message"

        if ($null -ne $script:LogBox -and -not $script:LogBox.IsDisposed) {
            $script:LogBox.AppendText($line + [Environment]::NewLine)
            $script:LogBox.SelectionStart = $script:LogBox.TextLength
            $script:LogBox.ScrollToCaret()
        }

        Write-StartupLog $Message
    }

    function Set-Status {
        param(
            [Parameter(Mandatory)]
            [string]$Message,

            [string]$Color = 'Muted'
        )

        $script:StatusLabel.Text = $Message
        $script:StatusLabel.ForeColor = Get-ThemeColor $Color
    }

    function Set-ButtonsRunning {
        param([bool]$Running)

        if ($Running) {
            $script:InstallButton.Text = 'Installing...'
            $script:UpdateButton.Text = 'Updating...'

            $script:InstallButton.BackColor = Get-ThemeColor 'Primary'
            $script:InstallButton.ForeColor = Get-ThemeColor 'Background'

            $script:UpdateButton.BackColor = Get-ThemeColor 'Secondary'
            $script:UpdateButton.ForeColor = Get-ThemeColor 'Foreground'
        }
        else {
            $script:InstallButton.Text = 'Install'
            $script:UpdateButton.Text = 'Update'

            $script:InstallButton.BackColor = Get-ThemeColor 'Primary'
            $script:InstallButton.ForeColor = Get-ThemeColor 'Background'

            $script:UpdateButton.BackColor = Get-ThemeColor 'Secondary'
            $script:UpdateButton.ForeColor = Get-ThemeColor 'Foreground'
        }
    }

    # --------------------------------------------------------
    # Completion and alerts
    # --------------------------------------------------------

    function Complete-Operation {
        param([int]$ExitCode)

        if (-not $script:IsRunning) {
            return
        }

        $operation = $script:CurrentOperation
        $success = $ExitCode -eq 0

        $script:ProcessTimer.Stop()
        $script:IsRunning = $false

        if ($null -ne $script:CurrentProcess) {
            $script:CurrentProcess.Dispose()
            $script:CurrentProcess = $null
        }

        if ($success) {
            Set-Status "$operation completed successfully." 'Success'
            Add-Log ''
            Add-Log "$operation completed successfully."

            $message = "Krishna Engineers $($operation.ToLower()) completed successfully.`r`n`r`nClick OK to close this window."

            $icon = [System.Windows.Forms.MessageBoxIcon]::Information
            $title = 'Krishna Engineers - Success'
        }
        else {
            Set-Status "$operation failed (exit code $ExitCode)." 'Danger'
            Add-Log ''
            Add-Log "$operation failed with exit code $ExitCode."

            $message = "Krishna Engineers $($operation.ToLower()) failed.`r`n`r`nExit code: $ExitCode`r`n`r`nReview the activity log for details.`r`n`r`nClick OK to close this window."

            $icon = [System.Windows.Forms.MessageBoxIcon]::Error
            $title = 'Krishna Engineers - Error'
        }

        [System.Windows.Forms.MessageBox]::Show(
            $script:Form,
            $message,
            $title,
            [System.Windows.Forms.MessageBoxButtons]::OK,
            $icon
        ) | Out-Null

        $script:Form.Close()
    }

    # --------------------------------------------------------
    # Launch installer/updater
    # --------------------------------------------------------

    function Start-Operation {
        param(
            [Parameter(Mandatory)]
            [ValidateSet('Install', 'Update')]
            [string]$Operation
        )

        if ($script:IsRunning) {
            return
        }

        if ($Operation -eq 'Install') {
            $scriptPath = $script:InstallScript
        }
        else {
            $scriptPath = $script:UpdateScript
        }

        if (-not (Test-Path -LiteralPath $scriptPath -PathType Leaf)) {
            $message = "The $Operation script was not found:`r`n`r`n$scriptPath"

            [System.Windows.Forms.MessageBox]::Show(
                $script:Form,
                $message,
                'Krishna Engineers - Error',
                [System.Windows.Forms.MessageBoxButtons]::OK,
                [System.Windows.Forms.MessageBoxIcon]::Error
            ) | Out-Null

            $script:Form.Close()
            return
        }

        $confirmation = [System.Windows.Forms.MessageBox]::Show(
            $script:Form,
            "Do you want to start $($Operation.ToLower())?",
            "Krishna Engineers - $Operation",
            [System.Windows.Forms.MessageBoxButtons]::YesNo,
            [System.Windows.Forms.MessageBoxIcon]::Question
        )

        if ($confirmation -ne [System.Windows.Forms.DialogResult]::Yes) {
            return
        }

        $script:IsRunning = $true
        $script:CurrentOperation = $Operation

        Set-ButtonsRunning -Running $true -Operation $Operation

        $script:LogBox.Clear()

        Set-Status "$Operation is running..." 'Primary'

        Add-Log "Starting $Operation..."
        Add-Log "Script: $scriptPath"
        Add-Log ''

        try {
            $powershellPath = Join-Path `
                $env:SystemRoot `
                'System32\WindowsPowerShell\v1.0\powershell.exe'

            $logId = [guid]::NewGuid().ToString('N')
            $tempPath = [System.IO.Path]::GetTempPath()

            $script:StdOutPath = Join-Path $tempPath "KrishnaEngineers-$logId-out.log"
            $script:StdErrPath = Join-Path $tempPath "KrishnaEngineers-$logId-err.log"

            [System.IO.File]::WriteAllText($script:StdOutPath, '')
            [System.IO.File]::WriteAllText($script:StdErrPath, '')

            $script:StdOutLines = 0
            $script:StdErrLines = 0

            $arguments = '-NoLogo -NoProfile -ExecutionPolicy Bypass -File "{0}"' -f $scriptPath

            Add-Log 'Launching PowerShell process...'

            $script:CurrentProcess = Start-Process `
                -FilePath $powershellPath `
                -ArgumentList $arguments `
                -WorkingDirectory $script:ProjectRoot `
                -WindowStyle Hidden `
                -RedirectStandardOutput $script:StdOutPath `
                -RedirectStandardError $script:StdErrPath `
                -PassThru `
                -ErrorAction Stop

            Add-Log "Process ID: $($script:CurrentProcess.Id)"
            Add-Log 'Waiting for script output...'

            $script:ProcessTimer.Start()
        }
        catch {
            $errorMessage = $_.Exception.Message

            Add-Log "Failed to start the script: $errorMessage"

            $script:IsRunning = $false

            Set-ButtonsRunning -Running $false
            Set-Status 'Unable to start the script.' 'Danger'

            [System.Windows.Forms.MessageBox]::Show(
                $script:Form,
                "Unable to start $($Operation.ToLower()).`r`n`r`n$errorMessage`r`n`r`nClick OK to close.",
                'Krishna Engineers - Error',
                [System.Windows.Forms.MessageBoxButtons]::OK,
                [System.Windows.Forms.MessageBoxIcon]::Error
            ) | Out-Null

            $script:Form.Close()
        }
    }

    # --------------------------------------------------------
    # Main window
    # --------------------------------------------------------

    $script:Form = New-Object System.Windows.Forms.Form
    $script:Form.Text = 'Krishna Engineers'
    $script:Form.StartPosition = [System.Windows.Forms.FormStartPosition]::CenterScreen
    $script:Form.ClientSize = New-Object System.Drawing.Size(760, 590)
    $script:Form.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::FixedSingle
    $script:Form.MaximizeBox = $false
    $script:Form.MinimizeBox = $true
    $script:Form.BackColor = Get-ThemeColor 'Background'
    $script:Form.ForeColor = Get-ThemeColor 'Foreground'
    $script:Form.Font = New-Object System.Drawing.Font('Segoe UI', 9)

    # Header
    $header = New-Object System.Windows.Forms.Panel
    $header.Location = New-Object System.Drawing.Point(0, 0)
    $header.Size = New-Object System.Drawing.Size(760, 100)
    $header.BackColor = Get-ThemeColor 'Card'

    [void]$script:Form.Controls.Add($header)

    Add-UiLabel -Parent $header -Text 'KRISHNA ENGINEERS' `
        -X 24 -Y 18 -Width 690 -Height 34 `
        -FontSize 19 -Color 'Foreground' `
        -FontStyle ([System.Drawing.FontStyle]::Bold) | Out-Null

    Add-UiLabel -Parent $header -Text 'APPLICATION SETUP MANAGER' `
        -X 26 -Y 56 -Width 690 -Height 22 `
        -FontSize 9 -Color 'Muted' | Out-Null

    # Buttons
    $script:InstallButton = New-Button `
        -Text 'Install' -X 24 -Y 118 -Width 170 -Height 42

    [void]$script:Form.Controls.Add($script:InstallButton)

    $script:UpdateButton = New-Button `
        -Text 'Update' -X 208 -Y 118 -Width 170 -Height 42 `
        -Background 'Secondary' -Foreground 'Foreground'

    [void]$script:Form.Controls.Add($script:UpdateButton)

    Add-UiLabel -Parent $script:Form `
        -Text 'Choose an action to run the corresponding setup script.' `
        -X 24 -Y 174 -Width 700 -Height 24 `
        -FontSize 9 -Color 'Muted' | Out-Null

    Add-UiLabel -Parent $script:Form -Text 'ACTIVITY LOG' `
        -X 24 -Y 210 -Width 700 -Height 24 `
        -FontSize 10 -Color 'Foreground' `
        -FontStyle ([System.Drawing.FontStyle]::Bold) | Out-Null

    # Output
    $script:LogBox = New-Object System.Windows.Forms.RichTextBox
    $script:LogBox.Location = New-Object System.Drawing.Point(24, 240)
    $script:LogBox.Size = New-Object System.Drawing.Size(712, 260)
    $script:LogBox.ReadOnly = $true
    $script:LogBox.DetectUrls = $false
    $script:LogBox.BorderStyle = [System.Windows.Forms.BorderStyle]::FixedSingle
    $script:LogBox.BackColor = Get-ThemeColor 'Secondary'
    $script:LogBox.ForeColor = Get-ThemeColor 'Foreground'
    $script:LogBox.Font = New-Object System.Drawing.Font('Consolas', 9)
    $script:LogBox.WordWrap = $true
    $script:LogBox.HideSelection = $false

    [void]$script:Form.Controls.Add($script:LogBox)

    # Status
    $statusPanel = New-Object System.Windows.Forms.Panel
    $statusPanel.Location = New-Object System.Drawing.Point(0, 520)
    $statusPanel.Size = New-Object System.Drawing.Size(760, 46)
    $statusPanel.BackColor = Get-ThemeColor 'Card'

    [void]$script:Form.Controls.Add($statusPanel)

    $script:StatusLabel = New-Label -Text 'Ready' `
        -X 24 -Y 12 -Width 700 -Height 22 `
        -FontSize 9 -Color 'Muted'

    [void]$statusPanel.Controls.Add($script:StatusLabel)

    # --------------------------------------------------------
    # Process output polling
    # --------------------------------------------------------

    $script:ProcessTimer = New-Object System.Windows.Forms.Timer
    $script:ProcessTimer.Interval = 250

    $script:ProcessTimer.add_Tick({
        try {
            # Standard output
            if (
                $script:StdOutPath -and
                (Test-Path -LiteralPath $script:StdOutPath)
            ) {
                $lines = @(Get-Content -LiteralPath $script:StdOutPath -ErrorAction SilentlyContinue)

                while ($script:StdOutLines -lt $lines.Count) {
                    Add-Log $lines[$script:StdOutLines]
                    $script:StdOutLines++
                }
            }

            # Standard error
            if (
                $script:StdErrPath -and
                (Test-Path -LiteralPath $script:StdErrPath)
            ) {
                $lines = @(Get-Content -LiteralPath $script:StdErrPath -ErrorAction SilentlyContinue)

                while ($script:StdErrLines -lt $lines.Count) {
                    Add-Log "[stderr] $($lines[$script:StdErrLines])"
                    $script:StdErrLines++
                }
            }

            if (
                $null -ne $script:CurrentProcess -and
                $script:CurrentProcess.HasExited
            ) {
                $exitCode = $script:CurrentProcess.ExitCode

                # Read the final output once more before the alert.
                if (
                    $script:StdOutPath -and
                    (Test-Path -LiteralPath $script:StdOutPath)
                ) {
                    $lines = @(Get-Content -LiteralPath $script:StdOutPath -ErrorAction SilentlyContinue)

                    while ($script:StdOutLines -lt $lines.Count) {
                        Add-Log $lines[$script:StdOutLines]
                        $script:StdOutLines++
                    }
                }

                if (
                    $script:StdErrPath -and
                    (Test-Path -LiteralPath $script:StdErrPath)
                ) {
                    $lines = @(Get-Content -LiteralPath $script:StdErrPath -ErrorAction SilentlyContinue)

                    while ($script:StdErrLines -lt $lines.Count) {
                        Add-Log "[stderr] $($lines[$script:StdErrLines])"
                        $script:StdErrLines++
                    }
                }

                Complete-Operation -ExitCode $exitCode
            }
        }
        catch {
            $script:ProcessTimer.Stop()

            $errorMessage = $_.Exception.Message
            Write-StartupLog "Monitoring error: $errorMessage"
            Add-Log "Launcher monitoring error: $errorMessage"

            $script:IsRunning = $false

            [System.Windows.Forms.MessageBox]::Show(
                $script:Form,
                "An error occurred while monitoring the script.`r`n`r`n$errorMessage`r`n`r`nClick OK to close.",
                'Krishna Engineers - Error',
                [System.Windows.Forms.MessageBoxButtons]::OK,
                [System.Windows.Forms.MessageBoxIcon]::Error
            ) | Out-Null

            $script:Form.Close()
        }
    })

    # Button events
    $script:InstallButton.Add_Click({
        Start-Operation -Operation 'Install'
    })

    $script:UpdateButton.Add_Click({
        Start-Operation -Operation 'Update'
    })

    # Prevent closing while a script is running.
    $script:Form.add_FormClosing({
        param($sender, $eventArgs)

        if ($script:IsRunning) {
            $eventArgs.Cancel = $true
        }
    })

    Write-StartupLog 'Showing launcher window.'

    [void]$script:Form.ShowDialog()

    # --------------------------------------------------------
    # Cleanup
    # --------------------------------------------------------

    if ($null -ne $script:ProcessTimer) {
        $script:ProcessTimer.Stop()
        $script:ProcessTimer.Dispose()
    }

    if ($null -ne $script:CurrentProcess) {
        $script:CurrentProcess.Dispose()
    }

    foreach ($logPath in @($script:StdOutPath, $script:StdErrPath)) {
        if (
            -not [string]::IsNullOrWhiteSpace($logPath) -and
            (Test-Path -LiteralPath $logPath)
        ) {
            Remove-Item -LiteralPath $logPath -Force -ErrorAction SilentlyContinue
        }
    }

    $script:Form.Dispose()
    Write-StartupLog 'Launcher closed.'
}
catch {
    $message = $_.Exception.Message
    $details = $_ | Out-String

    Write-StartupLog "FATAL ERROR: $message"
    Write-StartupLog $details

    try {
        [System.Windows.Forms.MessageBox]::Show(
            "Krishna Engineers launcher encountered an error.`r`n`r`n$message`r`n`r`nDiagnostic log:`r`n$script:StartupLog",
            'Krishna Engineers - Launcher Error',
            [System.Windows.Forms.MessageBoxButtons]::OK,
            [System.Windows.Forms.MessageBoxIcon]::Error
        ) | Out-Null
    }
    catch {
        Write-Host $details
        Read-Host 'Press Enter to close'
    }
}
