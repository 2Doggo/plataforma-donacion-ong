<?php
/**
 * ACTIVIDAD 2: Script para procesar y validar datos de proyectos
 * Implementa formularios HTML para gestionar información sobre proyectos
 */

// Incluir configuración de conexión
require_once 'conexion.php';

// Función para limpiar y validar datos de entrada
function limpiarDatos($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Función para validar datos del proyecto
function validarProyecto($datos) {
    $errores = array();
    
    // Validar nombre del proyecto
    if (empty($datos['nombre_proyecto'])) {
        $errores[] = "El nombre del proyecto es obligatorio";
    } elseif (strlen($datos['nombre_proyecto']) < 5) {
        $errores[] = "El nombre del proyecto debe tener al menos 5 caracteres";
    } elseif (strlen($datos['nombre_proyecto']) > 200) {
        $errores[] = "El nombre del proyecto no puede exceder 200 caracteres";
    }
    
    // Validar descripción
    if (empty($datos['descripcion_proyecto'])) {
        $errores[] = "La descripción del proyecto es obligatoria";
    } elseif (strlen($datos['descripcion_proyecto']) < 20) {
        $errores[] = "La descripción debe tener al menos 20 caracteres";
    } elseif (strlen($datos['descripcion_proyecto']) > 1000) {
        $errores[] = "La descripción no puede exceder 1000 caracteres";
    }
    
    // Validar presupuesto
    if (empty($datos['presupuesto'])) {
        $errores[] = "El presupuesto es obligatorio";
    } elseif (!is_numeric($datos['presupuesto'])) {
        $errores[] = "El presupuesto debe ser un número válido";
    } elseif (floatval($datos['presupuesto']) < 1000) {
        $errores[] = "El presupuesto mínimo es $1,000";
    } elseif (floatval($datos['presupuesto']) > 1000000) {
        $errores[] = "El presupuesto máximo es $1,000,000";
    }
    
    // Validar fechas
    if (empty($datos['fecha_inicio'])) {
        $errores[] = "La fecha de inicio es obligatoria";
    } elseif (!DateTime::createFromFormat('Y-m-d', $datos['fecha_inicio'])) {
        $errores[] = "Formato de fecha de inicio inválido";
    }
    
    if (empty($datos['fecha_fin'])) {
        $errores[] = "La fecha de fin es obligatoria";
    } elseif (!DateTime::createFromFormat('Y-m-d', $datos['fecha_fin'])) {
        $errores[] = "Formato de fecha de fin inválido";
    }
    
    // Validar que la fecha de fin sea posterior a la de inicio
    if (!empty($datos['fecha_inicio']) && !empty($datos['fecha_fin'])) {
        $fecha_inicio = new DateTime($datos['fecha_inicio']);
        $fecha_fin = new DateTime($datos['fecha_fin']);
        $hoy = new DateTime();
        
        if ($fecha_inicio < $hoy) {
            $errores[] = "La fecha de inicio no puede ser anterior a hoy";
        }
        
        if ($fecha_fin <= $fecha_inicio) {
            $errores[] = "La fecha de fin debe ser posterior a la fecha de inicio";
        }
    }
    
    return $errores;
}

// Función para insertar proyecto en la base de datos
function insertarProyecto($datos) {
    try {
        $conexion = conectarBD();
        
        // Verificar si ya existe un proyecto con el mismo nombre
        $stmt_check = $conexion->prepare("SELECT id_proyecto FROM PROYECTO WHERE nombre = ?");
        $stmt_check->bind_param("s", $datos['nombre_proyecto']);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result();
        
        if ($resultado->num_rows > 0) {
            return array(
                'exito' => false,
                'mensaje' => 'Ya existe un proyecto con ese nombre'
            );
        }
        
        // Preparar consulta para insertar
        $sql = "INSERT INTO PROYECTO (nombre, descripcion, presupuesto, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conexion->error);
        }
        
        // Vincular parámetros
        $stmt->bind_param("ssdss", 
            $datos['nombre_proyecto'],
            $datos['descripcion_proyecto'],
            $datos['presupuesto'],
            $datos['fecha_inicio'],
            $datos['fecha_fin']
        );
        
        // Ejecutar consulta
        if ($stmt->execute()) {
            $id_proyecto = $conexion->insert_id;
            $stmt->close();
            $conexion->close();
            
            return array(
                'exito' => true,
                'mensaje' => 'Proyecto registrado exitosamente',
                'id_proyecto' => $id_proyecto
            );
        } else {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        return array(
            'exito' => false,
            'mensaje' => 'Error en la base de datos: ' . $e->getMessage()
        );
    }
}

// Procesar formulario cuando se reciben datos POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar y obtener datos del formulario
    $datos_proyecto = array(
        'nombre_proyecto' => limpiarDatos($_POST['nombre_proyecto'] ?? ''),
        'descripcion_proyecto' => limpiarDatos($_POST['descripcion_proyecto'] ?? ''),
        'presupuesto' => limpiarDatos($_POST['presupuesto'] ?? ''),
        'fecha_inicio' => limpiarDatos($_POST['fecha_inicio'] ?? ''),
        'fecha_fin' => limpiarDatos($_POST['fecha_fin'] ?? '')
    );
    
    // Validar datos
    $errores = validarProyecto($datos_proyecto);
    
    if (empty($errores)) {
        // Si no hay errores, insertar en base de datos
        $resultado = insertarProyecto($datos_proyecto);
        
        if ($resultado['exito']) {
            $mensaje_exito = $resultado['mensaje'] . " (ID: " . $resultado['id_proyecto'] . ")";
            $mostrar_exito = true;
        } else {
            $errores[] = $resultado['mensaje'];
        }
    }
}

// Función para obtener todos los proyectos (para mostrar en tabla)
function obtenerProyectos() {
    try {
        $conexion = conectarBD();
        $sql = "SELECT id_proyecto, nombre, descripcion, presupuesto, fecha_inicio, fecha_fin, 
                       fecha_creacion FROM PROYECTO ORDER BY fecha_creacion DESC";
        $resultado = $conexion->query($sql);
        
        $proyectos = array();
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $proyectos[] = $fila;
            }
        }
        
        $conexion->close();
        return $proyectos;
        
    } catch (Exception $e) {
        return array();
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - Gestión de Proyectos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            border-left: 5px solid #28a745;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            border-left: 5px solid #dc3545;
        }
        
        .error-list {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .tabla-proyectos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tabla-proyectos th,
        .tabla-proyectos td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tabla-proyectos th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .tabla-proyectos tr:hover {
            background: #f8f9fa;
        }
        
        .proyecto-detalles {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Gestión de Proyectos</h1>
            <p>Resultado del Procesamiento</p>
        </div>
        
        <div class="content">
            <?php if (isset($mostrar_exito) && $mostrar_exito): ?>
                <div class="mensaje-exito">
                    <strong>✅ ¡Éxito!</strong><br>
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                    
                    <div class="proyecto-detalles">
                        <h4>📋 Detalles del Proyecto Registrado:</h4>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($datos_proyecto['nombre_proyecto']); ?></p>
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($datos_proyecto['descripcion_proyecto']); ?></p>
                        <p><strong>Presupuesto:</strong> $<?php echo number_format($datos_proyecto['presupuesto'], 2); ?></p>
                        <p><strong>Período:</strong> <?php echo $datos_proyecto['fecha_inicio']; ?> al <?php echo $datos_proyecto['fecha_fin']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errores)): ?>
                <div class="mensaje-error">
                    <strong>❌ Errores encontrados:</strong>
                    <ul class="error-list">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <h3>📊 Proyectos Registrados Actualmente:</h3>
            <?php
            $proyectos = obtenerProyectos();
            if (!empty($proyectos)):
            ?>
                <table class="tabla-proyectos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Presupuesto</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Registrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proyectos as $proyecto): ?>
                            <tr>
                                <td><?php echo $proyecto['id_proyecto']; ?></td>
                                <td><?php echo htmlspecialchars($proyecto['nombre']); ?></td>
                                <td>$<?php echo number_format($proyecto['presupuesto'], 2); ?></td>
                                <td><?php echo $proyecto['fecha_inicio']; ?></td>
                                <td><?php echo $proyecto['fecha_fin']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($proyecto['fecha_creacion'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay proyectos registrados aún.</p>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="formularios.html" class="btn">🔙 Volver al Formulario</a>
                <a href="index.php" class="btn">🏠 Ir al Inicio</a>
                <a href="consultas_avanzadas.php" class="btn">📊 Ver Consultas Avanzadas</a>
            </div>
        </div>
    </div>
</body>
</html>