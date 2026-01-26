# QA — ERP2 (rama `ia`) — Checklist final (Hitos 9A–9D)

**Fecha:** 2026-01-26  
**Stack:** PHP 8.x + MVC propio (Database::pdo, View::render, Flash, Auth::can/has, CSRF, Auditoria::log)

---

## 0) Precondiciones

1. BD cargada (schema + datos de prueba).
2. Usuario con permisos:
   - `terceros.*`, `productos.*`, `facturas.*`, `compras.*`, `pagos.*`, `inventario.*`, `cartera.ver`
3. App ejecutándose (ejemplo):
   - `php -S 127.0.0.1:8000 -t public router.php`
4. `APP_DEBUG=0` para validar que no se filtran stacktraces a UI.

---

## 1) Fix aplicados previamente (NO TOCAR)

Estos puntos ya fueron corregidos y **no** deben reintroducirse:

1) **Facturas `readLines`**
   - Soporta multilínea.
   - Permite servicios (`producto_id` null + descripción).

2) **Facturas `emitir()` HY093**
   - No repetir placeholders nombrados en un mismo statement.
   - Ejemplo correcto: `SET subtotal = :sub, total = :tot ...` (NO `:t` repetido).

3) **Búsquedas HY093**
   - Cartera / Producto / Tercero: placeholders únicos (`:q1/:q2/...`).

4) **Pagos `create()`**
   - old/errors + CSRF + transacción + `SELECT ... FOR UPDATE`.
   - anti-sobrepago.
   - bloqueo por estado (solo `emitida`).
   - logs / auditoría en bloqueos.

---

## 2) HITO 9A — UX base (Old Input + Field Errors)

### Criterios globales
- En POST con validación fallida:
  - Flash error general
  - `old()` preserva inputs
  - `err()` / `hasErr()` muestran errores por campo
  - redirect seguro (303 si aplica)
  - **sin HTTP 500 en UI**
- CSRF inválido:
  - Flash amigable
  - no ejecuta acción
  - sin 500

---

## 3) Terceros

### Crear
1. `/terceros/crear` → dejar requerido vacío → Guardar  
✅ Esperado: `err(campo)` + old preservado, sin 500.

### Editar
1. `/terceros/{id}/editar` → email inválido (si aplica) o requerido vacío  
✅ Esperado: error inline + old preservado, sin 500.

### Contactos (si existe)
1. `/terceros/{id}` → guardar contacto con requerido vacío  
✅ Esperado: error inline + old preservado, sin 500.

---

## 4) Productos / Servicios

### Crear producto
1. `/productos/crear` → tipo=producto → precio inválido  
✅ Esperado: `err(precio_venta)` + old preservado.

### Crear servicio
1. `/productos/crear` → tipo=servicio  
✅ Esperado: se crea sin afectar inventario.

### Editar
1. `/productos/{id}/editar` → invalidar un campo  
✅ Esperado: error inline + old preservado.

---

## 5) Facturas

### UX: preservar líneas tras error
1. `/facturas/crear` → tercero + 2 líneas → fecha inválida → Guardar  
✅ Esperado: fecha/tercero/líneas repobladas + err visible, sin 500.

### Emitir recalcula total desde DB
1. Crear → Guardar → Emitir  
✅ Esperado: total/subtotal coherentes con sumatoria en DB, estado `emitida`.

### Anular bloqueada con pagos
1. Emitir → pagar parcial → anular  
✅ Esperado: bloqueado con Flash + log, sin 500.

---

## 6) Compras

### UX: preservar proveedor y líneas tras error
1. `/compras/crear` → proveedor + 2 líneas → fecha inválida → Guardar  
✅ Esperado: old preserva proveedor y líneas, err visible, sin 500.

### Emitir sin HY093
1. Crear → Emitir  
✅ Esperado: no HY093, total desde líneas DB, estado `emitida`.

### Anular bloqueada con pagos
1. Emitir → pagar → anular  
✅ Esperado: bloqueado con Flash + log, sin 500.

---

## 7) Pagos

### Pago solo en emitidas
1. Intentar pagar borrador/anulada  
✅ Esperado: bloqueado con Flash + logs, sin 500.

### Anti-sobrepago
1. monto > saldo  
✅ Esperado: bloqueado, old preservado, sin 500.

### Búsqueda / filtros
1. `/pagos` → Buscar (q) con texto → Filtrar  
✅ Esperado: lista filtrada, sin 500.

2. Filtrar por Ref ID válido  
✅ Esperado: filtra correctamente.

---

## 8) Inventario

### Ajuste manual (si existe)
1. `/inventario/{id}` → cantidad inválida  
✅ Esperado: `err(cantidad)` + old preservado, sin 500.

2. Restar más del stock  
✅ Esperado: bloqueado con Flash, sin 500, stock no cambia.

---

## 9) Cartera

### Filtros persistentes (GET)
1. `/cartera` → setear tipo/q/tercero_id/desde/hasta/estado_pago → Filtrar  
✅ Esperado: los filtros se mantienen en inputs.

### Fechas inválidas no rompen la página
1. desde/hasta inválidas (o desde > hasta)  
✅ Esperado: mensaje amigable, listado sigue, sin 500.

---

## 10) Evidencia recomendada
- Capturas de formularios con old/err
- Capturas de emitir/anular (bloqueos)
- Capturas de cartera con filtros
- Logs de bloqueos y errores controlados (sin stacktrace en UI)
