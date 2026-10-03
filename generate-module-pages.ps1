$controllersPath = "app/Http/Controllers"
$pagesPath = "resources/js/pages"

$routeOverrides = @{
    Attendance = "attendance"
    PartSetup  = "part-setups"
}

function Convert-ToKebabCase {
    param([string]$Name)

    return ($Name -replace '([a-z0-9])([A-Z])', '$1-$2').ToLower()
}

function Convert-ToRouteName {
    param([string]$Name)

    if ($routeOverrides.ContainsKey($Name)) {
        return $routeOverrides[$Name]
    }

    $kebab = Convert-ToKebabCase $Name

    if ($kebab.EndsWith("s")) {
        return $kebab
    }

    return "${kebab}s"
}

function Convert-ToVariableName {
    param([string]$Name)

    return $Name.Substring(0, 1).ToLower() + $Name.Substring(1)
}

function Replace-FileContent {
    param(
        [string]$Path,
        [string]$ModuleName,
        [string]$ModuleVariable,
        [string]$RouteName
    )

    $content = Get-Content $Path -Raw

    $content = $content.Replace("Machines", $ModuleName)
    $content = $content.Replace("Machine", $ModuleName)
    $content = $content.Replace("machines", $RouteName)
    $content = $content.Replace("machine", $ModuleVariable)

    Set-Content -Path $Path -Value $content -NoNewline
}

$sourcePath = Join-Path $pagesPath "Machines"

$sourceIndex = Join-Path $sourcePath "Index.vue"
$sourceCreate = Join-Path $sourcePath "Create.vue"
$sourceEdit = Join-Path $sourcePath "Edit.vue"
$sourceForm = Join-Path $sourcePath "components/MachineForm.vue"

if (-not (Test-Path $sourceIndex)) {
    Write-Host "ERROR: Machines/Index.vue was not found." -ForegroundColor Red
    exit 1
}

$controllers = Get-ChildItem `
    $controllersPath `
    -Filter "*Controller.php" `
    -Recurse

foreach ($controller in $controllers) {

    if ($controller.Name -eq "Controller.php") {
        continue
    }

    if ($controller.Name -eq "MachineController.php") {
        continue
    }

    $controllerContent = Get-Content `
        $controller.FullName `
        -Raw

    if ($controllerContent -notmatch 'class\s+(\w+)Controller\s+extends') {
        continue
    }

    $moduleName = $Matches[1]

    $moduleVariable = Convert-ToVariableName $moduleName
    $routeName = Convert-ToRouteName $moduleName

    $modulePath = Join-Path `
        $pagesPath `
        $moduleName

    $componentsPath = Join-Path `
        $modulePath `
        "components"

    New-Item `
        -ItemType Directory `
        -Path $componentsPath `
        -Force |
        Out-Null

    $destinationIndex = Join-Path `
        $modulePath `
        "Index.vue"

    $destinationCreate = Join-Path `
        $modulePath `
        "Create.vue"

    $destinationEdit = Join-Path `
        $modulePath `
        "Edit.vue"

    $destinationForm = Join-Path `
        $componentsPath `
        "${moduleName}Form.vue"

    $files = @(
        @{
            Source = $sourceIndex
            Destination = $destinationIndex
        },
        @{
            Source = $sourceCreate
            Destination = $destinationCreate
        },
        @{
            Source = $sourceEdit
            Destination = $destinationEdit
        },
        @{
            Source = $sourceForm
            Destination = $destinationForm
        }
    )

    foreach ($file in $files) {

        if (Test-Path $file.Destination) {
            Write-Host `
                "Skipped: $($file.Destination) already exists." `
                -ForegroundColor Yellow

            continue
        }

        Copy-Item `
            $file.Source `
            $file.Destination

        Replace-FileContent `
            -Path $file.Destination `
            -ModuleName $moduleName `
            -ModuleVariable $moduleVariable `
            -RouteName $routeName

        Write-Host `
            "Created: $($file.Destination)" `
            -ForegroundColor Green
    }

    Write-Host ""
}

Write-Host "----------------------------------------" -ForegroundColor Cyan
Write-Host "Module page generation completed." -ForegroundColor Cyan
Write-Host "----------------------------------------" -ForegroundColor Cyan