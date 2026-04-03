CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    apellidos VARCHAR(160) NOT NULL,
    telefono VARCHAR(30) NULL,
    telegram_chat_id VARCHAR(40) NULL,
    telegram_usuario VARCHAR(120) NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS libros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL,
    titulo VARCHAR(160) NOT NULL,
    autor VARCHAR(160) NOT NULL,
    genero VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo_intercambio TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_libro_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prestamos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_libro INT UNSIGNED NOT NULL,
    id_usuario_dueno INT UNSIGNED NOT NULL,
    id_usuario_prestado INT UNSIGNED NOT NULL,
    fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion DATETIME NULL,
    CONSTRAINT fk_prestamo_libro
        FOREIGN KEY (id_libro) REFERENCES libros(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_prestamo_usuario_dueno
        FOREIGN KEY (id_usuario_dueno) REFERENCES usuarios(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_prestamo_usuario_prestado
        FOREIGN KEY (id_usuario_prestado) REFERENCES usuarios(id)
        ON DELETE RESTRICT,
    CONSTRAINT chk_prestamo_usuarios_distintos
        CHECK (id_usuario_dueno <> id_usuario_prestado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_libros_titulo ON libros(titulo);
CREATE INDEX idx_libros_autor ON libros(autor);
CREATE INDEX idx_libros_genero ON libros(genero);
CREATE UNIQUE INDEX ux_usuarios_telegram_chat_id ON usuarios(telegram_chat_id);
CREATE INDEX idx_prestamos_libro ON prestamos(id_libro);
CREATE INDEX idx_prestamos_prestado ON prestamos(id_usuario_prestado);
