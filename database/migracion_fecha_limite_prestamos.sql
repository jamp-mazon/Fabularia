ALTER TABLE prestamos
    ADD COLUMN fecha_limite_devolucion DATETIME NULL AFTER fecha_prestamo;

UPDATE prestamos
SET fecha_limite_devolucion = DATE_ADD(fecha_prestamo, INTERVAL 14 DAY)
WHERE fecha_limite_devolucion IS NULL;
