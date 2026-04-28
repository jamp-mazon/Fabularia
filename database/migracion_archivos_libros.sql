ALTER TABLE libros
    ADD COLUMN archivo_ruta VARCHAR(500) NULL AFTER descripcion,
    ADD COLUMN archivo_mime VARCHAR(120) NULL AFTER archivo_ruta,
    ADD COLUMN archivo_nombre_original VARCHAR(255) NULL AFTER archivo_mime;
