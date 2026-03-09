# Arquitectura ERP2 (rama ia)

> Documento técnico: estructura del sistema, decisiones clave y mecanismos de seguridad/integridad.

---

## 1) Visión general

ERP2 es un ERP en PHP con patrón MVC simple (sin frameworks). El sistema implementa:
- Autenticación y sesión
- RBAC (roles/permisos)
- CSRF en acciones críticas
- Flash messages + UX helpers (old/errors)
- Módulos core: Terceros, Productos, Facturas, Compras, Pagos, Inventario, Cartera
- Auditoría de acciones relevantes

---

## 2) Estructura del proyecto (MVC)

Estructura típica (alto nivel):
- `public/`  
  Punto de entrada web (front controller). Enruta requests a la aplicación.
- `src/`
  - `Core/` (infraestructura): Router/App/View/Database/Auth/Csrf/Flash, etc.
  - `Controller/` controladores por módulo
  - `Model/` modelos de acceso a datos y utilitarios (incluye Auditoría)
- `views/`
  - Vistas PHP por módulo (listados, formularios, detalle)
  - Parciales (flash, etc.)
- `scripts/` (si existe)
  - utilitarios CLI como seed demo

### Flujo request → controller → model → view
1. Request HTTP entra por `public/index.php`.
2. Router / App resuelve ruta → controlador + acción.
3. Controlador:
   - verifica autenticación/permisos
   - valida CSRF (si aplica)
   - ejecuta operaciones usando PDO/Model (transacciones si aplica)
   - setea Flash + redirige o renderiza vista
4. Vista PHP muestra datos y respeta condiciones RBAC (`Auth::has`).

---

## 3) Base de datos y acceso (Database::pdo)

### Database::pdo
- Centraliza la creación de conexión PDO.
- Configura DSN desde `.env` (host, puerto, nombre DB, usuario, contraseña).
- PDO se usa directo en controladores y/o modelos.

Decisiones:
- Se evita un ORM, se prioriza PDO con consultas explícitas.
- En operaciones críticas se usa transacción y locks.

---

## 4) RBAC (Roles/Permisos)

### Modelo conceptual
- `permisos.codigo` define permisos atómicos (ej: `facturas.emitir`).
- `roles` agrupa permisos.
- pivots típicos:
  - `usuario_roles (usuario_id, rol_id)`
  - `rol_permisos (rol_id, permiso_id)`
  - (opcional) `usuario_permisos` si existe

### En controladores: Auth::can
- Las acciones protegidas validan permisos antes de ejecutar lógica.
- Comportamiento esperado: 403 si no tiene permiso.

### En vistas: Auth::has
- Menús, botones y enlaces se ocultan si no hay permiso.
- Esto evita “mostrar” acciones que luego fallarán en backend.

### Admin-only por id=1 (Seguridad)
- Acceso a módulos de seguridad (Usuarios/Roles/Permisos) restringido por regla fija:
  - **solo usuario con `id=1`**
- Aunque exista un permiso tipo `seguridad.ver`, la regla admin-only prevalece.

---

## 5) CSRF

- Acciones críticas (crear/editar/emitir/anular/eliminar) exigen token CSRF.
- Validación típica:
  - si token inválido → Flash error → redirect → no se guarda nada.

Objetivo:
- Prevenir ataques CSRF sin frameworks, con un token por sesión.

---

## 6) Flash + UX helpers (old/errors)

### Flash
- Permite mensajes `success/error/info` tras redirects.
- Puede incluir “data flash” para transportar:
  - `old input` (valores previos)
  - `field errors` (errores por campo)

### Helpers
- `old($key, $default)` retorna valor previo.
- `err($key)` retorna mensaje de error para el campo.
- `hasErr($key)` indica si hay error para aplicar estilos.
- Soporta claves:
  - `items[0][cantidad]`
  - `items.0.cantidad`

Objetivo:
- UX consistente sin frameworks (similar a “old()” en Laravel).

---

## 7) Integridad transaccional y concurrencia

### Transacciones
- Operaciones de negocio críticas usan transacciones para asegurar atomicidad.
- Ejemplos típicos:
  - emitir/anular facturas y compras
  - registrar pagos
  - actualizar inventario/stock

### Locks (SELECT ... FOR UPDATE)
- Se usa `SELECT ... FOR UPDATE` para evitar condiciones de carrera:
  - pagos: lock sobre cabecera de factura/compra al recalcular saldo y registrar pago
  - inventario: lock sobre producto/stock al ajustar cantidades

Objetivo:
- Evitar sobrepago simultáneo.
- Evitar desajustes de stock por operaciones concurrentes.

---

## 8) Reglas clave de consistencia (Facturas/Compras)

### Emitir recalcula totales desde DB
- Para evitar manipulaciones del front o inconsistencias, al emitir:
  - recalcula `subtotal_linea` desde detalle persistido
  - total = sumatoria de líneas en DB
- Esto evita confiar en lo que llega por POST.

### Anular bloqueado si hay pagos
- Factura/compra no debe anularse si existen pagos asociados.
- Protege integridad de cartera y trazabilidad.

---

## 9) Pagos: modelo polimórfico

Tabla `pagos` (conceptual):
- `tipo_ref` indica entidad: `factura` o `compra` (u otra si el sistema extiende).
- `ref_id` indica ID del documento.
- `monto`, `fecha`, `metodo` y referencia/nota (según schema)
- Asociado a tercero y usuario que registra

Ventajas:
- Unifica pagos para varios documentos sin duplicar tablas.
- Permite cartera consolidada.

---

## 10) Auditoría

- Registro de acciones relevantes (módulos core y seguridad).
- `Auditoria::log(usuarioId, accion, entidad, entidadId, detalleArray)`
- Se valida que el acceso a la UI de auditoría se controle por RBAC (`auditoria.ver`) según implementación.

---

## 11) Notas / puntos a validar

- Cobertura exacta de permisos por acción puede variar según controladores.
- Algunas tablas pueden tener variaciones (`*_detalle` plural/singular, timestamps opcionales).
- Estados exactos (borrador/emitida/anulada) deben validarse contra el código actual.