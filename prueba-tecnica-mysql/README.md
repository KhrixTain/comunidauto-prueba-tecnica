# Prueba Técnica — ComunidAuto (MySQL)

## Requisitos

- MySQL **8.x**
- Archivo: **`prueba-tecnica.sql`** en la raíz de este directorio

## Ejecución rápida

> **Reset limpio:** en el `.sql` tenés `#DROP DATABASE IF EXISTS comunidauto_pt_mysql;`.  
> Para regenerar todo desde cero, **descomentar** esa línea y ejecutá el script otra vez.

---

## Esquema creado por el script

**Base de datos:** `comunidauto_pt_mysql`  
**Charset/Collation:** `utf8mb4` / `utf8mb4_0900_ai_ci` 

### Tabla `autos`

- `id BIGINT UNSIGNED PK AUTO_INCREMENT`
- `marca VARCHAR(64) NOT NULL`  ↩︎ `CHECK (CHAR_LENGTH(TRIM(marca)) > 0)`
- `modelo VARCHAR(64) NOT NULL` ↩︎ `CHECK (CHAR_LENGTH(TRIM(modelo)) > 0)`
- `precio DECIMAL(16,4) NOT NULL` ↩︎ `CHECK (precio >= 0)`
- `anio SMALLINT UNSIGNED NOT NULL`
- `created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3)`
- `updated_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)`
- **Índice:** `idx_autos_precio (precio)`

### Tabla `clientes`

- `id BIGINT UNSIGNED PK AUTO_INCREMENT`
- `nombre VARCHAR(64) NOT NULL`  ↩︎ `CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)`
- `apellido VARCHAR(64) NOT NULL` ↩︎ `CHECK (CHAR_LENGTH(TRIM(apellido)) > 0)`
- `email VARCHAR(254) NOT NULL UNIQUE` ↩︎ `CHECK (CHAR_LENGTH(TRIM(email)) > 0)`
- `created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3)`
- `updated_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)`

### Tabla `ventas`

- `id BIGINT UNSIGNED PK AUTO_INCREMENT`
- `cliente_id BIGINT UNSIGNED NOT NULL` → **FK** a `clientes(id)` **ON DELETE RESTRICT ON UPDATE CASCADE**
- `auto_id BIGINT UNSIGNED NOT NULL`    → **FK** a `autos(id)` **ON DELETE RESTRICT ON UPDATE CASCADE**
- `fecha DATE NOT NULL`
- `created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3)`
- `updated_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)`
- **Índices:** `idx_ventas_cliente (cliente_id)`, `idx_ventas_auto (auto_id)`

---

## Ejercicios incluidos (consultas listas en el `.sql`)

### 1) Listar autos por precio (menor → mayor)

```sql
SELECT *
FROM autos
ORDER BY precio ASC;
```

### 2) Autos con precio < 10.000.000

```sql
SELECT *
FROM autos
WHERE precio < 10000000
ORDER BY precio DESC;
```

### 3) Clientes con al menos una compra

```sql
SELECT clientes.*
FROM clientes
WHERE EXISTS (
    SELECT 1
    FROM ventas
    WHERE ventas.cliente_id = clientes.id
);
```

### 4) Total de autos vendidos por cliente

```sql
SELECT clientes.*, COUNT(ventas.id) AS total_autos_vendidos
FROM clientes
LEFT JOIN ventas
  ON clientes.id = ventas.cliente_id
GROUP BY clientes.id
ORDER BY total_autos_vendidos DESC;
```

### 5) Auto más vendido (modelo + cantidad)

**Solución 1 (permite múltiples ganadores por empate):** usa subconsultas anidadas para obtener la máxima cantidad y los modelos empatados.  
**Solución 2 (un único ganador):**

```sql
SELECT autos.modelo, COUNT(*) AS cant_ventas
FROM autos
INNER JOIN ventas
  ON autos.id = ventas.auto_id
GROUP BY autos.id, autos.modelo
ORDER BY cant_ventas DESC
LIMIT 1;
```
