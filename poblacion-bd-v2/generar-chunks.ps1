# =============================================================================
# generar-chunks.ps1
# Trocea salida/bgm-import-v2.csv en planillas de N PRODUCTOS c/u para subir por
# lotes (Ruta C). Reglas de oro:
#   - Corta POR PRODUCTO: cada variable viaja con TODAS sus variaciones en la
#     misma planilla, y el padre va ANTES que sus variaciones.
#   - Repite el header completo (con BOM) en cada planilla -> cada una es un CSV
#     valido e independiente para el importador de WooCommerce.
# Escribe en ./planillas/ + un _MANIFIESTO.txt para llevar la cuenta.
# =============================================================================
$ErrorActionPreference = 'Stop'
$proj   = $PSScriptRoot
$salida = Join-Path $proj 'salida'
$outDir = Join-Path $proj 'planillas'
$master = Join-Path $salida 'bgm-import-v2.csv'
$chunkSize = 200   # productos de nivel superior por planilla

if (-not (Test-Path $master)) { throw "Falta $master (corre generar-import.ps1 primero)" }
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }
Get-ChildItem $outDir -Filter '*.csv' -ErrorAction SilentlyContinue | Remove-Item -Force

$rows = Import-Csv $master
$enc  = New-Object System.Text.UTF8Encoding $true   # UTF-8 con BOM

# --- Indexar variaciones por SKU del padre (Superior) ------------------------
$varsByParent = @{}
foreach ($r in $rows) {
  if ($r.Tipo -eq 'variation') {
    $p = $r.Superior.Trim()
    if (-not $varsByParent.ContainsKey($p)) { $varsByParent[$p] = New-Object System.Collections.ArrayList }
    [void]$varsByParent[$p].Add($r)
  }
}

# --- Construir UNIDADES (producto + sus variaciones) en orden de archivo -----
$units = New-Object System.Collections.ArrayList
$consumed = @{}
foreach ($r in $rows) {
  if ($r.Tipo -eq 'variation') { continue }
  $unit = New-Object System.Collections.ArrayList
  [void]$unit.Add($r)
  $sku = $r.SKU.Trim()
  if ($sku -ne '' -and $varsByParent.ContainsKey($sku)) {
    foreach ($v in $varsByParent[$sku]) { [void]$unit.Add($v) }
    $consumed[$sku] = $true
  }
  [void]$units.Add($unit)
}

# --- Variaciones huerfanas (padre no esta como producto) ---------------------
$orphans = New-Object System.Collections.ArrayList
foreach ($p in $varsByParent.Keys) {
  if (-not $consumed.ContainsKey($p)) { foreach ($v in $varsByParent[$p]) { [void]$orphans.Add($v) } }
}

# --- Escribir planillas -------------------------------------------------------
$nChunks = [math]::Ceiling($units.Count / $chunkSize)
$man = New-Object System.Collections.ArrayList
[void]$man.Add("MANIFIESTO DE PLANILLAS - poblacion V2")
[void]$man.Add("Generado: " + (Get-Date).ToString('yyyy-MM-dd HH:mm'))
[void]$man.Add("Origen:   salida\bgm-import-v2.csv")
[void]$man.Add("Tamano:   $chunkSize productos por planilla")
[void]$man.Add("Al importar: marcar 'Actualizar productos existentes'.")
[void]$man.Add("------------------------------------------------------------")
[void]$man.Add(("{0,-20} {1,9} {2,7}  hecho" -f 'Planilla','Productos','Filas'))

$totProd = 0; $totRows = 0
for ($ci = 0; $ci -lt $nChunks; $ci++) {
  $from = $ci * $chunkSize
  $to   = [math]::Min(($ci + 1) * $chunkSize, $units.Count) - 1
  $slice = $units[$from..$to]
  $chunkRows = foreach ($u in $slice) { foreach ($r in $u) { $r } }
  $lines = $chunkRows | ConvertTo-Csv -NoTypeInformation
  $name  = "planilla-{0:D2}.csv" -f ($ci + 1)
  [System.IO.File]::WriteAllLines((Join-Path $outDir $name), $lines, $enc)
  $totProd += @($slice).Count; $totRows += @($chunkRows).Count
  [void]$man.Add(("{0,-20} {1,9} {2,7}  [ ]" -f $name, @($slice).Count, @($chunkRows).Count))
}

[void]$man.Add("------------------------------------------------------------")
[void]$man.Add(("{0,-20} {1,9} {2,7}" -f 'TOTAL', $totProd, $totRows))
if ($orphans.Count -gt 0) {
  $on = 'planilla-99-huerfanas.csv'
  $ol = $orphans | ConvertTo-Csv -NoTypeInformation
  [System.IO.File]::WriteAllLines((Join-Path $outDir $on), $ol, $enc)
  [void]$man.Add("")
  [void]$man.Add("AVISO: $($orphans.Count) variaciones huerfanas (padre ausente) en $on - revisar aparte.")
}
[System.IO.File]::WriteAllText((Join-Path $outDir '_MANIFIESTO.txt'), ($man -join "`r`n"), $enc)

Write-Output ("Planillas escritas: {0}  en planillas\" -f $nChunks)
Write-Output ("Productos: {0}  | Filas (con variaciones): {1}  | Huerfanas: {2}" -f $totProd, $totRows, $orphans.Count)
Write-Output "Ver planillas\_MANIFIESTO.txt para el detalle y checklist."
