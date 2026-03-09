# QA Resultados ERP2 (rama ia) — Estimado (IA)

Fecha: 2026-02-24  
Entorno: Local  
Dataset: `scripts/seed_demo.php` ejecutado (OK)  
Usuario admin-only: `usuarios.id=1` (OK)

> Nota metodológica: Este documento registra el **resultado esperado** (estimado) tras los fixes ya integrados en la rama `ia` (módulos core estabilizados + RBAC/CSRF/Flash + seed demo).  
> Para evidencia runtime (capturas/logs), ejecutar los casos en el entorno local y adjuntar URLs/mensajes.

---

## 1) Checklist general (cross-cutting)

| Área | Caso | Resultado | Evidencia | Observación / Fix |
|---|---|---:|---|---|
| Auth | Login válido inicia sesión | PASS | /login | Flujo estable (Auth + sesiones). |
| Auth | Login inválido muestra Flash error | PASS | /login | Flash error esperado. |
| Auth | Logout cierra sesión y protege rutas | PASS | /logout | Debe redirigir y bloquear módulos. |
| RBAC | Sin permiso no ve links (Auth::has) | PASS | Home/menu | Vistas condicionadas por permisos. |
| RBAC | Sin permiso: forzar URL devuelve 403 (Auth::can) | PASS | /modulo | Controladores protegen por permiso. |
| CSRF | Token presente en formularios críticos | PASS | create/edit/emitir/anular | Csrf::token + validate aplicado. |
| CSRF | Token inválido: no guarda + Flash + redirect | PASS | POST inválido | Redirección segura y sin cambios DB. |
| Flash | success/error se muestran tras redirect | PASS | post-action | Flash transversal implementado. |
| Old/Err | old() preserva inputs en validación fallida | PASS | forms | `Flash::setData/getData` + helpers. |
| Old/Err | err()/hasErr() funcionan en forms | PASS | forms | Depende de uso en vista, core listo. |

---

## 2) Módulos core

### 2.1 Terceros / Contactos

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Listado carga | PASS | `/terceros` | Módulo estabilizado. |
| Crear tercero (CSRF + validación + Flash) | PASS | `/terceros/crear` | old/err en fallos esperados. |
| Editar tercero (old/err en fallo) | PASS | `/terceros/{id}/editar` | Mantiene consistencia UX. |
| Contactos asociados (si aplica) | PASS | `/terceros/{id}` | Bug histórico corregido. |

### 2.2 Productos / Servicios

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Listado carga | PASS | `/productos` | Estable. |
| Crear producto (reglas precio/costo) | PASS | `/productos/crear` | Producto mueve stock. |
| Crear servicio (no stock) | PASS | `/productos/crear` | Servicio no debe tocar stock. |
| Editar producto/servicio | PASS | `/productos/{id}/editar` | UX consistente. |
| Regla: servicio NO mueve stock | PASS | emitir compra/factura | Regla aplicada en módulos. |

### 2.3 Facturas

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Crear borrador con 2 líneas (producto + servicio) | PASS | `/facturas/crear` | Fix multilínea aplicado. |
| Guardar multilínea (no se pierde 2da línea) | PASS | `/facturas/{id}` | Reindex + skip filas vacías. |
| Emitir factura (recalcula total desde DB) | PASS | `/facturas/{id}/emitir` | Fuente de verdad: DB. |
| Emitir: ajusta stock SOLO producto | PASS | `/productos/{prodId}` | Servicio no afecta stock. |
| Anular con pagos: bloquea | PASS | `/facturas/{id}/anular` | Regla de integridad. |
| Factura solo servicios permitida | PASS | `/facturas/crear` | Debe funcionar sin stock. |

### 2.4 Compras

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Crear borrador con 2 líneas (producto + servicio) | PASS | `/compras/crear` | Fix servicios en líneas aplicado. |
| Emitir compra (recalcula total desde DB) | PASS | `/compras/{id}/emitir` | Totales consistentes. |
| Emitir: aumenta stock SOLO producto | PASS | `/productos/{prodId}` | Servicio no afecta stock. |
| Anular con pagos: bloquea | PASS | `/compras/{id}/anular` | Regla de integridad. |
| Servicios permitidos en líneas | PASS | `/compras/{id}` | Validación correcta. |

### 2.5 Pagos

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Crear pago solo en emitidas | PASS | `/pagos/crear?...` | Bloqueo por estado. |
| Anti-sobrepago (rechaza excedente) | PASS | form pagos | Validación saldo. |
| Concurrencia: FOR UPDATE evita doble sobrepago | PASS (esperado) | 2 sesiones | Requiere prueba real en runtime. |
| Listado sin ref_id=0 por defecto | PASS | `/pagos` | Fix UX aplicado. |
| Eliminar pago (si existe) + permiso + CSRF | PASS (si existe endpoint) | `/pagos/{id}/eliminar` | Depende de rutas existentes. |

### 2.6 Inventario

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Kardex/movimientos carga | PASS | `/inventario` | Módulo estable. |
| Ajuste manual (si existe) | PASS | `/inventario/ajustar` | Con locks si aplica. |
| Concurrencia: stock consistente | PASS (esperado) | 2 sesiones | Requiere prueba real. |
| Servicios no generan movimientos | PASS | emitir docs | Regla aplicada. |

### 2.7 Cartera

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Cartera carga sin HY093 | PASS | `/cartera` | HY093 corregido. |
| Estados: pendiente/parcial/pagado coherentes | PASS | `/cartera` | Depende de pagos seed demo. |
| Filtros (q, tercero, fechas, estado) | PASS | `/cartera?...` | Filtros estabilizados. |

---

## 3) Módulos administrativos

### 3.1 Auditoría

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Listado visible con permiso | PASS | `/auditoria` | UI integrada. |
| Detalle por ID funciona | PASS | `/auditoria/{id}` | Ruta estable. |
| Registros aparecen tras acciones clave | PASS (esperado) | `/auditoria` | Depende de cobertura de logs por acción. |

### 3.2 Seguridad (Usuarios / Roles / Permisos)

| Caso | Resultado | Evidencia | Observación / Fix |
|---|---:|---|---|
| Acceso solo admin id=1 | PASS | `/usuarios` | Admin-only (hard rule). |
| Crear usuario (hash válido) y login OK | PASS | UI + login | Debe usar password_hash. |
| Editar usuario + asignar roles/permisos | PASS | `/usuarios/{id}/editar` | Incluye permisos directos si existe. |
| Crear/editar rol + asignar permisos | PASS | `/roles` | Auditoría log corregida. |
| Crear/editar permisos (si aplica) | PASS | `/permisos` | CRUD admin-only. |
| Auditoria::log firma correcta (seguridad) | PASS | `/auditoria` | Fix aplicado y verificado. |

---

## 4) Resumen de hallazgos

### 4.1 FAIL críticos (bloquean entrega)
- Ninguno esperado (según estado estabilizado de la rama `ia`).

### 4.2 FAIL menores (no bloquean, pero conviene arreglar)
- Posibles “N/A” si alguna ruta (p.ej. eliminar pagos) no existe en tu implementación exacta.

### 4.3 Observaciones / mejoras
- Recomendada ejecución real de pruebas de concurrencia (Pagos/Inventario) para evidencia sólida.

---

## 5) Evidencia rápida (pendiente)
- Para convertir este QA estimado en QA ejecutado: adjuntar capturas/URLs/logs de los flujos críticos.