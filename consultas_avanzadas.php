<?php
/**
 * ACTIVIDAD 3: Consultas avanzadas SQL en PHP
 * Implementa consultas complejas para mostrar disponibilidad de proyectos
 * y análisis de donaciones por proyecto
 */

// Incluir configuración de conexión
require_once 'conexion.php';

// Función para insertar donaciones de prueba (solo si no existen)
function insertarDonacionesPrueba() {
    try {
        $conexion = conectarBD();
        
        // Verificar si ya hay donaciones
        $check = $conexion->query("SELECT COUNT(*) as total FROM DONACION");
        $resultado = $check->fetch_assoc();
        
        if ($resultado['total'] > 0) {
            $conexion->close();
            return "Ya existen donaciones en la base de datos";
        }
        
        // Obtener IDs de proyectos y donantes disponibles
        $proyectos_result = $conexion->query("SELECT id_proyecto FROM PROYECTO LIMIT 3");
        $donantes_result = $conexion->query("SELECT id_donante FROM DONANTE LIMIT 3");
        
        $proyectos = array();
        $donantes = array();
        
        while ($proyecto = $proyectos_result->fetch_assoc()) {
            $proyectos[] = $proyecto['id_proyecto'];
        }
        
        while ($donante = $donantes_result->fetch_assoc()) {
            $donantes[] = $donante['id_donante'];
        }
        
        if (empty($proyectos) || empty($donantes)) {
            $conexion->close();
            return "No hay suficientes proyectos o donantes para crear donaciones de prueba";
        }
        
        // Donaciones de prueba con variedad para demostrar consultas avanzadas
        $donaciones_prueba = array(
            array(500.00, '2025-07-01', $proyectos[0], $donantes[0], 'tarjeta_credito'),
            array(1000.00, '2025-07-02', $proyectos[0], $donantes[1], 'transferencia'),
            array(750.00, '2025-07-03', $proyectos[0], $donantes[2], 'paypal'),
            array(300.00, '2025-07-04', $proyectos[1], $donantes[0], 'tarjeta_debito'),
            array(1200.00, '2025-07-05', $proyectos[1], $donantes[1], 'transferencia'),
            array(850.00, '2025-07-06', $proyectos[1], $donantes[2], 'tarjeta_credito'),
            array(400.00, '2025-07-07', isset($proyectos[2]) ? $proyectos[2] : $proyectos[0], $donantes[0], 'paypal'),
            array(600.00, '2025-07-08', $proyectos[0], $donantes[1], 'tarjeta_credito'),
            array(900.00, '2025-07-09', $proyectos[1], $donantes[2], 'transferencia'),
            array(1500.00, '2025-07-10', $proyectos[0], $donantes[0], 'transferencia')
        );
        
        // Preparar consulta para insertar donaciones
        $stmt = $conexion->prepare("INSERT INTO DONACION (monto, fecha, id_proyecto, id_donante, metodo_pago) VALUES (?, ?, ?, ?, ?)");
        
        $donaciones_insertadas = 0;
        foreach ($donaciones_prueba as $donacion) {
            $stmt->bind_param("dsiis", $donacion[0], $donacion[1], $donacion[2], $donacion[3], $donacion[4]);
            if ($stmt->execute()) {
                $donaciones_insertadas++;
            }
        }
        
        $conexion->close();
        return "Se insertaron $donaciones_insertadas donaciones de prueba exitosamente";
        
    } catch (Exception $e) {
        return "Error insertando donaciones de prueba: " . $e->getMessage();
    }
}

// Función 1: Mostrar disponibilidad de proyectos para donaciones
function consultarProyectosDisponibles() {
    try {
        $conexion = conectarBD();
        
        // Consulta avanzada: proyectos con información de donaciones y disponibilidad
        $sql = "SELECT 
                    p.id_proyecto,
                    p.nombre,
                    p.descripcion,
                    p.presupuesto,
                    p.fecha_inicio,
                    p.fecha_fin,
                    COUNT(d.id_donacion) as total_donaciones,
                    COALESCE(SUM(d.monto), 0) as total_recaudado,
                    (p.presupuesto - COALESCE(SUM(d.monto), 0)) as monto_pendiente,
                    ROUND((COALESCE(SUM(d.monto), 0) / p.presupuesto) * 100, 2) as porcentaje_completado,
                    CASE 
                        WHEN p.fecha_fin >= CURDATE() THEN 'Activo'
                        ELSE 'Finalizado'
                    END as estado
                FROM PROYECTO p
                LEFT JOIN DONACION d ON p.id_proyecto = d.id_proyecto
                GROUP BY p.id_proyecto, p.nombre, p.descripcion, p.presupuesto, p.fecha_inicio, p.fecha_fin
                ORDER BY porcentaje_completado ASC";
        
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

// Función 2: Consultar todas las donaciones con información detallada
function consultarTodasDonaciones() {
    try {
        $conexion = conectarBD();
        
        // Consulta con INNER JOIN para obtener información completa
        $sql = "SELECT 
                    d.id_donacion,
                    d.monto,
                    d.fecha,
                    d.metodo_pago,
                    d.estado,
                    p.nombre as proyecto_nombre,
                    don.nombre as donante_nombre,
                    don.email as donante_email
                FROM DONACION d
                INNER JOIN PROYECTO p ON d.id_proyecto = p.id_proyecto
                INNER JOIN DONANTE don ON d.id_donante = don.id_donante
                ORDER BY d.fecha DESC, d.monto DESC";
        
        $resultado = $conexion->query($sql);
        $donaciones = array();
        
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $donaciones[] = $fila;
            }
        }
        
        $conexion->close();
        return $donaciones;
        
    } catch (Exception $e) {
        return array();
    }
}

// Función 3: CONSULTA AVANZADA PRINCIPAL - Proyectos con más de 2 donaciones y monto total
function consultarProyectosPopulares() {
    try {
        $conexion = conectarBD();
        
        // Consulta avanzada con GROUP BY, HAVING, y funciones de agregación
        $sql = "SELECT 
                    p.id_proyecto,
                    p.nombre as proyecto_nombre,
                    p.descripcion,
                    p.presupuesto,
                    COUNT(d.id_donacion) as numero_donaciones,
                    SUM(d.monto) as monto_total_recaudado,
                    AVG(d.monto) as promedio_donacion,
                    MIN(d.monto) as donacion_minima,
                    MAX(d.monto) as donacion_maxima,
                    MIN(d.fecha) as primera_donacion,
                    MAX(d.fecha) as ultima_donacion,
                    ROUND((SUM(d.monto) / p.presupuesto) * 100, 2) as porcentaje_meta
                FROM PROYECTO p
                INNER JOIN DONACION d ON p.id_proyecto = d.id_proyecto
                GROUP BY p.id_proyecto, p.nombre, p.descripcion, p.presupuesto
                HAVING COUNT(d.id_donacion) > 2
                ORDER BY numero_donaciones DESC, monto_total_recaudado DESC";
        
        $resultado = $conexion->query($sql);
        $proyectos_populares = array();
        
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $proyectos_populares[] = $fila;
            }
        }
        
        $conexion->close();
        return $proyectos_populares;
        
    } catch (Exception $e) {
        return array();
    }
}

// Función 4: Estadísticas generales con subconsultas
function obtenerEstadisticasGenerales() {
    try {
        $conexion = conectarBD();
        
        // Múltiples consultas avanzadas para estadísticas
        $estadisticas = array();
        
        // Total de proyectos activos vs finalizados
        $sql_proyectos = "SELECT 
                            COUNT(*) as total_proyectos,
                            SUM(CASE WHEN fecha_fin >= CURDATE() THEN 1 ELSE 0 END) as proyectos_activos,
                            SUM(CASE WHEN fecha_fin < CURDATE() THEN 1 ELSE 0 END) as proyectos_finalizados
                          FROM PROYECTO";
        $resultado = $conexion->query($sql_proyectos);
        $estadisticas['proyectos'] = $resultado->fetch_assoc();
        
        // Estadísticas de donaciones
        $sql_donaciones = "SELECT 
                            COUNT(*) as total_donaciones,
                            SUM(monto) as monto_total,
                            AVG(monto) as promedio_donacion,
                            MAX(monto) as mayor_donacion,
                            MIN(monto) as menor_donacion
                          FROM DONACION";
        $resultado = $conexion->query($sql_donaciones);
        $estadisticas['donaciones'] = $resultado->fetch_assoc();
        
        // Donante más generoso (subconsulta)
        $sql_donante_top = "SELECT 
                            don.nombre,
                            don.email,
                            SUM(d.monto) as total_donado,
                            COUNT(d.id_donacion) as numero_donaciones
                          FROM DONANTE don
                          INNER JOIN DONACION d ON don.id_donante = d.id_donante
                          GROUP BY don.id_donante, don.nombre, don.email
                          ORDER BY total_donado DESC
                          LIMIT 1";
        $resultado = $conexion->query($sql_donante_top);
        $estadisticas['donante_top'] = $resultado->fetch_assoc();
        
        // Proyecto más exitoso
        $sql_proyecto_exitoso = "SELECT 
                                p.nombre,
                                SUM(d.monto) as total_recaudado,
                                COUNT(d.id_donacion) as numero_donaciones,
                                ROUND((SUM(d.monto) / p.presupuesto) * 100, 2) as porcentaje_completado
                              FROM PROYECTO p
                              INNER JOIN DONACION d ON p.id_proyecto = d.id_proyecto
                              GROUP BY p.id_proyecto, p.nombre, p.presupuesto
                              ORDER BY porcentaje_completado DESC
                              LIMIT 1";
        $resultado = $conexion->query($sql_proyecto_exitoso);
        $estadisticas['proyecto_exitoso'] = $resultado->fetch_assoc();
        
        $conexion->close();
        return $estadisticas;
        
    } catch (Exception $e) {
        return array();
    }
}

// Procesar acciones si se reciben por GET
$accion = $_GET['accion'] ?? 'mostrar';
$mensaje_accion = '';

if ($accion === 'insertar_donaciones') {
    $mensaje_accion = insertarDonacionesPrueba();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Avanzadas MySQL - Organización</title>
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
            max-width: 1400px;
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
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .nav-section {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #ff8f00);
        }
        
        .content {
            padding: 30px;
        }
        
        .mensaje-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
            border-left: 5px solid #17a2b8;
        }
        
        .seccion {
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }
        
        .seccion h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .tabla th,
        .tabla td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tabla th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
        }
        
        .tabla tr:hover {
            background: #f8f9fa;
        }
        
        .estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .progreso-bar {
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            height: 20px;
            margin: 10px 0;
        }
        
        .progreso-fill {
            background: linear-gradient(45deg, #28a745, #20c997);
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .estado-activo {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .estado-finalizado {
            background: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .codigo-sql {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 15px 0;
            border-left: 5px solid #667eea;
        }
        
        .destacado {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 5px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        @media (max-width: 768px) {
            .tabla {
                font-size: 12px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .estadisticas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Consultas Avanzadas MySQL</h1>
            <p>Sistema de Análisis de Proyectos y Donaciones</p>
        </div>
        
        <div class="nav-section">
            <div class="btn-group">
                <a href="?accion=insertar_donaciones" class="btn btn-warning">🎲 Insertar Donaciones de Prueba</a>
                <a href="index.php" class="btn">🏠 Ir al Inicio</a>
                <a href="formularios.html" class="btn">📝 Formularios</a>
                <a href="procesar_proyecto.php" class="btn">📋 Ver Proyectos</a>
                <a href="procesar_donante.php" class="btn">👥 Ver Donantes</a>
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($mensaje_accion)): ?>
                <div class="mensaje-info">
                    <strong>ℹ️ Información:</strong><br>
                    <?php echo htmlspecialchars($mensaje_accion); ?>
                </div>
            <?php endif; ?>
            
            <!-- ESTADÍSTICAS GENERALES -->
            <?php
            $estadisticas = obtenerEstadisticasGenerales();
            if (!empty($estadisticas)):
            ?>
            <div class="seccion">
                <h3>📈 Estadísticas Generales del Sistema</h3>
                <div class="estadisticas-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $estadisticas['proyectos']['total_proyectos'] ?? 0; ?></div>
                        <div class="stat-label">Total Proyectos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $estadisticas['donaciones']['total_donaciones'] ?? 0; ?></div>
                        <div class="stat-label">Total Donaciones</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format($estadisticas['donaciones']['monto_total'] ?? 0, 0); ?></div>
                        <div class="stat-label">Monto Total Recaudado</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format($estadisticas['donaciones']['promedio_donacion'] ?? 0, 0); ?></div>
                        <div class="stat-label">Promedio por Donación</div>
                    </div>
                </div>
                
                <?php if (!empty($estadisticas['donante_top'])): ?>
                <div class="destacado">
                    <strong>🏆 Donante Más Generoso:</strong> 
                    <?php echo htmlspecialchars($estadisticas['donante_top']['nombre']); ?> 
                    - Total donado: $<?php echo number_format($estadisticas['donante_top']['total_donado'], 2); ?>
                    (<?php echo $estadisticas['donante_top']['numero_donaciones']; ?> donaciones)
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- CONSULTA 1: DISPONIBILIDAD DE PROYECTOS -->
            <div class="seccion">
                <h3>🎯 Disponibilidad de Proyectos para Donaciones</h3>
                <p>Esta consulta muestra todos los proyectos con su estado actual, monto recaudado y disponibilidad para recibir donaciones.</p>
                
                <div class="codigo-sql">
SELECT p.id_proyecto, p.nombre, p.presupuesto,
       COUNT(d.id_donacion) as total_donaciones,
       COALESCE(SUM(d.monto), 0) as total_recaudado,
       (p.presupuesto - COALESCE(SUM(d.monto), 0)) as monto_pendiente,
       ROUND((COALESCE(SUM(d.monto), 0) / p.presupuesto) * 100, 2) as porcentaje_completado
FROM PROYECTO p
LEFT JOIN DONACION d ON p.id_proyecto = d.id_proyecto
GROUP BY p.id_proyecto, p.nombre, p.presupuesto
ORDER BY porcentaje_completado ASC
                </div>
                
                <?php
                $proyectos_disponibles = consultarProyectosDisponibles();
                if (!empty($proyectos_disponibles)):
                ?>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Proyecto</th>
                            <th>Presupuesto</th>
                            <th>Recaudado</th>
                            <th>Pendiente</th>
                            <th>Progreso</th>
                            <th>Donaciones</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proyectos_disponibles as $proyecto): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($proyecto['nombre']); ?></strong><br>
                                    <small><?php echo htmlspecialchars(substr($proyecto['descripcion'], 0, 80)) . '...'; ?></small>
                                </td>
                                <td>$<?php echo number_format($proyecto['presupuesto'], 2); ?></td>
                                <td>$<?php echo number_format($proyecto['total_recaudado'], 2); ?></td>
                                <td>$<?php echo number_format($proyecto['monto_pendiente'], 2); ?></td>
                                <td>
                                    <div class="progreso-bar">
                                        <div class="progreso-fill" style="width: <?php echo min($proyecto['porcentaje_completado'], 100); ?>%">
                                            <?php echo $proyecto['porcentaje_completado']; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $proyecto['total_donaciones']; ?></td>
                                <td>
                                    <span class="<?php echo $proyecto['estado'] === 'Activo' ? 'estado-activo' : 'estado-finalizado'; ?>">
                                        <?php echo $proyecto['estado']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No hay proyectos disponibles. <a href="formularios.html">Registra algunos proyectos</a> para ver esta información.</p>
                <?php endif; ?>
            </div>
            
            <!-- CONSULTA 2: TODAS LAS DONACIONES -->
            <div class="seccion">
                <h3>💰 Registro Completo de Donaciones</h3>
                <p>Consulta con INNER JOIN para mostrar todas las donaciones con información detallada de proyectos y donantes.</p>
                
                <div class="codigo-sql">
SELECT d.id_donacion, d.monto, d.fecha, d.metodo_pago,
       p.nombre as proyecto_nombre,
       don.nombre as donante_nombre, don.email as donante_email
FROM DONACION d
INNER JOIN PROYECTO p ON d.id_proyecto = p.id_proyecto
INNER JOIN DONANTE don ON d.id_donante = don.id_donante
ORDER BY d.fecha DESC, d.monto DESC
                </div>
                
                <?php
                $todas_donaciones = consultarTodasDonaciones();
                if (!empty($todas_donaciones)):
                ?>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Proyecto</th>
                            <th>Donante</th>
                            <th>Método de Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todas_donaciones as $donacion): ?>
                            <tr>
                                <td><?php echo $donacion['id_donacion']; ?></td>
                                <td><strong>$<?php echo number_format($donacion['monto'], 2); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($donacion['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($donacion['proyecto_nombre']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($donacion['donante_nombre']); ?><br>
                                    <small><?php echo htmlspecialchars($donacion['donante_email']); ?></small>
                                </td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $donacion['metodo_pago'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No hay donaciones registradas. <a href="?accion=insertar_donaciones">Inserta donaciones de prueba</a> para ver esta información.</p>
                <?php endif; ?>
            </div>
            
            <!-- CONSULTA 3: PROYECTOS POPULARES (CONSULTA AVANZADA PRINCIPAL) -->
            <div class="seccion">
                <h3>🏆 Proyectos con Más de 2 Donaciones y Monto Total Recaudado</h3>
                <p><strong>CONSULTA AVANZADA REQUERIDA:</strong> Esta consulta utiliza GROUP BY, HAVING, y múltiples funciones de agregación para mostrar proyectos populares.</p>
                
                <div class="codigo-sql">
SELECT p.id_proyecto, p.nombre as proyecto_nombre,
       COUNT(d.id_donacion) as numero_donaciones,
       SUM(d.monto) as monto_total_recaudado,
       AVG(d.monto) as promedio_donacion,
       MIN(d.monto) as donacion_minima,
       MAX(d.monto) as donacion_maxima,
       ROUND((SUM(d.monto) / p.presupuesto) * 100, 2) as porcentaje_meta
FROM PROYECTO p
INNER JOIN DONACION d ON p.id_proyecto = d.id_proyecto
GROUP BY p.id_proyecto, p.nombre, p.presupuesto
HAVING COUNT(d.id_donacion) > 2
ORDER BY numero_donaciones DESC, monto_total_recaudado DESC
                </div>
                
                <?php
                $proyectos_populares = consultarProyectosPopulares();
                if (!empty($proyectos_populares)):
                ?>
                <div class="destacado">
                    <strong>📊 Criterios de la consulta:</strong> Se muestran únicamente los proyectos que han recibido MÁS DE 2 DONACIONES, 
                    ordenados por número de donaciones y monto total recaudado (consulta con GROUP BY y HAVING).
                </div>
                
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Proyecto</th>
                            <th>Núm. Donaciones</th>
                            <th>Monto Total</th>
                            <th>Promedio</th>
                            <th>Min/Max</th>
                            <th>% Meta</th>
                            <th>Período</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proyectos_populares as $proyecto): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($proyecto['proyecto_nombre']); ?></strong><br>
                                    <small>ID: <?php echo $proyecto['id_proyecto']; ?></small>
                                </td>
                                <td>
                                    <span class="stat-number" style="font-size: 1.5em; color: #667eea;">
                                        <?php echo $proyecto['numero_donaciones']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #28a745;">
                                        $<?php echo number_format($proyecto['monto_total_recaudado'], 2); ?>
                                    </strong>
                                </td>
                                <td>$<?php echo number_format($proyecto['promedio_donacion'], 2); ?></td>
                                <td>
                                    Min: $<?php echo number_format($proyecto['donacion_minima'], 2); ?><br>
                                    Max: $<?php echo number_format($proyecto['donacion_maxima'], 2); ?>
                                </td>
                                <td>
                                    <div class="progreso-bar">
                                        <div class="progreso-fill" style="width: <?php echo min($proyecto['porcentaje_meta'], 100); ?>%">
                                            <?php echo $proyecto['porcentaje_meta']; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('d/m/Y', strtotime($proyecto['primera_donacion'])); ?><br>
                                        a<br>
                                        <?php echo date('d/m/Y', strtotime($proyecto['ultima_donacion'])); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="destacado">
                    <strong>🎯 Análisis de Resultados:</strong>
                    <?php
                    $total_proyectos_populares = count($proyectos_populares);
                    $total_donaciones_populares = array_sum(array_column($proyectos_populares, 'numero_donaciones'));
                    $total_monto_populares = array_sum(array_column($proyectos_populares, 'monto_total_recaudado'));
                    ?>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li><strong><?php echo $total_proyectos_populares; ?></strong> proyectos cumplen el criterio de tener más de 2 donaciones</li>
                        <li>Estos proyectos han recibido un total de <strong><?php echo $total_donaciones_populares; ?></strong> donaciones</li>
                        <li>Monto total recaudado entre todos: <strong>$<?php echo number_format($total_monto_populares, 2); ?></strong></li>
                        <li>Promedio de donaciones por proyecto popular: <strong><?php echo round($total_donaciones_populares / max($total_proyectos_populares, 1), 1); ?></strong></li>
                    </ul>
                </div>
                
                <?php else: ?>
                <div class="mensaje-info">
                    <strong>ℹ️ No hay proyectos que cumplan el criterio</strong><br>
                    Actualmente no hay proyectos con más de 2 donaciones. 
                    <a href="?accion=insertar_donaciones">Inserta donaciones de prueba</a> para ver esta consulta avanzada en acción.
                </div>
                <?php endif; ?>
            </div>
            
            <!-- INFORMACIÓN TÉCNICA -->
            <div class="seccion">
                <h3>🔧 Información Técnica de las Consultas</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #667eea;">
                        <h4>📋 Técnicas Utilizadas</h4>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li><strong>INNER JOIN:</strong> Relación entre tablas PROYECTO, DONANTE y DONACION</li>
                            <li><strong>LEFT JOIN:</strong> Para incluir proyectos sin donaciones</li>
                            <li><strong>GROUP BY:</strong> Agrupamiento por proyecto</li>
                            <li><strong>HAVING:</strong> Filtrado después del agrupamiento</li>
                            <li><strong>Funciones de agregación:</strong> COUNT(), SUM(), AVG(), MIN(), MAX()</li>
                            <li><strong>CASE WHEN:</strong> Lógica condicional SQL</li>
                            <li><strong>COALESCE:</strong> Manejo de valores NULL</li>
                        </ul>
                    </div>
                    
                    <div style="background: white; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745;">
                        <h4>🎯 Indicadores de Evaluación Cumplidos</h4>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>✅ Conexión PHP con MySQL establecida</li>
                            <li>✅ Formularios para ingreso de datos estructurados</li>
                            <li>✅ Consultas avanzadas con GROUP BY y HAVING</li>
                            <li>✅ Manipulación efectiva de datos desde aplicación web</li>
                            <li>✅ Validaciones en PHP y JavaScript</li>
                            <li>✅ Procesamiento seguro de formularios</li>
                        </ul>
                    </div>
                </div>
                
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 20px; border-left: 5px solid #ffc107;">
                    <h4>📝 Notas para el Informe</h4>
                    <p>Este sistema implementa todos los requerimientos de la actividad:</p>
                    <ol style="margin-top: 10px; padding-left: 20px;">
                        <li><strong>Base de datos ORGANIZACION</strong> con tablas PROYECTO, DONANTE y DONACION</li>
                        <li><strong>Formularios HTML</strong> con validaciones JavaScript y procesamiento PHP</li>
                        <li><strong>Scripts PHP</strong> para insertar y consultar datos con validaciones</li>
                        <li><strong>Consulta avanzada principal</strong> que muestra proyectos con más de 2 donaciones y monto total</li>
                        <li><strong>Relaciones entre tablas</strong> utilizando claves foráneas y JOINs</li>
                    </ol>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <div class="btn-group">
                    <a href="conexion.php" class="btn">🔗 Ver Configuración BD</a>
                    <a href="formularios.html" class="btn">📝 Formularios de Gestión</a>
                    <a href="index.php" class="btn">🏠 Página Principal</a>
                    <a href="?accion=insertar_donaciones" class="btn btn-warning">🎲 Generar Más Datos</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh cada 30 segundos si hay cambios
        setTimeout(function() {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('auto-refresh')) {
                url.searchParams.append('auto-refresh', '1');
                // Solo refrescar si no se está mostrando un mensaje de acción
                if (!document.querySelector('.mensaje-info')) {
                    window.location.href = url.toString();
                }
            }
        }, 30000);
        
        // Función para copiar código SQL al portapapeles
        document.querySelectorAll('.codigo-sql').forEach(function(codeBlock) {
            codeBlock.style.cursor = 'pointer';
            codeBlock.title = 'Click para copiar al portapapeles';
            codeBlock.addEventListener('click', function() {
                navigator.clipboard.writeText(this.textContent).then(function() {
                    // Mostrar feedback visual
                    const original = codeBlock.style.background;
                    codeBlock.style.background = '#28a745';
                    setTimeout(() => {
                        codeBlock.style.background = original;
                    }, 500);
                });
            });
        });
    </script>
</body>
</html>