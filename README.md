# ERP2 — ERP en PHP (MVC) + MySQL, desarrollado iterativamente con apoyo de IA  
**Proyecto de tesis inspirado en Dolibarr (open source)**

ERP2 es un ERP web ligero construido en **PHP 8+** con un **MVC propio**, pensado para ser **funcional, seguro y fácil de instalar**.  
Este proyecto forma parte de una **tesis** y toma como referencia conceptual y funcional un ERP open source ampliamente usado:

- Dolibarr (referencia): https://github.com/Dolibarr/dolibarr.git

> Nota: ERP2 **no es Dolibarr**, ni un fork directo. Es una implementación propia (MVC simple) inspirada en las ideas ERP del proyecto.

Repositorio: https://github.com/KevinMoscoso/erp2

---

## Características

- **Terceros** (clientes / proveedores) + contactos asociados
- **Productos / Servicios**
  - Regla: **producto mueve stock**, **servicio no mueve stock**
- **Facturas**
  - Estados: `borrador`, `emitida`, `anulada`
  - Emisión/anulación con reglas de integridad
- **Compras**
  - Estados: `borrador`, `emitida`, `anulada`
  - Entrada a inventario solo para productos
- **Pagos** (modelo polimórfico)
  - Pagos para **facturas** y **compras**
  - Anti-sobrepago + control por estado (solo `emitida`)
  - Concurrencia: `SELECT ... FOR UPDATE` en cabecera
- **Inventario + Kardex**
  - Movimientos: entrada / salida / ajuste
  - Transacciones y locks para consistencia
- **Cartera (CXC/CXP)**
  - CXC (facturas emitidas) / CXP (compras emitidas)
  - Estados: `pendiente`, `parcial`, `pagado`
- **Seguridad RBAC**
  - Roles / permisos por `permisos.codigo`
  - UI condicionada por permisos (vistas) + guardas en controladores
  - Módulo de Seguridad restringido a **admin-only por `id=1`**
- **Auditoría**
  - Registro de acciones clave
  - Vista de auditoría con filtros y detalle por ID
- **UX base**
  - `Flash` + `old()/err()/hasErr()` para formularios y validación

---

## Requisitos

- **PHP 8.0+**
- Extensiones PHP: `pdo`, `pdo_mysql`
- **MySQL / MariaDB**
- **Composer**
- Servidor web:
  - Recomendado: **Apache/Nginx** apuntando a `public/`
  - Alternativa para pruebas: servidor embebido de PHP

---

## Instalación (paso a paso)

### Paso 1 — Clonar el proyecto

```bash
git clone https://github.com/KevinMoscoso/erp2.git
cd erp2
```

### Paso 2 — Instalar dependencias (Composer)
Para uso normal (demo / producción):
```bash
composer install --no-dev
```
Para desarrollo:
```bash
composer install
```

### Paso 3 — Crear base de datos
Crea un schema vacío (recomendado utf8mb4):
```SQL
-- ERP2 clean schema (generated from Erp2.sql)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP DATABASE IF EXISTS `erp2`;
CREATE DATABASE `erp2` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `erp2`;

CREATE TABLE `usuarios` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permisos` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permisos_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_roles` (
`usuario_id` int unsigned NOT NULL,
  `rol_id` int unsigned NOT NULL,
  PRIMARY KEY (`usuario_id`,`rol_id`),
  KEY `idx_usuario_roles_rol` (`rol_id`),
  CONSTRAINT `fk_usuario_roles_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_roles_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rol_permisos` (
`rol_id` int unsigned NOT NULL,
  `permiso_id` int unsigned NOT NULL,
  PRIMARY KEY (`rol_id`,`permiso_id`),
  KEY `idx_rol_permisos_permiso` (`permiso_id`),
  CONSTRAINT `fk_rol_permisos_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rol_permisos_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terceros` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('cliente','proveedor','ambos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cliente',
  `nombre_comercial` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identificacion` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_terceros_tipo` (`tipo`),
  KEY `idx_terceros_nombre` (`nombre_comercial`),
  KEY `idx_terceros_identificacion` (`identificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contactos` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `tercero_id` int unsigned NOT NULL,
  `nombres` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contactos_tercero` (`tercero_id`),
  CONSTRAINT `fk_contactos_tercero` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `productos` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('producto','servicio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'producto',
  `referencia` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `precio_venta` decimal(18,2) NOT NULL DEFAULT '0.00',
  `costo` decimal(18,2) DEFAULT NULL,
  `stock_actual` decimal(18,2) NOT NULL DEFAULT '0.00',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_productos_referencia` (`referencia`),
  KEY `idx_productos_tipo` (`tipo`),
  KEY `idx_productos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventario_movimientos` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` int unsigned NOT NULL,
  `tipo` enum('entrada','salida','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(18,2) NOT NULL,
  `saldo_anterior` decimal(18,2) NOT NULL,
  `saldo_nuevo` decimal(18,2) NOT NULL,
  `referencia_tipo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` int unsigned DEFAULT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `nota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inv_prod` (`producto_id`),
  KEY `idx_inv_ref` (`referencia_tipo`,`referencia_id`),
  KEY `idx_inv_usuario` (`usuario_id`),
  CONSTRAINT `fk_inv_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `facturas` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `tercero_id` int unsigned NOT NULL,
  `estado` enum('borrador','emitida','anulada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_facturas_numero` (`numero`),
  KEY `idx_facturas_tercero_id` (`tercero_id`),
  KEY `idx_facturas_fecha` (`fecha`),
  KEY `idx_facturas_estado` (`estado`),
  CONSTRAINT `fk_facturas_terceros` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `factura_detalles` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `factura_id` int unsigned NOT NULL,
  `producto_id` int unsigned DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(14,2) NOT NULL DEFAULT '1.00',
  `precio_unitario` decimal(14,2) NOT NULL DEFAULT '0.00',
  `subtotal_linea` decimal(14,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_det_factura` (`factura_id`),
  KEY `idx_det_producto` (`producto_id`),
  CONSTRAINT `fk_det_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_det_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `compras` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `tercero_id` int unsigned NOT NULL,
  `estado` enum('borrador','emitida','anulada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_compras_numero` (`numero`),
  KEY `idx_compras_tercero_id` (`tercero_id`),
  KEY `idx_compras_fecha` (`fecha`),
  KEY `idx_compras_estado` (`estado`),
  CONSTRAINT `fk_compras_terceros` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `compra_detalles` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `compra_id` int unsigned NOT NULL,
  `producto_id` int unsigned DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(18,2) NOT NULL,
  `costo_unitario` decimal(18,2) NOT NULL,
  `subtotal_linea` decimal(18,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_compra_detalles_compra_id` (`compra_id`),
  KEY `idx_compra_detalles_producto_id` (`producto_id`),
  CONSTRAINT `fk_compra_detalles_compras` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compra_detalles_productos` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pagos` (
`id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo_ref` enum('factura','compra') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_id` int unsigned NOT NULL,
  `tercero_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(18,2) NOT NULL,
  `metodo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pagos_ref` (`tipo_ref`,`ref_id`),
  KEY `idx_pagos_tercero` (`tercero_id`),
  KEY `idx_pagos_fecha` (`fecha`),
  KEY `idx_pagos_usuario` (`usuario_id`),
  CONSTRAINT `fk_pagos_tercero` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auditoria` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `accion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detalle_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_usuario_id` (`usuario_id`),
  KEY `idx_auditoria_accion` (`accion`),
  KEY `idx_auditoria_entidad` (`entidad`,`entidad_id`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
```

---

### Paso 4 — Configurar credenciales de DB (.env)
ERP2 usa .env para la conexión a base de datos.
Crea/edita un archivo .env en la raíz del proyecto con:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=erp2
DB_USER=root
DB_PASS=TU_PASSWORD
```

---

### Ejecutar el proyecto
Opción A — Servidor embebido (solo pruebas)
Desde la raíz del proyecto:
```bash
php -S 127.0.0.1:8000 router.php
```
Abrir:
```Plain text
http://127.0.0.1:8000
```
---

### Crear cuenta admin para usar el erp:
Usuario admin (email + password configurables):
-Rol Admin
-Permisos mínimos (los básicos que tu UI usa)
-Asignación admin → rol Admin
-Garantiza que el admin sea id=1 (importante porque tu módulo Seguridad es admin-only por id=1)

⚠️ Importante: Verificar que tu tabla usuarios debe tener id auto_increment.
El script fuerza id=1. Si ya existe un id=1, no lo pisa.
```SQL
-- =========================================================
-- ADMIN INICIAL CONFIGURABLE (para primera ejecución)
-- - Crea usuario admin con id=1 si no existe
-- - Crea rol Admin y permisos mínimos
-- - Asigna rol Admin al usuario id=1
-- =========================================================

-- 1) CONFIGURA AQUÍ TUS CREDENCIALES INICIALES
SET @ADMIN_EMAIL = 'admin@erp2.local';
SET @ADMIN_PASS_PLAIN = 'admin';  -- cámbialo si quieres
SET @ADMIN_NOMBRE = 'Administrador';

-- 2) Generar hash en MySQL (bcrypt) si tu MySQL soporta SHA2 (sí) y tu app acepta password_hash bcrypt
--    OJO: En PHP, password_hash() genera bcrypt con formato $2y$... (recomendado).
--    Como SQL no puede generar bcrypt real de forma estándar, hay dos opciones:
--    A) Dejar un hash "temporal" y forzar cambio de password en el primer login (ideal si tu app lo soporta)
--    B) Insertar un password_hash ya generado con PHP (recomendado)
--
-- RECOMENDADO: genera el hash en tu PC una sola vez y pégalo aquí:
-- php -r "echo password_hash('admin', PASSWORD_DEFAULT), PHP_EOL;"
--
-- Luego reemplaza @ADMIN_PASS_HASH con el resultado.
SET @ADMIN_PASS_HASH = '$2y$10$REEMPLAZA_ESTE_HASH_POR_UNO_REAL_GENERADO_CON_PHP';

-- 3) Crear rol Admin (si no existe)
INSERT INTO roles (nombre, descripcion, create_at)
SELECT 'Admin', 'Rol administrativo (instalación inicial)', NOW()
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'Admin');

-- 4) Crear permisos mínimos (si no existen)
-- Ajusta/añade permisos según tu sistema.
INSERT INTO permisos (codigo)
SELECT 'terceros.ver'     WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='terceros.ver');
INSERT INTO permisos (codigo)
SELECT 'terceros.crear'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='terceros.crear');
INSERT INTO permisos (codigo)
SELECT 'terceros.editar'  WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='terceros.editar');

INSERT INTO permisos (codigo)
SELECT 'productos.ver'    WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='productos.ver');
INSERT INTO permisos (codigo)
SELECT 'productos.crear'  WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='productos.crear');
INSERT INTO permisos (codigo)
SELECT 'productos.editar' WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='productos.editar');

INSERT INTO permisos (codigo)
SELECT 'facturas.ver'     WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='facturas.ver');
INSERT INTO permisos (codigo)
SELECT 'facturas.crear'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='facturas.crear');
INSERT INTO permisos (codigo)
SELECT 'facturas.editar'  WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='facturas.editar');
INSERT INTO permisos (codigo)
SELECT 'facturas.emitir'  WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='facturas.emitir');
INSERT INTO permisos (codigo)
SELECT 'facturas.anular'  WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='facturas.anular');

INSERT INTO permisos (codigo)
SELECT 'compras.ver'      WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='compras.ver');
INSERT INTO permisos (codigo)
SELECT 'compras.crear'    WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='compras.crear');
INSERT INTO permisos (codigo)
SELECT 'compras.editar'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='compras.editar');
INSERT INTO permisos (codigo)
SELECT 'compras.emitir'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='compras.emitir');
INSERT INTO permisos (codigo)
SELECT 'compras.anular'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='compras.anular');

INSERT INTO permisos (codigo)
SELECT 'pagos.ver'        WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='pagos.ver');
INSERT INTO permisos (codigo)
SELECT 'pagos.crear'      WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='pagos.crear');
INSERT INTO permisos (codigo)
SELECT 'pagos.eliminar'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='pagos.eliminar');

INSERT INTO permisos (codigo)
SELECT 'inventario.ver'   WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='inventario.ver');
INSERT INTO permisos (codigo)
SELECT 'inventario.ajustar' WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='inventario.ajustar');

INSERT INTO permisos (codigo)
SELECT 'cartera.ver'      WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='cartera.ver');

INSERT INTO permisos (codigo)
SELECT 'auditoria.ver'    WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='auditoria.ver');

INSERT INTO permisos (codigo)
SELECT 'seguridad.ver'    WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE codigo='seguridad.ver');

-- 5) Asignar todos los permisos al rol Admin (idempotente)
SET @RID := (SELECT id FROM roles WHERE nombre='Admin' LIMIT 1);

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT @RID, p.id
FROM permisos p
WHERE NOT EXISTS (
  SELECT 1 FROM rol_permisos rp WHERE rp.rol_id=@RID AND rp.permiso_id=p.id
);

-- 6) Crear usuario admin id=1 si no existe
INSERT INTO usuarios (id, nombre, email, password_hash, created_at, updated_at)
SELECT 1, @ADMIN_NOMBRE, @ADMIN_EMAIL, @ADMIN_PASS_HASH, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE id = 1);

-- 7) Asignar rol Admin al usuario id=1 (idempotente)
INSERT INTO usuario_roles (usuario_id, rol_id)
SELECT 1, @RID
WHERE NOT EXISTS (
  SELECT 1 FROM usuario_roles ur WHERE ur.usuario_id=1 AND ur.rol_id=@RID
);
```

---

### Verificacióin Rápida:
1.- Inicia sesión y confirma menú por permisos (RBAC).
2.- Flujo mínimo recomendado:
  - Crea un tercero (cliente y proveedor)
  - Crea producto + servicio
  - Crea factura borrador con líneas (producto + servicio) → emitir → verificar stock (solo producto)
  - Crea compra borrador con líneas (producto + servicio) → emitir → verificar stock (solo producto)
  - Registra pagos y valida:
   -- no sobrepago
   -- solo sobre documentos emitidos
  - Abre Cartera y valida estados pendiente/parcial/pagado
  - Abre Auditoría y valida trazabilidad

---

### Solución de problemas (rápido)

- **“Error de conexión DB”**
  - Revisa .env (host, user, pass, dbname)
  - Confirma MySQL/MariaDB en ejecución
  - Confirma que importaste el SQL en el schema correcto

- **“Pantalla en blanco / Error 500”**
  - Ejecuta composer install
  - Verifica PHP 8+
  - Verifica que el servidor apunte a public/ (recomendado)

- **“No aparece Seguridad (Usuarios/Roles/Permisos)”**
  - Es normal si no eres admin id=1
  - Debes ingresar con usuario id=1 para acceder a Seguridad

---

## Autor

Kevin Moscoso — ERP2 (Proyecto de tesis, inspirado en Dolibarr)
