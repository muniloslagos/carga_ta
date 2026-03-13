-- =====================================================
-- INSERTAR ITEMS MENSUALES DE TRANSPARENCIA ACTIVA
-- direccion_id = NULL (asignar después)
-- =====================================================

-- Paso 1: Eliminar restricción UNIQUE en numeracion (permite múltiples items con el mismo número)
ALTER TABLE items_transparencia DROP INDEX `numeracion`;

-- Paso 2: Insertar items (direccion_id = NULL, asignar después)
INSERT INTO `items_transparencia` (`numeracion`, `nombre`, `periodicidad`, `direccion_id`, `activo`) VALUES
(11, 'Modificaciones presupuestarias', 'mensual', NULL, 1),
(11, 'Libro diario municipal', 'mensual', NULL, 1),
(11, 'Informe analítico de variaciones de la deuda - Municipalidad', 'mensual', NULL, 1),
(11, 'Informe analítico de variaciones de la ejecución presupuestaria - Municipalidad', 'mensual', NULL, 1),
(11, 'Informe analítico de variaciones de la ejecución presupuestaria de iniciativas de inversión - Municipalidad', 'mensual', NULL, 1),
(11, 'Balance de comprobación y de saldos agregado - Municipalidad', 'mensual', NULL, 1),
(11, 'Balance de comprobación y de saldos desagregado - Municipalidad', 'mensual', NULL, 1),
(11, 'Información estadística sobre bonificaciones - Municipalidad', 'mensual', NULL, 1),
(11, 'Balance de ejecución presupuestaria - Ingresos - Municipalidad', 'mensual', NULL, 1),
(11, 'Balance de ejecución presupuestaria - Gastos - Municipalidad', 'mensual', NULL, 1),
(11, 'Gastos de Representación, Protocolo y Ceremonial - Municipalidad', 'mensual', NULL, 1),
(11, 'Gastos de Avisaje y publicidad - Municipalidad', 'mensual', NULL, 1),
(11, 'Gastos de Avisaje y publicidad - desglose de gastos - Municipalidad', 'mensual', NULL, 1),
(11, 'Estado de situación financiera - Municipalidad', 'mensual', NULL, 1),
(11, 'Pasivos - Municipalidad', 'mensual', NULL, 1),
(7,  'Patentes comerciales', 'mensual', NULL, 1),
(4,  'Información relativa a las autoridades que ejerzan cargos de elección popular (Dieta Concejales)', 'mensual', NULL, 1),
(4,  'Remuneraciones Planta - Municipalidad', 'mensual', NULL, 1),
(4,  'Remuneraciones Contrata - Municipalidad', 'mensual', NULL, 1),
(4,  'Remuneraciones Código del Trabajo - Municipalidad', 'mensual', NULL, 1),
(4,  'Remuneraciones Honorarios - Municipalidad', 'mensual', NULL, 1),
(4,  'Informes Honorarios - Municipalidad', 'mensual', NULL, 1),
(4,  'Caja Chica - Fabiola Caceres (BODEGA) - Mónica Chavez', 'mensual', NULL, 1),
(4,  'Caja Chica - Elizabet Valle (SECPLAN)', 'mensual', NULL, 1),
(4,  'Caja Chica - Tamara Fernandez (ADMINISTRACION)', 'mensual', NULL, 1),
(7,  'Permisos y autorizaciones del Art. 116 bis C LGUC - Obras', 'mensual', NULL, 1),
(7,  'Planilla excel permisos de obras', 'mensual', NULL, 1),
(7,  'Permisos de obras en PDF', 'mensual', NULL, 1),
(11, 'Modificaciones presupuestarias - Salud', 'mensual', NULL, 1),
(11, 'Informe analítico de variaciones de la deuda - Salud', 'mensual', NULL, 1),
(11, 'Informe analítico de variaciones de la ejecución presupuestaria - Salud', 'mensual', NULL, 1),
(11, 'Gastos de Avisaje y publicidad - desglose de gastos - Salud', 'mensual', NULL, 1),
(11, 'Balance de comprobación y de saldos agregado - Salud', 'mensual', NULL, 1),
(11, 'Balance de comprobación y de saldos desagregado - Salud', 'mensual', NULL, 1),
(11, 'Información estadística sobre bonificaciones - Salud', 'mensual', NULL, 1),
(11, 'Balance de ejecución presupuestaria - Ingresos - Salud', 'mensual', NULL, 1),
(11, 'Balance de ejecución presupuestaria - Gastos - Salud', 'mensual', NULL, 1),
(11, 'Gastos de Representación, Protocolo y Ceremonial - Salud', 'mensual', NULL, 1),
(11, 'Gastos de Avisaje y publicidad - Salud', 'mensual', NULL, 1),
(11, 'Estado de situación financiera - Salud', 'mensual', NULL, 1),
(11, 'Pasivos - Salud', 'mensual', NULL, 1),
(4,  'Remuneraciones Planta - Salud', 'mensual', NULL, 1),
(4,  'Remuneraciones Contrata - Salud', 'mensual', NULL, 1),
(4,  'Remuneraciones Honorarios - Salud', 'mensual', NULL, 1),
(4,  'Informes Honorarios en pdf - Salud', 'mensual', NULL, 1),
(5,  'Caja Chica - Lorena Gonzalez - Salud', 'mensual', NULL, 1),
(5,  'Caja Chica - Victoria Esparza - Salud', 'mensual', NULL, 1),
(11, 'Informe fondos transitorios Royalty a la Minería - Ley de Presupuestos 2024', 'mensual', NULL, 1),
(4,  'Escala de Remuneraciones - Municipalidad', 'mensual', NULL, 1),
(4,  'Escala de Remuneraciones - Salud', 'mensual', NULL, 1),
(7,  'Autorizaciones', 'mensual', NULL, 1),
(7,  'Convenios con otras entidades', 'mensual', NULL, 1),
(7,  'Ordenanzas', 'mensual', NULL, 1),
(7,  'Concursos Publicos', 'mensual', NULL, 1),
(7,  'Comodatos', 'mensual', NULL, 1),
(7,  'Planes Comunales', 'mensual', NULL, 1),
(0,  'Actas ordinarias Concejo Municipal', 'mensual', NULL, 1),
(0,  'Actas extraordinarias Concejo Municipal', 'mensual', NULL, 1),
(4,  'Autoridades capacitadas en prevención de violencia y acoso en el trabajo', 'mensual', NULL, 1),
(6,  'Otras Transferencias', 'mensual', NULL, 1),
(9,  'Información Subsidios y beneficios Propios', 'mensual', NULL, 1),
(5,  'Actas de evaluación de comisiones evaluadoras de licitaciones y compras públicas', 'mensual', NULL, 1),
(9,  'Información Subsidios y Beneficios como Intermediario', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Beca Municipal', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Asistencia social a personas naturales', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Familias seguridades y oportunidades', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Subsidio agua potable y alcantarillado - Rural', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Subsidio agua potable y alcantarillado - Urbano', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Programa Autoconsumo', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Programa Habitabilidad', 'mensual', NULL, 1),
(9,  'Nómina Beneficiarios - Subsidio Único Familiar', 'mensual', NULL, 1),
(13, 'Entidades en que tenga participación, representación o intervención el organismo', 'mensual', NULL, 1),
(12, 'Auditorías al ejercicio presupuestario y aclaraciones', 'mensual', NULL, 1),
(0,  'Registros públicos de organizaciones vigentes - Ley N°21.146', 'mensual', NULL, 1);

-- =====================================================
-- VERIFICAR LOS ITEMS INSERTADOS
-- =====================================================
SELECT id, numeracion, nombre, periodicidad, direccion_id
FROM items_transparencia
WHERE direccion_id IS NULL
ORDER BY numeracion, nombre;
