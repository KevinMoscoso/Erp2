# QA Checklist ERP2 (rama ia)

> Objetivo: checklist end-to-end para validar que el ERP funciona sin romper seguridad, integridad y UX base (Flash + old/errors).

---

## 1) Precondiciones

- [ ] Tener el proyecto clonado y dependencias instaladas:
  - [ ] `composer install`
- [ ] Configurar `.env` con credenciales de DB:
  - [ ] `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- [ ] Base de datos creada y esquema aplicado (migraciones/import SQL según tu flujo).
- [ ] Existe usuario **admin** con `id=1` (requisito de acceso a Seguridad).
  - [ ] Si no existe, crear manualmente (o usar seed demo si está disponible).
- [ ] (Opcional) Ejecutar seed demo si existe:
  - [ ] `php scripts/seed_demo.php`
- [ ] Servidor web levantado apuntando a `/public` (Apache/Nginx/PHP built-in).

---

## 2) Checklist general (cross-cutting)

### 2.1 Autenticación (Auth)
- [ ] Login con credenciales válidas inicia sesión.
- [ ] Login con credenciales inválidas muestra error (Flash).
- [ ] Logout cierra sesión y protege rutas privadas.

### 2.2 RBAC (permisos)
- [ ] Usuario sin permiso NO ve links en UI (vistas usan `Auth::has(...)`).
- [ ] Usuario sin permiso recibe 403 si fuerza URL (controladores usan `Auth::can(...)`).
- [ ] Usuario con permiso puede acceder al módulo correspondiente.

### 2.3 CSRF
- [ ] Formularios críticos incluyen token CSRF.
- [ ] Con CSRF inválido:
  - [ ] no se guarda nada en DB
  - [ ] se muestra Flash de error
  - [ ] redirección segura (303/302 según controlador)

### 2.4 Flash
- [ ] Flash `success` se muestra después de operaciones exitosas.
- [ ] Flash `error` se muestra en errores de validación o fallos de operación.
- [ ] Flash no persiste indebidamente tras navegación posterior.

### 2.5 Old input + field errors
- [ ] En validación fallida, se preserva `old()` para inputs.
- [ ] `err()` muestra error por campo (si aplica).
- [ ] `hasErr()` aplica estado visual de error (si la vista lo usa).
- [ ] Soporte de llaves anidadas tipo `items[0][cantidad]` / `items.0.cantidad` (Validar en formularios con líneas).

---

## 3) Pruebas por módulo

> Nota: rutas y nombres reales pueden variar; validar con las rutas del sistema.

### 3.1 Terceros / Contactos
- [ ] Listado carga correctamente.
- [ ] Crear tercero:
  - [ ] CSRF requerido
  - [ ] Validación de campos obligatorios (mostrar Flash + err/old)
  - [ ] Al guardar, se ve en listado
- [ ] Editar tercero:
  - [ ] CSRF requerido
  - [ ] Valida y preserva old/err en fallo
- [ ] Contactos (si existe módulo/tabla):
  - [ ] CRUD básico
  - [ ] Relación con tercero correcta
- [ ] Casos:
  - [ ] Cliente vs Proveedor (si existe campo `tipo`)
  - [ ] Identificación única (si existe regla en DB/código)

### 3.2 Productos / Servicios
- [ ] Listado carga correctamente.
- [ ] Crear producto:
  - [ ] CSRF requerido
  - [ ] Reglas de validación (precio/costo)
  - [ ] Stock inicial (si se maneja en tabla producto)
- [ ] Crear servicio:
  - [ ] CSRF requerido
  - [ ] NO debe afectar inventario/stock (según flujo del sistema)
- [ ] Editar producto/servicio:
  - [ ] CSRF requerido
  - [ ] Old/err en fallo
- [ ] Regla clave:
  - [ ] Producto mueve stock (por compras/facturas/ajustes)
  - [ ] Servicio NO mueve stock

### 3.3 Facturas
- [ ] Listado carga y filtra (si aplica).
- [ ] Crear factura en borrador:
  - [ ] Permite múltiples líneas (producto + servicio).
  - [ ] Validación de líneas:
    - [ ] no debe guardar solo 1 línea
    - [ ] filas vacías no deben romper (se ignoran)
    - [ ] old/err funciona si hay error en línea (Validar en implementación)
- [ ] Emitir factura:
  - [ ] Solo permite emitir cuando estado es borrador (si aplica).
  - [ ] Recalcula `subtotal_linea` y `total` desde DB (según implementación).
  - [ ] Ajusta stock de productos (FOR UPDATE / lock si aplica).
- [ ] Anular factura:
  - [ ] Si existen pagos, debe bloquear anulación.
  - [ ] Si estaba emitida, revierte stock (si aplica).
- [ ] Integridad:
  - [ ] Transacciones en emitir/anular (Validar en implementación)
- [ ] Edge cases:
  - [ ] Producto con stock insuficiente (bloqueo o error según reglas vigentes)
  - [ ] Factura con solo servicios (permitido)

### 3.4 Compras
- [ ] Crear compra borrador:
  - [ ] Múltiples líneas, permite servicios.
- [ ] Emitir compra:
  - [ ] Recalcula totales desde DB (si aplica).
  - [ ] Incrementa stock de productos (FOR UPDATE / lock si aplica).
- [ ] Anular compra:
  - [ ] Bloquea si hay pagos.
  - [ ] Si estaba emitida, revierte stock (si aplica).
- [ ] Validación de líneas:
  - [ ] Servicios permitidos sin romper lógica de stock.

### 3.5 Pagos
- [ ] Crear pago:
  - [ ] Solo sobre documentos **emitidos** (factura/compra).
  - [ ] Anti-sobrepago (no permite pagar más del saldo).
  - [ ] Maneja concurrencia con `SELECT ... FOR UPDATE` en cabecera (factura/compra).
  - [ ] CSRF requerido
  - [ ] Flash y validaciones (monto>0, fecha válida, tipo_ref y ref_id).
- [ ] Listado de pagos:
  - [ ] Filtros (tipo_ref/ref_id) no muestran “0” cuando vacío (si existe fix).
- [ ] Eliminar pago (si existe):
  - [ ] Validar permisos + CSRF
  - [ ] Recalcular saldo/cartera (según código actual) (Validar en implementación)

### 3.6 Inventario
- [ ] Kardex / movimientos (si existe vista):
  - [ ] Carga y filtra por producto/fechas.
- [ ] Ajustes de inventario (si existe):
  - [ ] Permisos requeridos
  - [ ] CSRF requerido
  - [ ] `SELECT ... FOR UPDATE` en producto o tabla de stock (según flujo)
- [ ] Integridad:
  - [ ] Cada movimiento refleja saldo_anterior/saldo_nuevo (si existen columnas)
- [ ] Servicios no generan movimientos.

### 3.7 Cartera
- [ ] Vista cartera carga sin errores.
- [ ] Filtros funcionan (cliente/proveedor, estado, rango fechas si aplica).
- [ ] Estados coherentes:
  - [ ] PENDIENTE cuando no hay pagos
  - [ ] PARCIAL cuando pago < total
  - [ ] PAGADO cuando pago == total
- [ ] No ocurre `SQLSTATE[HY093] Invalid parameter number` (placeholder/params alineados).
- [ ] Sumas / saldos coherentes contra pagos registrados.

### 3.8 Auditoría
- [ ] Listado de auditoría visible solo con permiso `auditoria.ver` (si aplica).
- [ ] Detalle de auditoría abre correctamente.
- [ ] Acciones relevantes quedan registradas (crear/editar/emitir/anular/pagos/seguridad) (Validar en implementación, depende de cobertura de logs).

### 3.9 Seguridad (Usuarios / Roles / Permisos)
- [ ] Acceso **solo** para admin `id=1` (admin-only).
- [ ] Usuarios:
  - [ ] Crear usuario con password válido (hash) permite login.
  - [ ] Editar usuario, asignación roles/permisos (si aplica).
- [ ] Roles:
  - [ ] Crear / editar roles
  - [ ] Asignar permisos a roles
- [ ] Permisos:
  - [ ] Crear / editar permisos (si UI lo permite)
- [ ] Auditoría::log en seguridad:
  - [ ] Llamada usa firma de 5 parámetros (Validar en implementación).

---

## 4) Pruebas de concurrencia (simulación 2 sesiones)

> Objetivo: validar que FOR UPDATE y anti-sobrepago evitan inconsistencias.

### 4.1 Concurrencia en pagos (factura)
Precondición: factura emitida con saldo pendiente.

Pasos:
1. [ ] Abrir **dos navegadores** (o incognito) con el mismo usuario con permiso `pagos.crear`.
2. [ ] En ambos, abrir formulario de pago para la misma factura.
3. [ ] En sesión A, ingresar monto igual a (saldo pendiente) y enviar.
4. [ ] Inmediatamente en sesión B, intentar enviar un pago que exceda el nuevo saldo (o el mismo monto).

Resultado esperado:
- [ ] Sesión B debe fallar con mensaje de “sobrepago / saldo insuficiente”.
- [ ] DB no debe tener pagos que excedan el total.
- [ ] Cartera debe reflejar estado correcto.

### 4.2 Concurrencia en inventario (ajustes o emitir compra/factura)
Precondición: producto con stock conocido.

Pasos:
1. [ ] Abrir dos sesiones.
2. [ ] Disparar operaciones concurrentes que afecten el mismo producto:
   - [ ] emitir compra en una sesión y ajuste en otra, o
   - [ ] emitir dos compras/facturas simultáneas.
3. [ ] Verificar que el stock final es consistente.

Resultado esperado:
- [ ] No hay stock corrupto (saltos, negativos inesperados si el sistema bloquea).
- [ ] Movimientos registran saldos secuenciales (si aplica).

---

## 5) Registro de resultados

- [ ] Registrar cada fallo encontrado en `docs/INCIDENCIAS.md` (o añadir una nueva incidencia).
- [ ] Adjuntar evidencia (capturas/logs) en tu sistema de tracking (fuera de repo si aplica).