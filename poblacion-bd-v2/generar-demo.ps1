# =============================================================================
# generar-demo.ps1
# Procesa los dos exports (WooCommerce + Advanced Woo Discount Rules) desde
# ./datos/, cruza por SKU, mapea al formato _bgm_* y escribe ./salida/bgm-demo-data.js
# (window.BGM_DEMO = {...}) que consume demo-problemas-soluciones.html.
#
# Es solo ANALISIS / DRY-RUN: no escribe nada en WordPress.
# =============================================================================
$ErrorActionPreference = 'Stop'

$dataDir = Join-Path $PSScriptRoot 'datos'
$outDir  = Join-Path $PSScriptRoot 'salida'

$wcFile    = Get-ChildItem $dataDir -Filter 'wc-product-export*.csv'       | Select-Object -First 1
$rulesFile = Get-ChildItem $dataDir -Filter 'advanced-discount-rules*.csv' | Select-Object -First 1
if (-not $wcFile)    { throw "No encuentro wc-product-export*.csv en $dataDir" }
if (-not $rulesFile) { throw "No encuentro advanced-discount-rules*.csv en $dataDir" }

$w  = Import-Csv $wcFile.FullName
$rd = Import-Csv $rulesFile.FullName

# --- SKU -> filas de producto (del WC export) --------------------------------
$skuRows = @{}
foreach ($row in $w) {
    $s = $row.SKU.Trim()
    if ($s -ne '') {
        if (-not $skuRows.ContainsKey($s)) { $skuRows[$s] = New-Object System.Collections.ArrayList }
        [void]$skuRows[$s].Add($row)
    }
}

# --- Reglas bulk activas -----------------------------------------------------
$bulk = $rd | Where-Object { $_.discount_type -eq 'wdr_bulk_discount' -and $_.enabled -eq '1' }

# --- SKUs referenciados por CUALQUIER regla bulk (activa o no) ----------------
# Sirve para distinguir "regla desactivada" de "nunca tuvo regla" en sin-reglas.
$skusAnyRule = @{}
foreach ($rule in ($rd | Where-Object { $_.discount_type -eq 'wdr_bulk_discount' })) {
    try { $f = $rule.filters | ConvertFrom-Json } catch { continue }
    foreach ($k in $f.PSObject.Properties.Name) {
        if ($f.$k.type -eq 'product_sku') {
            foreach ($v in $f.$k.value) { $skusAnyRule[[string]$v] = $true }
        }
    }
}

# --- Acumular tramos por SKU -------------------------------------------------
# from < 12  -> Nivel I ; from >= 12 -> Nivel II
$skuData = @{}
foreach ($rule in $bulk) {
    $f = $rule.filters | ConvertFrom-Json
    $skus = @()
    foreach ($k in $f.PSObject.Properties.Name) {
        if ($f.$k.type -eq 'product_sku') { $skus += $f.$k.value }
    }
    $b = $rule.bulk_adjustments | ConvertFrom-Json
    foreach ($rk in $b.ranges.PSObject.Properties.Name) {
        $rg = $b.ranges.$rk
        if ($rg.value -match '^\d+(\.\d+)?$' -and [double]$rg.value -gt 0) {
            $frm = 0; if ("$($rg.from)" -match '^\d+$') { $frm = [int]$rg.from }
            $val = [double]$rg.value
            foreach ($s in $skus) {
                if (-not $skuData.ContainsKey($s)) {
                    $skuData[$s] = @{ t1_min=$null; t1_desc=$null; t2_min=$null; t2_desc=$null; reglas=@() }
                }
                if ($frm -lt 12) { $skuData[$s].t1_min = $frm; $skuData[$s].t1_desc = $val }
                else             { $skuData[$s].t2_min = $frm; $skuData[$s].t2_desc = $val }
                $skuData[$s].reglas += ("#{0} {1}" -f $rule.id, $rule.title)
            }
        }
    }
}

# --- Clasificar cada SKU -----------------------------------------------------
$clean      = New-Object System.Collections.ArrayList
$ambiguous  = New-Object System.Collections.ArrayList
$notfound   = New-Object System.Collections.ArrayList
$variations = New-Object System.Collections.ArrayList
$cSimple = 0; $cVariable = 0; $soloT1 = 0; $soloT2 = 0; $ambos = 0

foreach ($s in $skuData.Keys) {
    $d = $skuData[$s]
    if ($d.t1_desc -ne $null -and $d.t2_desc -ne $null) { $ambos++ }
    elseif ($d.t1_desc -ne $null) { $soloT1++ }
    elseif ($d.t2_desc -ne $null) { $soloT2++ }

    $rec = [ordered]@{
        sku    = $s
        t1_min = $d.t1_min; t1_desc = $d.t1_desc
        t2_min = $d.t2_min; t2_desc = $d.t2_desc
        reglas = @($d.reglas | Select-Object -Unique)
    }

    if (-not $skuRows.ContainsKey($s)) {
        $rec.estado = 'sin_match'; [void]$notfound.Add($rec); continue
    }
    $rows = $skuRows[$s]
    if ($rows.Count -gt 1) {
        $rec.estado = 'ambiguo'
        $rec.candidatos = @($rows | ForEach-Object { [ordered]@{ id=$_.ID; tipo=$_.Tipo; nombre=$_.Nombre } })
        [void]$ambiguous.Add($rec); continue
    }
    $p = $rows[0]
    $rec.id = $p.ID; $rec.tipo = $p.Tipo; $rec.nombre = $p.Nombre; $rec.precio = $p.'Precio normal'
    if ($p.Tipo -eq 'variation') {
        $rec.estado = 'variacion'; $rec.padre = $p.Superior; [void]$variations.Add($rec)
    } else {
        if ($p.Tipo -eq 'simple') { $cSimple++ } elseif ($p.Tipo -eq 'variable') { $cVariable++ }
        $rec.estado = 'ok'; [void]$clean.Add($rec)
    }
}

# --- Productos (simple/variable) que NO recibirian descuento -----------------
# Existen en el catalogo pero ninguna regla activa los cubre -> para investigar.
$sinReglas = New-Object System.Collections.ArrayList
$srSimple = 0; $srVariable = 0; $srSinSku = 0; $srDesactivada = 0; $srNunca = 0
foreach ($row in $w) {
    $tipo = $row.Tipo
    if ($tipo -ne 'simple' -and $tipo -ne 'variable') { continue }
    $s = $row.SKU.Trim()
    if ($s -ne '' -and $skuData.ContainsKey($s)) { continue }   # tiene descuento activo -> no aplica
    if     ($s -eq '')                    { $motivo = 'sin_sku';           $srSinSku++ }
    elseif ($skusAnyRule.ContainsKey($s)) { $motivo = 'regla_desactivada'; $srDesactivada++ }
    else                                  { $motivo = 'nunca_en_regla';    $srNunca++ }
    if ($tipo -eq 'simple') { $srSimple++ } else { $srVariable++ }
    [void]$sinReglas.Add([ordered]@{
        sku    = $s
        id     = $row.ID
        tipo   = $tipo
        nombre = $row.Nombre
        precio = $row.'Precio normal'
        motivo = $motivo
    })
}

$summary = [ordered]@{
    generado            = (Get-Date).ToString('yyyy-MM-dd HH:mm')
    wc_file             = $wcFile.Name
    rules_file          = $rulesFile.Name
    reglas_total        = $rd.Count
    reglas_bulk_activas = ($bulk | Measure-Object).Count
    skus_en_reglas      = $skuData.Count
    clean               = $clean.Count
    clean_simple        = $cSimple
    clean_variable      = $cVariable
    ambiguous           = $ambiguous.Count
    notfound            = $notfound.Count
    variations          = $variations.Count
    solo_t1             = $soloT1
    solo_t2             = $soloT2
    ambos               = $ambos
    sin_reglas             = $sinReglas.Count
    sin_reglas_simple      = $srSimple
    sin_reglas_variable    = $srVariable
    sin_reglas_desactivada = $srDesactivada
    sin_reglas_nunca       = $srNunca
    sin_reglas_sin_sku     = $srSinSku
}

$out = [ordered]@{
    summary    = $summary
    clean      = @($clean      | Sort-Object { [string]$_.nombre })
    ambiguous  = @($ambiguous)
    notfound   = @($notfound   | Sort-Object { [string]$_.sku })
    variations = @($variations)
    sinreglas  = @($sinReglas  | Sort-Object { [string]$_.nombre })
}

if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }
$json = $out | ConvertTo-Json -Depth 8 -Compress
Set-Content -Path (Join-Path $outDir 'bgm-demo-data.js') -Value ("window.BGM_DEMO = " + $json + ";") -Encoding UTF8

$summary.GetEnumerator() | ForEach-Object { "{0,-22} {1}" -f $_.Key, $_.Value }
Write-Output "OK -> salida/bgm-demo-data.js"
