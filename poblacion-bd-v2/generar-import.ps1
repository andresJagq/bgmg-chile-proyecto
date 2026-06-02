# =============================================================================
# generar-import.ps1
# Construye el CSV de importacion para la V2 limpia.
#   - Toma datos/wc-product-export*.csv (catalogo completo de V1).
#   - Inyecta las 5 columnas Meta: _bgm_* (precios mayoristas) cruzando por SKU
#     contra salida/bgm-demo-data.js (set 'clean', que ya excluye los huerfanos).
#   - RECORTE LIMPIO: bota la columna ID, 'Swatches Attributes' y TODAS las
#     columnas 'Meta: *' de plugins de V1 (Elementor, Facebook, Google, Yoast,
#     woosea, Neve, Jetpack, etc.). Conserva el resto de columnas core de WC.
#   - Escribe salida/bgm-import-v2.csv (UTF-8 con BOM, igual que el export de WC).
#
# Tecnica anti-acentos: NO se teclean nombres de columnas con tildes; se descartan
# por patron y se conserva el resto, evitando que PS 5.1 corrompa los literales.
#
# Dry-run de archivos: NO toca WordPress.
# =============================================================================
$ErrorActionPreference = 'Stop'
$proj   = $PSScriptRoot
$datos  = Join-Path $proj 'datos'
$salida = Join-Path $proj 'salida'

$wcFile = Get-ChildItem $datos -Filter 'wc-product-export*.csv' | Select-Object -First 1
if (-not $wcFile) { throw "No encuentro wc-product-export*.csv en $datos" }

# --- Mapa SKU -> tiers, desde el demo (clean ya excluye los 663 huerfanos) ----
$js   = Get-Content (Join-Path $salida 'bgm-demo-data.js') -Raw -Encoding UTF8
$json = $js -replace '^\s*window\.BGM_DEMO\s*=\s*','' -replace ';\s*$',''
$demo = $json | ConvertFrom-Json
$map = @{}
foreach ($c in $demo.clean) {
  $map[[string]$c.sku] = @{ min1=$c.t1_min; desc1=$c.t1_desc; min2=$c.t2_min; desc2=$c.t2_desc }
}

function IntOrBlank($v){ if ($null -ne $v -and "$v" -ne '') { [int][double]$v } else { '' } }

# --- Importar y decidir columnas a conservar (por descarte, sin teclear acentos) ---
$w     = Import-Csv $wcFile.FullName
$cols  = $w[0].PSObject.Properties.Name
$drop  = @('ID','Swatches Attributes')
$keep  = $cols | Where-Object { $_ -notin $drop -and $_ -notlike 'Meta: *' }

$nFilled = 0
$out = foreach ($row in $w) {
  $o = [ordered]@{}
  foreach ($k in $keep) { $o[$k] = $row.$k }
  $sku = $row.SKU.Trim()
  if ($sku -ne '' -and $map.ContainsKey($sku)) {
    $t = $map[$sku]
    $o['Meta: _bgm_min_1']          = (IntOrBlank $t.min1)
    $o['Meta: _bgm_descuento_1']    = (IntOrBlank $t.desc1)
    $o['Meta: _bgm_min_2']          = (IntOrBlank $t.min2)
    $o['Meta: _bgm_descuento_2']    = (IntOrBlank $t.desc2)
    $o['Meta: _bgm_modo_descuento'] = 'unico'
    $nFilled++
  } else {
    $o['Meta: _bgm_min_1']          = ''
    $o['Meta: _bgm_descuento_1']    = ''
    $o['Meta: _bgm_min_2']          = ''
    $o['Meta: _bgm_descuento_2']    = ''
    $o['Meta: _bgm_modo_descuento'] = ''
  }
  [pscustomobject]$o
}

# --- Escribir UTF-8 con BOM (mismo formato que el export de WC) ----------------
$lines   = $out | ConvertTo-Csv -NoTypeInformation
$outFile = Join-Path $salida 'bgm-import-v2.csv'
[System.IO.File]::WriteAllLines($outFile, $lines, (New-Object System.Text.UTF8Encoding $true))

Write-Output ("Origen:                {0}" -f $wcFile.Name)
Write-Output ("Filas escritas:        {0}" -f @($out).Count)
Write-Output ("Columnas conservadas:  {0} core + 5 _bgm_ = {1}" -f $keep.Count, ($keep.Count + 5))
Write-Output ("Columnas botadas:      {0} (ID + Swatches + metas de plugins)" -f ($cols.Count - $keep.Count))
Write-Output ("Productos con _bgm_:   {0}  (esperado 1237)" -f $nFilled)
Write-Output ("Archivo:               salida\bgm-import-v2.csv")
