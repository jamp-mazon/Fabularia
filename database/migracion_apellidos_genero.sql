ALTER TABLE usuarios
    ADD COLUMN apellidos VARCHAR(160) NOT NULL DEFAULT '' AFTER nombre;

ALTER TABLE libros
    ADD COLUMN genero VARCHAR(100) NOT NULL DEFAULT 'Sin genero' AFTER autor;

CREATE INDEX idx_libros_genero ON libros(genero);
