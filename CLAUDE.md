# BGMG Chile — Contexto para Claude

> **Regla #1:** Toda la información persistente del proyecto vive DENTRO de esta carpeta
> (`bgmg-chile-proyecto/`), que se sincroniza por Google Drive entre PCs.
> **NO usar la memoria nativa de Claude** — esa se guarda en `C:\...\.claude\` (local a cada
> PC y dependiente del directorio desde el que se abra Claude), por lo que NO viaja por Drive
> y se fragmenta. Si hay que recordar algo del proyecto, va en un archivo de esta carpeta.

## Qué es
Sitio WooCommerce chileno **`new.beautygirlmg.cl`** (V2, en construcción). 3 plugins propios
+ 1 tema base. Descripción completa y orden de instalación en `README-EMPEZAR-AQUI.md`.

## Dónde estamos (2026-05-31)
- **Entorno: staging.** Fase: **optimizar/pulir los 3 plugins** antes de la migración V1→V2
  (paso posterior). La migración NO es lo primero todavía.
- **Auditoría de los 3 plugins: COMPLETA** (sin hallazgos críticos/altos). Plan en
  `AUDITORIA-OPTIMIZACION.md`.
- **Estado vivo, pendientes y conocimiento del proyecto → `HANDOFF.md`** (doc único; reemplaza los
  handoffs fechados, ahora en `historial/`).
- **Última entrega:** **bgmg-landing 6.5.3** — **BL-01c COMPLETO** (Fase 1 CSS+markup + Fase 2 JS de lupa/carrito a global; 1 copia en vez de 8) + template **404 branded** + BL-01c
  Fase 1 (minicart a global, validado). También **mayorista 2.5.4** (BM-01 rate-limit) y **bgmg-chile
  1.18.2** (quick wins). Ver `HANDOFF.md` §1–§3.

## Versiones actuales del CÓDIGO
| Pieza | Versión |
|---|---|
| bgmg-chile | **1.18.3** |
| bgmg-landing | **6.7.5** |
| beautygirlmg-mayorista | **2.7.4** |
| bgmg-tema-base | 1.1.0 |

> bgmg-landing versiona en 2 sitios: header del plugin + constante `BGMG_LANDING_VERSION`
> (úsala como cache-buster en los `wp_enqueue_*`). Subir SIEMPRE la versión al cambiar PHP/CSS/JS.

## Próximo paso al retomar
Ver `HANDOFF.md` §2–§3. BL-01c Fase 1 (CSS+markup del minicart a global) **hecha y validada** en
6.5.0. Decisión abierta: hacer **BL-01c Fase 2** (globalizar el JS de abrir/cerrar — DRY menor, más
riesgo) o pasar a lo de mayor valor para lanzar: los 2 ajustes 🔴 de wp-admin + **script de
migración V1→V2** (+ BC-03 caché de stats).

## Cómo regenerar zips
**NO usar `Compress-Archive`** (genera backslashes y rompe la instalación en WordPress). Usar este
script (escribe forward-slashes; las rutas asumen este `$root`):

```powershell
function New-PluginZip {
  param([string]$SourceDir, [string]$EntryPrefix, [string]$ZipPath)
  Add-Type -AssemblyName System.IO.Compression.FileSystem | Out-Null
  if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
  $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, 'Create')
  try {
    $base = (Resolve-Path $SourceDir).Path.TrimEnd('\','/')
    Get-ChildItem -Path $base -Recurse -File | ForEach-Object {
      $rel = $_.FullName.Substring($base.Length).TrimStart('\','/').Replace('\','/')
      [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, "$EntryPrefix/$rel", [System.IO.Compression.CompressionLevel]::Optimal)
    }
  } finally { $zip.Dispose() }
}
$root = 'G:\Otros ordenadores\Mi portátil\Google drive proyectos\bgmg-chile-proyecto'
New-PluginZip "$root\plugins\bgmg-chile"             "bgmg-chile"             "$root\zips\bgmg-chile.zip"
New-PluginZip "$root\plugins\bgmg-landing"           "bgmg-landing"           "$root\zips\bgmg-landing.zip"
New-PluginZip "$root\plugins\beautygirlmg-mayorista" "beautygirlmg-mayorista" "$root\zips\beautygirlmg-mayorista.zip"
New-PluginZip "$root\themes\bgmg-tema-base"          "bgmg-tema-base"         "$root\zips\bgmg-tema-base.zip"
```

## Notas de trato / técnicas
- Usuario venezolano; hablar de "tú".
- Los tests con estado (carrito, login, compra real) los hace el usuario manualmente; Claude
  verifica solo URLs públicas sin sesión.
- Si se edita un PHP con PowerShell, releerlo antes del siguiente Edit del editor. Escribir
  siempre **sin BOM** (evita "headers already sent" en WP).
- **Verificar sintaxis SIEMPRE antes de subir** un cambio:
  - **PHP:** `php -l <archivo>`. CLI portable en esta PC: `C:\Users\jose1\bgmg-tools\php\php.exe`
    (fuera del proyecto, no sincroniza). En otra PC sin php: instalar portable NTS x64 de
    windows.php.net (el VC++ runtime ya estaba). Lintar todo:
    `Get-ChildItem plugins,themes -Recurse -Filter *.php | % { & $php -l $_.FullName }`.
  - **JS embebido:** `php -l` NO valida el JS dentro de los `<script>`. **Node** está instalado →
    extraer el `<script>` (que no tenga `<?php` dentro) a un `.tmp.js` y `node --check`.
