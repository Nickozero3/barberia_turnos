$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root

$requiredFiles = @(
    "public\assets\css\app.css",
    "public\assets\js\theme.js",
    "public\_header.php",
    "public\admin\_header.php",
    "public\admin\login.php",
    "docker-compose.yml"
)

foreach ($file in $requiredFiles) {
    if (-not (Test-Path (Join-Path $root $file))) {
        throw "No se encontro $file. Extrae el parche dentro de la carpeta principal de fioreee_barber y acepta reemplazar los archivos."
    }
}

Write-Host "Reconstruyendo solamente el contenedor web..."
docker compose up -d --build web

if ($LASTEXITCODE -ne 0) {
    throw "No se pudo reconstruir la aplicacion. Revisa que Docker Desktop este iniciado."
}

Write-Host "Tema oscuro instalado correctamente."
Write-Host "No se borraron turnos, clientes ni configuraciones."
