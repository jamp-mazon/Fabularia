-- Normaliza telefonos existentes para integraciones externas como n8n.
-- Mantiene el prefijo + cuando estaba al principio y elimina espacios, guiones y parentesis.

UPDATE usuarios
SET telefono = CASE
    WHEN telefono IS NULL OR TRIM(telefono) = '' THEN NULL
    WHEN LEFT(TRIM(telefono), 1) = '+' THEN CONCAT(
        '+',
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(SUBSTRING(TRIM(telefono), 2), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '')
    )
    ELSE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(telefono), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '')
END;
