/*
 * =================
 *  Tablas sugeridas:
 *  
 * autos (id, marca, modelo, precio, anio)
 * clientes (id, nombre, apellido, email)
 * ventas (id, cliente_id, auto_id, fecha)
 * =================
 */


#DROP DATABASE IF EXISTS comunidauto_pt_mysql;    #--DESCOMENTAR PARA REGENERAR
CREATE DATABASE IF NOT EXISTS comunidauto_pt_mysql
	DEFAULT CHARACTER SET utf8mb4
	DEFAULT COLLATE utf8mb4_0900_ai_ci;

USE comunidauto_pt_mysql;

CREATE TABLE autos(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    marca VARCHAR(64) NOT NULL,
    modelo VARCHAR(64) NOT NULL,
    precio DECIMAL(16,4) NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP(3) NOT NULL
		DEFAULT CURRENT_TIMESTAMP(3),
	updated_at TIMESTAMP(3) NOT NULL
		DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    CONSTRAINT chk_autos_marca_not_blank CHECK (CHAR_LENGTH(TRIM(marca)) > 0),
    CONSTRAINT chk_autos_modelo_not_blank CHECK (CHAR_LENGTH(TRIM(modelo)) > 0),
    CONSTRAINT chk_autos_precio_not_negative CHECK (precio >= 0)
);
CREATE INDEX idx_autos_precio ON autos(precio); 

CREATE TABLE clientes(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(64) NOT NULL,
    apellido VARCHAR(64) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    created_at TIMESTAMP(3) NOT NULL
		DEFAULT CURRENT_TIMESTAMP(3),
	updated_at TIMESTAMP(3) NOT NULL
		DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3),
    CONSTRAINT chk_clientes_nombre_not_blank CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
    CONSTRAINT chk_clientes_apellido_not_blank CHECK (CHAR_LENGTH(TRIM(apellido)) > 0),
    CONSTRAINT chk_clientes_email_not_blank CHECK (CHAR_LENGTH(TRIM(email)) > 0)
);

CREATE TABLE ventas(
	id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cliente_id BIGINT UNSIGNED NOT NULL,
    auto_id BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    FOREIGN KEY(cliente_id) REFERENCES clientes(id)
		ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY(auto_id) REFERENCES autos(id)
		ON DELETE RESTRICT
        ON UPDATE CASCADE,
    created_at TIMESTAMP(3) NOT NULL
		DEFAULT CURRENT_TIMESTAMP(3),
	updated_at TIMESTAMP(3) NOT NULL
		DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3)
);
CREATE INDEX idx_ventas_cliente ON ventas(cliente_id);
CREATE INDEX idx_ventas_auto ON ventas(auto_id);







/*
 * =================
 *  Insersión de datos (Generados con IA para pruebas)
 * =================
 */

-- =========================
-- AUTOS
-- =========================
INSERT INTO autos (marca, modelo, precio, anio) VALUES
('Toyota',      'Corolla',        9200000.0000, 2022),
('Toyota',      'Hilux',         18500000.0000, 2023),
('Volkswagen',  'Gol Trend',      4800000.0000, 2018),
('Chevrolet',   'Onix',           7100000.0000, 2021),
('Ford',        'Ranger',        19900000.0000, 2022),
('Peugeot',     '208',            8300000.0000, 2023),
('Renault',     'Sandero',        6400000.0000, 2020),
('Fiat',        'Cronos',         8900000.0000, 2024),
('Jeep',        'Renegade',      16800000.0000, 2022),
('Honda',       'Civic',         12500000.0000, 2020);

-- =========================
-- CLIENTES
-- =========================
INSERT INTO clientes (nombre, apellido, email) VALUES
('Juan',   'Pérez',     'juan.perez@example.com'),
('María',  'López',     'maria.lopez@example.com'),
('Carlos', 'García',    'carlos.garcia@example.com'),
('Ana',    'Torres',    'ana.torres@example.com'),
('Diego',  'Fernández', 'diego.fernandez@example.com'),
('Lucía',  'Gómez',     'lucia.gomez@example.com'),
('Pablo',  'Castillo',  'pablo.castillo@example.com'),
('Sofía',  'Romero',    'sofia.romero@example.com');  -- esta persona no compra nada (para el ej. 3)

-- =========================
-- VENTAS
-- (asume que las tablas están vacías y los IDs autoincrementales empiezan en 1)
-- =========================
-- Corolla (auto_id = 1) será el más vendido (5 ventas)
INSERT INTO ventas (cliente_id, auto_id, fecha) VALUES
(1, 1, '2025-01-10'),
(2, 1, '2025-02-14'),
(3, 1, '2025-03-21'),
(7, 1, '2025-05-05'),
(5, 1, '2025-05-12'),

-- Sander (auto_id = 7) también será el más vendido (5 ventas) [Descomentar para comprobar]
/*
(5, 7, '2025-01-10'),
(4, 7, '2025-02-14'),
(7, 7, '2025-03-21'),
(7, 7, '2025-05-05'),
(1, 7, '2025-05-12'),
*/
-- Otras ventas variadas
(4, 9, '2025-01-05'),  -- Jeep Renegade
(5, 5, '2025-03-01'),  -- Ford Ranger
(6, 2, '2025-03-11'),  -- Toyota Hilux
(7, 8, '2025-02-23'),  -- Fiat Cronos
(1, 3, '2025-04-02'),  -- VW Gol Trend
(2, 4, '2025-04-10'),  -- Chevrolet Onix
(3, 6, '2025-04-16'),  -- Peugeot 208
(6, 5, '2025-04-20');  -- Ford Ranger (segunda venta)







/*
 * =================
 *  Ejercicio 1
 *
 *  Enunciado: Listar todos los autos ordenados por precio de menor a mayor. 
 * =================
 */
 
SELECT *
FROM autos
ORDER BY precio ASC;

/*
 * =================
 *  Ejercicio 2
 *
 *  Enunciado: Mostrar los autos cuyo precio sea menor a 10.000.000. 
 * =================
 */
 
SELECT *
FROM autos
WHERE precio < 10000000
ORDER BY precio DESC;
 
 /*
 * =================
 *  Ejercicio 3
 *
 *  Enunciado: Obtener todos los clientes que hayan realizado al menos una compra.
 * 	('Sofía',  'Romero',    'sofia.romero@example.com') No realiza ninguna compra.
 * =================
 */
 
SELECT clientes.*
FROM clientes
WHERE EXISTS (
	SELECT 1
    FROM ventas
    WHERE ventas.cliente_id = clientes.id
);

/*
 * =================
 *  Ejercicio 4
 *
 *  Enunciado: Mostrar el total de autos vendidos por cada cliente. 
 * =================
 */
 
SELECT clientes.*, COUNT(ventas.id) AS total_autos_vendidos
FROM clientes
LEFT JOIN ventas
ON clientes.id = ventas.cliente_id
GROUP BY clientes.id
ORDER BY total_autos_vendidos DESC;

/*
 * =================
 *  Ejercicio 5
 *
 *  Enunciado: Mostrar el auto más vendido (nombre del modelo + cantidad).
 *
 *  SOLUCIÓN 1: Considerando múltiples autos que hayan alcanzado la maxima cantidad de ventas.
 * =================
 */

SELECT autos.modelo, autos_con_maxima_cant_ventas.cant_ventas
FROM autos
INNER JOIN (
	SELECT autos_con_cant_ventas.*  #En esta query filtro por todos los autos que cumplieron con la maxima cantidad de ventas
	FROM (
		SELECT ventas.auto_id, COUNT(*) as cant_ventas  #En esta query calculo el total de ventas por cada auto de la tabla ventas
		FROM ventas
		GROUP BY ventas.auto_id
	) AS autos_con_cant_ventas
	INNER JOIN (
		SELECT MAX(cant_ventas) as max_cant_ventas  #En esta query hago un calculo auxiliar de la maxima cantidad de ventas realizadas
		FROM (
			SELECT COUNT(*) AS cant_ventas
			FROM ventas
			GROUP BY ventas.auto_id
		) AS aux
	) AS calculo_auxiliar
	ON autos_con_cant_ventas.cant_ventas = calculo_auxiliar.max_cant_ventas
) AS autos_con_maxima_cant_ventas
ON autos.id = autos_con_maxima_cant_ventas.auto_id;

/*
 * =================
 *  Ejercicio 5
 *
 *  Enunciado: Mostrar el auto más vendido (nombre del modelo + cantidad).
 *
 *  SOLUCIÓN 2: Considerando que solo un único auto haya realizado la máxima cantidad de ventas.
 * =================
 */

SELECT autos.modelo, COUNT(*) AS cant_ventas
FROM autos
INNER JOIN ventas
ON autos.id = ventas.auto_id
GROUP BY autos.id, autos.modelo
ORDER BY cant_ventas DESC
LIMIT 1;
 