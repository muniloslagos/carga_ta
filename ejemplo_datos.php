<?php
/**
 * Script para insertar datos de ejemplo
 * Ejecutar después de setup.php
 * Acceder en: http://localhost/cumplimiento/ejemplo_datos.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Verificar que la BD existe
if (!$conn->select_db(DB_NAME)) {
    die("Error: La base de datos no existe. Ejecute setup.php primero");
}

echo "<h2>Insertando datos de ejemplo...</h2>";

// 1. Crear direcciones
$direcciones_sql = "INSERT IGNORE INTO direcciones (nombre, descripcion) VALUES
('Dirección de Planeación', 'Responsable de la planeación estratégica'),
('Dirección de Finanzas', 'Gestión de recursos financieros'),
('Dirección de Recursos Humanos', 'Administración del talento humano'),
('Dirección de Tecnología', 'Infraestructura tecnológica'),
('Dirección de Servicios al Ciudadano', 'Atención al usuario')";

if ($conn->query($direcciones_sql)) {
    echo "<p style='color:green;'>✓ Direcciones creadas</p>";
} else {
    echo "<p style='color:red;'>✗ Error al crear direcciones: " . $conn->error . "</p>";
}

// 2. Crear usuarios de ejemplo
$usuarios = [
    ['nombre' => 'Revisor Director', 'email' => 'revisor@cumplimiento.local', 'password' => password_hash('revisor123', PASSWORD_DEFAULT), 'perfil' => 'director_revisor', 'direccion_id' => 1],
    ['nombre' => 'Cargador 1', 'email' => 'cargador1@cumplimiento.local', 'password' => password_hash('cargador123', PASSWORD_DEFAULT), 'perfil' => 'cargador_informacion', 'direccion_id' => 2],
    ['nombre' => 'Cargador 2', 'email' => 'cargador2@cumplimiento.local', 'password' => password_hash('cargador123', PASSWORD_DEFAULT), 'perfil' => 'cargador_informacion', 'direccion_id' => 3],
    ['nombre' => 'Publicador', 'email' => 'publicador@cumplimiento.local', 'password' => password_hash('publicador123', PASSWORD_DEFAULT), 'perfil' => 'publicador', 'direccion_id' => 4]
];

foreach ($usuarios as $user) {
    $sql = "INSERT IGNORE INTO usuarios (nombre, email, password, perfil, direccion_id, activo) 
            VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $user['nombre'], $user['email'], $user['password'], $user['perfil'], $user['direccion_id']);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Usuario creado: {$user['email']}</p>";
    } else {
        echo "<p style='color:orange;'>⚠ Usuario ya existe: {$user['email']}</p>";
    }
}

// 3. Crear items de transparencia
$items_sql = "INSERT IGNORE INTO items_transparencia (numeracion, nombre, descripcion, direccion_id, periodicidad) VALUES
('1', 'Información de Identificación Institucional', 'Datos básicos de la institución', 1, 'anual'),
('1.1', 'Misión y Visión', 'Documentos de misión y visión institucional', 1, 'anual'),
('1.2', 'Estructura Administrativa', 'Organigrama y estructura organizacional', 1, 'anual'),
('2', 'Normatividad', 'Leyes y reglamentos aplicables', 1, 'trimestral'),
('2.1', 'Reglamentos Internos', 'Reglamentos de operación', 1, 'trimestral'),
('3', 'Información Financiera', 'Estados financieros y presupuestales', 2, 'mensual'),
('3.1', 'Presupuesto del Periodo', 'Documentos presupuestales', 2, 'trimestral'),
('3.2', 'Ingresos y Gastos', 'Estados de ingresos y egresos', 2, 'mensual'),
('4', 'Información de Contrataciones', 'Contratos y procesos de selección', 3, 'mensual'),
('4.1', 'Procesos de Selección', 'Convocatorias públicas', 3, 'ocurrencia'),
('5', 'Información de Recursos Humanos', 'Personal y nómina', 3, 'trimestral'),
('5.1', 'Directorio de Empleados', 'Listado de personal activo', 3, 'semestral'),
('6', 'Planes, Programas y Proyectos', 'Documentos de planeación', 1, 'anual'),
('7', 'Resultados de Evaluaciones', 'Evaluaciones institucionales', 1, 'semestral'),
('8', 'Información Tecnológica', 'Sistemas de información', 4, 'trimestral')";

if ($conn->query($items_sql)) {
    echo "<p style='color:green;'>✓ Items de transparencia creados</p>";
} else {
    echo "<p style='color:red;'>✗ Error al crear items: " . $conn->error . "</p>";
}

// 4. Asignar usuarios a items
$asignaciones_sql = "INSERT IGNORE INTO item_usuarios (item_id, usuario_id) 
SELECT i.id, u.id FROM items_transparencia i, usuarios u 
WHERE u.perfil = 'cargador_informacion' AND i.id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15)";

if ($conn->query($asignaciones_sql)) {
    echo "<p style='color:green;'>✓ Usuarios asignados a items</p>";
} else {
    echo "<p style='color:orange;'>⚠ Algunos items ya estaban asignados</p>";
}

echo "<hr>";
echo "<h2>✓ Datos de ejemplo insertados correctamente</h2>";
echo "<p><strong>Usuarios de prueba:</strong></p>";
echo "<ul>";
echo "<li>Email: revisor@cumplimiento.local | Contraseña: revisor123 | Perfil: Director Revisor</li>";
echo "<li>Email: cargador1@cumplimiento.local | Contraseña: cargador123 | Perfil: Cargador</li>";
echo "<li>Email: cargador2@cumplimiento.local | Contraseña: cargador123 | Perfil: Cargador</li>";
echo "<li>Email: publicador@cumplimiento.local | Contraseña: publicador123 | Perfil: Publicador</li>";
echo "</ul>";
echo "<p><a href='login.php'>Ir a iniciar sesión</a></p>";

$conn->close();
?>
