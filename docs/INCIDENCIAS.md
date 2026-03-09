# Bitácora de incidencias ERP2 (rama ia)

> Formato: **Síntoma → Causa raíz → Fix → Prueba → Archivos típicos afectados**  
> Nota: Los nombres exactos pueden variar; describimos el patrón observado “según el código actual”.

---

## INC-001 — Compras con servicios no permitía líneas

**Síntoma**
- Al crear/editar compra con líneas que incluían servicios, el sistema rechazaba o descartaba esas líneas.

**Causa raíz**
- Validación de lectura de líneas (ej. `readLines()`) asumía que todas las líneas debían comportarse como “producto con stock”.
- Se descartaban filas válidas de tipo servicio o se exigía campos de producto de forma estricta.

**Fix**
- Ajuste de validación de líneas para permitir servicios:
  - servicios sin impacto en stock
  - reglas de cantidad/precio coherentes
- No se cambió lógica de negocio del módulo; se corrigió el parsing/validación de líneas.

**Prueba**
- Crear compra con 2 líneas: producto + servicio.
- Guardar borrador → emitir → verificar total correcto.
- Verificar que inventario solo cambia por producto (no por servicio).

**Archivos típicos afectados**
- `src/Controller/ComprasController.php`
- `views/compras/*`
- (si existía helper de líneas) funciones de parsing en el controlador.

---

## INC-002 — HY093 en Cartera (placeholders/params desalineados)

**Síntoma**
- Error PDO: `SQLSTATE[HY093] Invalid parameter number` al cargar cartera o aplicar filtros.

**Causa raíz**
- Consulta SQL con placeholders reutilizados o parámetros que no coincidían con el array `execute()`.
- Ejemplo típico: `:t` usado para dos columnas o params faltantes.

**Fix**
- Separar placeholders (ej. `:sub` / `:tot`) o enviar ambos parámetros explícitamente.
- Alinear `execute([...])` con placeholders reales de la query.

**Prueba**
- Abrir `/cartera` sin filtros y con filtros.
- Verificar que no aparece HY093.
- Verificar estados/saldos coherentes.

**Archivos típicos afectados**
- `src/Controller/CarteraController.php`
- (si hay modelo) `src/Model/*` relacionado a cartera.

---

## INC-003 — Facturas: solo guardaba la primera línea

**Síntoma**
- Al crear factura con múltiples líneas, solo se persistía una línea en DB.

**Causa raíz**
- El parser/validador de líneas descartaba filas por:
  - índices “huecos” en arrays del POST
  - validaciones estrictas (ej. cantidad vacía) sin tolerar fila parcialmente vacía
- `readLines()` devolvía un array con `count=1` aun cuando el POST tenía varias.

**Fix**
- Reindexar líneas con `array_values()` para evitar huecos.
- Ignorar filas completamente vacías.
- Mantener validación de campos obligatorios por línea, pero con reglas consistentes.

**Prueba**
- Crear factura con 2 líneas: producto + servicio.
- Verificar que ambas se guardan y aparecen al reabrir.
- Verificar que totales se calculan sobre ambas líneas.

**Archivos típicos afectados**
- `src/Controller/FacturasController.php`
- `views/facturas/*`

---

## INC-004 — HY093 al emitir factura (placeholders repetidos)

**Síntoma**
- Al emitir factura, error PDO HY093.

**Causa raíz**
- Placeholder duplicado en SQL (ej. `:t`) usado para dos columnas distintas (subtotal y total).
- PDO no podía mapear correctamente o faltaba un param.

**Fix**
- Cambiar a placeholders únicos (`:sub`, `:tot`) o proveer ambos parámetros.
- Revisión de consultas en `emitir()`.

**Prueba**
- Emitir factura con 2 líneas.
- Verificar que estado pasa a emitida.
- Verificar que total/subtotales se recalculan desde DB.
- Verificar que stock se ajusta (solo productos).

**Archivos típicos afectados**
- `src/Controller/FacturasController.php`

---

## INC-005 — Auditoría: 404 / no aparecía en menú

**Síntoma**
- `/auditoria` devolvía 404 o el módulo no aparecía en el Home/menú.

**Causa raíz**
- Faltaba registro de rutas en el router/app (App.php).
- Faltaba link condicional en Home/menú.
- No era problema de permisos (un 403 sería distinto), sino de routing.

**Fix**
- Agregar rutas para auditoría en configuración del router/app.
- Agregar link en Home/menú condicionado por `Auth::has('auditoria.ver')` (o permiso equivalente).

**Prueba**
- Entrar como usuario con permiso de auditoría:
  - aparece el link
  - `/auditoria` responde 200
- Entrar como usuario sin permiso:
  - no aparece link
  - si fuerza URL: 403 (si el controlador lo valida)

**Archivos típicos afectados**
- `src/Core/App.php` o router equivalente
- `views/home/index.php` (o menú parcial)
- `src/Controller/AuditoriaController.php`

---

## INC-006 — Auditoria::log firma incorrecta (se “silenciaba”)

**Síntoma**
- Acciones de seguridad (crear/editar roles/usuarios/permisos) no registraban auditoría.
- En algunos casos podía haber un TypeError oculto por try/catch.

**Causa raíz**
- Llamada a `Auditoria::log(...)` con firma equivocada:
  - se pasaban 2 parámetros (acción + detalle) en vez de 5 parámetros requeridos.
- try/catch evitaba que se notara, pero perdía trazabilidad.

**Fix**
- Actualizar llamadas a:
  - `Auditoria::log($usuarioId, $accion, $entidad, $entidadId, $detalle)`
- Evitar silenciar fallos sin al menos un registro en error_log (según criterio).

**Prueba**
- Crear/editar rol en UI.
- Verificar registro en auditoría con:
  - usuario_id correcto
  - acción y entidad correctas
  - entidad_id correcto
  - detalle presente

**Archivos típicos afectados**
- `src/Model/Auditoria.php`
- `src/Controller/RolesController.php`
- `src/Controller/UsuariosController.php`
- `src/Controller/PermisosController.php`

---

## INC-007 — (Histórico) UI/UX: filtro “Ref ID” mostraba 0

**Síntoma**
- En listado de pagos (u otro listado), el filtro “Ref ID” mostraba `0` cuando no se había filtrado.

**Causa raíz**
- Controller seteaba `refId` a 0 por defecto y la vista lo imprimía.

**Fix**
- No setear/mostrar `refId` cuando no sea > 0.

**Prueba**
- Cargar listado sin filtros y verificar que el input aparece vacío.
- Aplicar filtro y verificar que se conserva el valor.

**Archivos típicos afectados**
- `src/Controller/PagosController.php`
- `views/pagos/index.php`