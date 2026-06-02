# new.beautygirlmg.cl — Proyecto completo

> Snapshot en Drive al **2026-05-28**. Contiene los 3 plugins + el tema base del sitio v2 (`new.beautygirlmg.cl`). Si abrís esto desde otra PC, este archivo te dice qué hay y por dónde empezar. Estado más reciente: [HANDOFF-2026-05-28.md](./HANDOFF-2026-05-28.md).

## Qué hay acá

```
bgmg-chile-proyecto/
├── README-EMPEZAR-AQUI.md           ← este archivo
├── HANDOFF-2026-05-27.md            ← snapshot detallado del trabajo en bgmg-chile
├── BGMG-CHILE-API-PARA-LANDING.md   ← doc de API pública entre plugins
├── LEEME.txt                        ← notas históricas del proyecto
│
├── plugins/                         ← los 3 plugins WP del sitio
│   ├── bgmg-chile/                  v1.15.2 — RUT, comunas, envíos chilenos, wizards
│   ├── bgmg-landing/                v6.3.7  — templates custom de checkout/cart/account
│   └── beautygirlmg-mayorista/      v2.5.2  — precios mayorista (tiered pricing + surtido)
│
├── themes/                          ← el tema base que requiere WP
│   └── bgmg-tema-base/              esqueleto mínimo; delega todo a bgmg-landing
│
└── zips/                            ← listos para subir a wp-admin
    ├── bgmg-chile.zip               (126 KB)
    ├── bgmg-landing.zip             (100 KB)
    ├── beautygirlmg-mayorista.zip
    └── bgmg-tema-base.zip
```

## Cómo retomar el trabajo

### Si solo necesitás instalar todo en una nueva instalación WP

Orden recomendado:

1. **Tema:** subir `bgmg-tema-base.zip` desde wp-admin → Apariencia → Temas → Añadir nuevo → Subir tema. Activar.
2. **Plugins** (subir uno por uno desde wp-admin → Plugins → Añadir nuevo → Subir plugin):
   - `bgmg-chile.zip` (núcleo de localización)
   - `bgmg-landing.zip` (templates)
   - `beautygirlmg-mayorista.zip` (precios mayorista)
3. Configurar según el checklist del [HANDOFF](./HANDOFF-2026-05-27.md) sección 8.

### Si vas a seguir editando código

1. Clonar/copiar esta carpeta a la PC nueva (Drive Desktop lo hace solo si tenés la misma cuenta).
2. Abrir Claude Code apuntando a esta carpeta.
3. Decirle: **"Continuemos con el proyecto new.beautygirlmg, leé el HANDOFF más reciente."**
4. Claude ve el handoff y retoma con contexto completo.

## Versiones snapshot

| Componente | Versión | Tipo |
|------------|---------|------|
| `bgmg-chile` | **1.15.2** | Plugin (localización Chile) |
| `bgmg-landing` | **6.3.7** | Plugin (actúa como tema) |
| `beautygirlmg-mayorista` | **2.5.2** | Plugin (precios mayorista) |
| `bgmg-tema-base` | — | Tema (esqueleto) |

## Sitios

- **`beautygirlmg.cl`** = **producción actual (V1)**. NO tiene los plugins de esta carpeta.
- **`new.beautygirlmg.cl`** = **V2 en construcción**. Destino de todos los plugins de esta carpeta.

## Cómo se relacionan los plugins entre sí

- **`bgmg-tema-base`** = tema. Esqueleto mínimo. NO sirve templates por sí solo.
- **`bgmg-landing`** = sirve los templates custom de checkout, cart, account, etc. via `template_include`. Delega el header/footer.
- **`bgmg-chile`** = agrega campos chilenos (RUT, comunas), métodos de envío, validaciones, paneles admin. Los templates de bgmg-landing y los hooks de bgmg-chile cooperan.
- **`beautygirlmg-mayorista`** = lógica de precios mayoristas (tiered pricing). Independiente pero coordina con bgmg-landing vía API documentada en `BGMG-CHILE-API-PARA-LANDING.md` (y el contrato propio del plugin).

## Soporte

Toda la info técnica de bgmg-chile está en [HANDOFF-2026-05-27.md](./HANDOFF-2026-05-27.md). Para los otros plugins NO hay handoff todavía (se tocaron en sesiones anteriores). Si pasaron varias semanas desde el snapshot, conviene pedirle a Claude un handoff actualizado al retomar.
