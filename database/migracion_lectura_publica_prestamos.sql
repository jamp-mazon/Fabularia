ALTER TABLE prestamos
    ADD COLUMN lectura_publica TINYINT(1) NOT NULL DEFAULT 0 AFTER id_usuario_prestado,
    ADD COLUMN lectura_fuente VARCHAR(40) NULL AFTER lectura_publica,
    ADD COLUMN lectura_id_externo VARCHAR(120) NULL AFTER lectura_fuente,
    ADD COLUMN lectura_url VARCHAR(700) NULL AFTER lectura_id_externo,
    ADD COLUMN lectura_formato VARCHAR(80) NULL AFTER lectura_url,
    ADD COLUMN pagina_lectura_actual INT UNSIGNED NOT NULL DEFAULT 1 AFTER lectura_formato,
    ADD COLUMN paginas_lectura_totales INT UNSIGNED NULL AFTER pagina_lectura_actual,
    ADD COLUMN fecha_actualizacion_lectura DATETIME NULL AFTER paginas_lectura_totales;

