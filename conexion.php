<?php

// Script de conexión segura con MySQL
// Base de datos: ORGANIZACION
// Tablas: PROYECTO, DONANTE, DONACION
 

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_iacc');  
define('DB_PASS', 'iacc123456'); 
define('DB_NAME', 'ORGANIZACION');

// Función para establecer conexión con MySQLi

function conectarBD() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Verificar conexión
    if ($conexion->connect_error) {
        die("ERROR DE CONEXIÓN: " . $conexion->connect_error);
    }

    $conexion->set_charset("utf8");
    
    return $conexion;
}

// Función para insertar datos de prueba

function insertarDatosPrueba() {
    $conexion = conectarBD();
    
    // Insertar proyectos
    $proyectos = [
        ['Educación para Todos', 'Programa de becas y materiales educativos para niños en situación vulnerable', 50000.00, '2025-01-01', '2025-12-31'],
        ['Alimentación Comunitaria', 'Comedores comunitarios en barrios de bajos recursos', 30000.00, '2025-02-01', '2025-11-30'],
        ['Vivienda Digna', 'Construcción de viviendas sociales para familias necesitadas', 75000.00, '2025-03-01', '2026-02-28']
    ];
    
    foreach ($proyectos as $proyecto) {
        $stmt = $conexion->prepare("INSERT INTO PROYECTO (nombre, descripcion, presupuesto, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $proyecto[0], $proyecto[1], $proyecto[2], $proyecto[3], $proyecto[4]);
        $stmt->execute();
    }
    
    // Insertar donantes
    $donantes = [
        ['Juan Pérez García', 'juan.perez@email.com', 'Av. Principal 123, Santiago', '+56912345678'],
        ['María González López', 'maria.gonzalez@email.com', 'Calle Las Flores 456, Providencia', '+56987654321'],
        ['Carlos Rodríguez Silva', 'carlos.rodriguez@email.com', 'Pasaje Los Olivos 789, Las Condes', '+56911223344']
    ];
    
    foreach ($donantes as $donante) {
        $stmt = $conexion->prepare("INSERT INTO DONANTE (nombre, email, direccion, telefono) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $donante[0], $donante[1], $donante[2], $donante[3]);
        $stmt->execute();
    }
    
    $conexion->close();
    echo "Datos de prueba insertados correctamente!<br>";
}

// Ejecutar solo si se llama directamente
if (basename($_SERVER['PHP_SELF']) == 'conexion.php') {
    echo "<h2>Configuración de Base de Datos - Organización Sin Fines de Lucro</h2>";
    
    // Insertar datos de prueba
    insertarDatosPrueba();
    
    // Probar conexión
    echo "<h3>Prueba de Conexión:</h3>";
    $conn = conectarBD();
    echo "✅ Conexión MySQLi exitosa<br>";
    $conn->close();
}
?>