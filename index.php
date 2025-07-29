<?php
// Incluir configuración de sesiones seguras
require_once 'config_sesiones.php';

// Iniciar sesión segura
iniciarSesionSegura();

// Clase para gestionar el carrito de donaciones
class CarritoDonaciones {
    private $sesionKey = 'carrito_donaciones';
    
    public function __construct() {
        if (!isset($_SESSION[$this->sesionKey])) {
            $_SESSION[$this->sesionKey] = array();
        }
    }
    
    // Agregar donación al carrito
    public function agregarDonacion($proyecto_id, $monto, $donante, $email) {
        $donacion = array(
            'id' => uniqid('don_'),
            'proyecto_id' => $proyecto_id,
            'monto' => floatval($monto),
            'donante' => htmlspecialchars($donante),
            'email' => filter_var($email, FILTER_SANITIZE_EMAIL),
            'fecha_agregado' => time(),
            'estado' => 'pendiente'
        );
        
        $_SESSION[$this->sesionKey][] = $donacion;
        return $donacion['id'];
    }
    
    // Obtener todas las donaciones del carrito
    public function obtenerDonaciones() {
        return $_SESSION[$this->sesionKey];
    }
    
    // Calcular total del carrito
    public function calcularTotal() {
        $total = 0;
        foreach ($_SESSION[$this->sesionKey] as $donacion) {
            $total += $donacion['monto'];
        }
        return $total;
    }
    
    // Eliminar donación del carrito
    public function eliminarDonacion($donacion_id) {
        foreach ($_SESSION[$this->sesionKey] as $key => $donacion) {
            if ($donacion['id'] === $donacion_id) {
                unset($_SESSION[$this->sesionKey][$key]);
                $_SESSION[$this->sesionKey] = array_values($_SESSION[$this->sesionKey]);
                return true;
            }
        }
        return false;
    }
    
    // Vaciar carrito
    public function vaciarCarrito() {
        $_SESSION[$this->sesionKey] = array();
    }
    
    // Obtener cantidad de donaciones
    public function contarDonaciones() {
        return count($_SESSION[$this->sesionKey]);
    }
}

// Función mejorada para procesar donación con sesiones
function procesarDonacionSegura($monto, $donante, $email, $metodoPago, $proyecto = '', $csrf_token = '') {
    // Verificar token CSRF
    if (!verificarTokenCSRF($csrf_token)) {
        return array(
            'exito' => false,
            'mensaje' => 'Token de seguridad inválido. Intente nuevamente.'
        );
    }
    
    // Función para filtrar datos de entrada
    function filtrarDatos($datos) {
        $datos = trim($datos);
        $datos = stripslashes($datos);
        $datos = htmlspecialchars($datos);
        return $datos;
    }
    
    // Filtrar todos los datos de entrada
    $monto = filtrarDatos($monto);
    $donante = filtrarDatos($donante);
    $email = filtrarDatos($email);
    $metodoPago = filtrarDatos($metodoPago);
    $proyecto = filtrarDatos($proyecto);
    
    // Validaciones básicas
    $errores = array();
    
    if (empty($monto) || !is_numeric($monto) || $monto <= 0) {
        $errores[] = "El monto debe ser un número mayor a 0";
    }
    
    if (empty($donante)) {
        $errores[] = "El nombre del donante es obligatorio";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Email inválido";
    }
    
    if (empty($metodoPago)) {
        $errores[] = "Debe seleccionar un método de pago";
    }
    
    // Verificar límites de donación por sesión (medida de seguridad)
    $carrito = new CarritoDonaciones();
    $totalCarrito = $carrito->calcularTotal();
    
    if (($totalCarrito + floatval($monto)) > 10000) {
        $errores[] = "El total de donaciones por sesión no puede exceder $10,000";
    }
    
    // Si hay errores, retornar mensaje de error
    if (!empty($errores)) {
        return array(
            'exito' => false,
            'mensaje' => 'Errores encontrados: ' . implode(', ', $errores)
        );
    }
    
    // Agregar al carrito de donaciones
    $donacion_id = $carrito->agregarDonacion($proyecto, $monto, $donante, $email);
    
    // Simulación de procesamiento exitoso
    $numeroTransaccion = 'TXN' . date('Ymd') . rand(1000, 9999);
    
    // Registrar en log de auditoría
    error_log("Donación agregada al carrito: ID=$donacion_id, Monto=$monto, Usuario=$donante");
    
    return array(
        'exito' => true,
        'mensaje' => "Donación agregada al carrito exitosamente",
        'datos' => array(
            'donacion_id' => $donacion_id,
            'numero_transaccion' => $numeroTransaccion,
            'monto' => $monto,
            'donante' => $donante,
            'proyecto' => $proyecto,
            'fecha' => date('Y-m-d H:i:s'),
            'total_carrito' => $carrito->calcularTotal(),
            'cantidad_donaciones' => $carrito->contarDonaciones()
        )
    );
}

// Inicializar carrito de donaciones
$carritoDonaciones = new CarritoDonaciones();

// Clase para manejo de eventos
class GestorEventos {
    private $eventos;
    
    public function __construct() {
        $this->eventos = array();
        $this->eventos = array(
            array(
                'id' => 1,
                'descripcion' => 'Caminata familiar para recaudar fondos',
                'tipo' => 'recaudacion',
                'lugar' => 'Parque Central',
                'fecha' => '2025-07-15',
                'hora' => '09:00'
            ),
            array(
                'id' => 2,
                'descripcion' => 'Cena de gala con artistas locales',
                'tipo' => 'cultural',
                'lugar' => 'Hotel Plaza',
                'fecha' => '2025-07-22',
                'hora' => '19:00'
            )
        );
    }
    
    // Método para agregar evento
    public function agregarEvento($descripcion, $tipo, $lugar, $fecha, $hora) {
        $evento = array(
            'id' => count($this->eventos) + 1,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'lugar' => $lugar,
            'fecha' => $fecha,
            'hora' => $hora
        );
        
        $this->eventos[] = $evento;
        return true;
    }
    
    public function obtenerEventos() {
        return $this->eventos;
    }
    
    // Método para filtrar eventos por tipo
    public function filtrarPorTipo($tipo) {
        $eventosFiltrados = array();
        
        foreach ($this->eventos as $evento) {
            if (strtolower($evento['tipo']) === strtolower($tipo)) {
                $eventosFiltrados[] = $evento;
            }
        }
        
        return $eventosFiltrados;
    }
    
    // Método para buscar eventos por descripción
    public function buscarPorDescripcion($termino) {
        $eventosEncontrados = array();
        
        foreach ($this->eventos as $evento) {
            if (stripos($evento['descripcion'], $termino) !== false || 
                stripos($evento['lugar'], $termino) !== false) {
                $eventosEncontrados[] = $evento;
            }
        }
        
        return $eventosEncontrados;
    }
    
    // Método para filtrado personalizado
    public function filtradoPersonalizado($tipo = '', $fecha = '', $lugar = '') {
        $eventosFiltrados = $this->eventos;
        
        if (!empty($tipo)) {
            $eventosFiltrados = array_filter($eventosFiltrados, function($evento) use ($tipo) {
                return strtolower($evento['tipo']) === strtolower($tipo);
            });
        }
        
        if (!empty($fecha)) {
            $eventosFiltrados = array_filter($eventosFiltrados, function($evento) use ($fecha) {
                return $evento['fecha'] === $fecha;
            });
        }
        
        if (!empty($lugar)) {
            $eventosFiltrados = array_filter($eventosFiltrados, function($evento) use ($lugar) {
                return stripos($evento['lugar'], $lugar) !== false;
            });
        }
        
        return array_values($eventosFiltrados);
    }
}

// Inicializar gestor de eventos en sesión
if (!isset($_SESSION['gestorEventos'])) {
    $_SESSION['gestorEventos'] = new GestorEventos();
}

$gestorEventos = $_SESSION['gestorEventos'];
$mensaje = '';
$tipoMensaje = '';

// Procesamiento de formularios con seguridad mejorada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        
        // Procesar donación con método POST y CSRF
        if ($_POST['accion'] === 'procesar_donacion') {
            $monto = $_POST['monto'] ?? '';
            $donante = $_POST['donante'] ?? '';
            $email = $_POST['email'] ?? '';
            $metodoPago = $_POST['metodo_pago'] ?? '';
            $proyecto = $_POST['proyecto'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';
            
            $resultado = procesarDonacionSegura($monto, $donante, $email, $metodoPago, $proyecto, $csrf_token);
            
            if ($resultado['exito']) {
                $mensaje = $resultado['mensaje'] . ". Total en carrito: $" . $resultado['datos']['total_carrito'];
                $tipoMensaje = 'exito';
                
                // Regenerar token CSRF después de operación exitosa
                unset($_SESSION['csrf_token']);
            } else {
                $mensaje = $resultado['mensaje'];
                $tipoMensaje = 'error';
            }
        }
        
        // Eliminar donación del carrito
        if ($_POST['accion'] === 'eliminar_donacion') {
            $donacion_id = $_POST['donacion_id'] ?? '';
            $csrf_token = $_POST['csrf_token'] ?? '';
            
            if (verificarTokenCSRF($csrf_token)) {
                if ($carritoDonaciones->eliminarDonacion($donacion_id)) {
                    $mensaje = "Donación eliminada del carrito";
                    $tipoMensaje = 'exito';
                } else {
                    $mensaje = "Error al eliminar donación";
                    $tipoMensaje = 'error';
                }
            } else {
                $mensaje = "Token de seguridad inválido";
                $tipoMensaje = 'error';
            }
        }
        
        if ($_POST['accion'] === 'finalizar_compra') {
            $csrf_token = $_POST['csrf_token'] ?? '';
            
            if (verificarTokenCSRF($csrf_token)) {
                $donaciones = $carritoDonaciones->obtenerDonaciones();
                
                if (count($donaciones) > 0) {
                    // Simular procesamiento de todas las donaciones
                    $totalProcesado = $carritoDonaciones->calcularTotal();
                    $cantidadDonaciones = $carritoDonaciones->contarDonaciones();
                    
                    // Limpiar carrito después de procesar
                    $carritoDonaciones->vaciarCarrito();
                    
                    $mensaje = "Compra finalizada exitosamente. Total procesado: $$totalProcesado ($cantidadDonaciones donaciones)";
                    $tipoMensaje = 'exito';
                    
                    // Log de auditoría
                    error_log("Compra finalizada: Total=$totalProcesado, Donaciones=$cantidadDonaciones");
                } else {
                    $mensaje = "No hay donaciones en el carrito";
                    $tipoMensaje = 'error';
                }
            } else {
                $mensaje = "Token de seguridad inválido";
                $tipoMensaje = 'error';
            }
        }
    }
}

// Procesamiento de búsqueda
$eventosBusqueda = array();
$terminoBusqueda = '';
if (isset($_GET['buscar']) && !empty($_GET['eventos'])) {
    $terminoBusqueda = $_GET['eventos'];
    $eventosBusqueda = $gestorEventos->buscarPorDescripcion($terminoBusqueda);
}

$todosEventos = $gestorEventos->obtenerEventos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Organización sin fines de lucro</title>
</head>
<body>
    <!-- Mensajes PHP -->
    <?php if (!empty($mensaje)): ?>
        <div class="php-message <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>
    
    <!-- Indicador de carrito -->
    <div id="carrito-indicador" class="carrito-status">
        <span class="carrito-icon">🛒</span>
        <span class="carrito-count"><?php echo $carritoDonaciones->contarDonaciones(); ?></span>
        <span class="carrito-total">Total: $<?php echo number_format($carritoDonaciones->calcularTotal(), 2); ?></span>
    </div>

     <nav>
        <div class="up-nav">
            <img src="logo.jpg" alt="logo-placeholder">
            <h1>Organización sin fines de lucro</h1>
            <div class="search-container">
                <form method="GET" style="display: flex; align-items: center;">
                    <input type="text" name="eventos" placeholder="Buscar eventos..." 
                           value="<?php echo htmlspecialchars($terminoBusqueda); ?>">
                    <button type="submit" name="buscar" value="1">Buscar</button>
                </form>
            </div>
        </div>
        
        <div class="down-nav">
            <div class="option-container" onclick="cambiarPestaña('projects', this)">
                <a href="#">Proyectos</a>
            </div>
            <div class="option-container" id="donations" onclick="cambiarPestaña('donations', this)">
                <a href="#">Donaciones</a>
            </div>
            <div class="option-container" onclick="cambiarPestaña('carrito', this)">
                <a href="#">Carrito (<?php echo $carritoDonaciones->contarDonaciones(); ?>)</a>
            </div>
            <div class="option-container" onclick="cambiarPestaña('events', this)">
                <a href="#">Eventos</a>
            </div>
            <div class="option-container" onclick="cambiarPestaña('admin', this)">
                <a href="#">Administrar</a>
            </div>
        </div>
    </nav>

    <div id="results-container">
        <!-- Pestaña de Proyectos -->
        <div id="projects-tabs" class="tab-content">
            <h2>Nuestros Proyectos</h2>
            <div id="projects-container"></div>
        </div>
        
         <!-- Pestaña de Donaciones con seguridad mejorada -->
        <div id="donations-tab" class="tab-content">
            <h2>Realizar Donación Segura</h2>
            
            <div class="php-form">
                <h3>Sistema de Donación Seguro</h3>
                <form method="POST">
                    <input type="hidden" name="accion" value="procesar_donacion">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                    
                    <div class="form-group">
                        <label for="php-donante">Nombre completo:</label>
                        <input type="text" id="php-donante" name="donante" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="php-email">Email:</label>
                        <input type="email" id="php-email" name="email" required maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="php-monto">Monto a donar:</label>
                        <input type="number" id="php-monto" name="monto" min="1" max="5000" step="0.01" required>
                        <small>Máximo $5,000 por donación individual</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="php-proyecto">Proyecto (opcional):</label>
                        <select id="php-proyecto" name="proyecto">
                            <option value="">Donación general</option>
                            <option value="Educación para Todos">Educación para Todos</option>
                            <option value="Alimentación Comunitaria">Alimentación Comunitaria</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="php-metodo">Método de pago:</label>
                        <select id="php-metodo" name="metodo_pago" required>
                            <option value="">Seleccione método</option>
                            <option value="tarjeta_credito">Tarjeta de Crédito</option>
                            <option value="tarjeta_debito">Tarjeta de Débito</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="php-btn">Agregar al Carrito</button>
                </form>
            </div>
        </div>

         <!-- Nueva pestaña de Carrito -->
        <div id="carrito-tab" class="tab-content">
            <h2>Carrito de Donaciones</h2>
            
            <?php if ($carritoDonaciones->contarDonaciones() > 0): ?>
                <div class="carrito-resumen">
                    <h3>Resumen del Carrito</h3>
                    <p><strong>Total de donaciones:</strong> <?php echo $carritoDonaciones->contarDonaciones(); ?></p>
                    <p><strong>Monto total:</strong> $<?php echo number_format($carritoDonaciones->calcularTotal(), 2); ?></p>
                </div>
                
                <div class="donaciones-carrito">
                    <?php foreach ($carritoDonaciones->obtenerDonaciones() as $donacion): ?>
                        <div class="donacion-item">
                            <h4>Donación ID: <?php echo htmlspecialchars($donacion['id']); ?></h4>
                            <p><strong>Donante:</strong> <?php echo htmlspecialchars($donacion['donante']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($donacion['email']); ?></p>
                            <p><strong>Monto:</strong> $<?php echo number_format($donacion['monto'], 2); ?></p>
                            <p><strong>Proyecto:</strong> <?php echo htmlspecialchars($donacion['proyecto_id'] ?: 'Donación general'); ?></p>
                            <p><strong>Agregado:</strong> <?php echo date('Y-m-d H:i:s', $donacion['fecha_agregado']); ?></p>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="accion" value="eliminar_donacion">
                                <input type="hidden" name="donacion_id" value="<?php echo htmlspecialchars($donacion['id']); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                                <button type="submit" class="btn-eliminar" onclick="return confirm('¿Eliminar esta donación?')">Eliminar</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="carrito-acciones">
                    <form method="POST" style="display: inline-block; margin-right: 10px;">
                        <input type="hidden" name="accion" value="finalizar_compra">
                        <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                        <button type="submit" class="php-btn btn-finalizar" onclick="return confirm('¿Procesar todas las donaciones?')">
                            Finalizar Compra ($<?php echo number_format($carritoDonaciones->calcularTotal(), 2); ?>)
                        </button>
                    </form>
                </div>
                
            <?php else: ?>
                <div class="carrito-vacio">
                    <h3>El carrito está vacío</h3>
                    <p>Agregue donaciones desde la pestaña "Donaciones"</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pestaña de Eventos -->
        <div id="events-tab" class="tab-content">
            <h2>Próximos Eventos</h2>
            
            <?php if (!empty($terminoBusqueda)): ?>
                <h3>Resultados de búsqueda para "<?php echo htmlspecialchars($terminoBusqueda); ?>":</h3>
                <div class="eventos-php">
                    <?php if (empty($eventosBusqueda)): ?>
                        <p>No se encontraron eventos que coincidan con tu búsqueda.</p>
                    <?php else: ?>
                        <?php foreach ($eventosBusqueda as $evento): ?>
                            <div class="evento-php">
                                <h4><?php echo htmlspecialchars($evento['descripcion']); ?></h4>
                                <div class="evento-meta">
                                    <strong>Tipo:</strong> <?php echo htmlspecialchars($evento['tipo']); ?>
                                </div>
                                <div class="evento-meta">
                                    <strong>Lugar:</strong> <?php echo htmlspecialchars($evento['lugar']); ?>
                                </div>
                                <div class="evento-meta">
                                    <strong>Fecha:</strong> <?php echo htmlspecialchars($evento['fecha']); ?>
                                </div>
                                <div class="evento-meta">
                                    <strong>Hora:</strong> <?php echo htmlspecialchars($evento['hora']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <hr style="margin: 20px 0;">
            <?php endif; ?>

            <h3>Todos los eventos:</h3>
            <div id="events-container"></div>
            
            <!-- Mostrar eventos con PHP también -->
            <div class="eventos-php">
                <?php foreach ($todosEventos as $evento): ?>
                    <div class="evento-php">
                        <h4><?php echo htmlspecialchars($evento['descripcion']); ?></h4>
                        <div class="evento-meta">
                            <strong>Tipo:</strong> <?php echo htmlspecialchars($evento['tipo']); ?>
                        </div>
                        <div class="evento-meta">
                            <strong>Lugar:</strong> <?php echo htmlspecialchars($evento['lugar']); ?>
                        </div>
                        <div class="evento-meta">
                            <strong>Fecha:</strong> <?php echo htmlspecialchars($evento['fecha']); ?>
                        </div>
                        <div class="evento-meta">
                            <strong>Hora:</strong> <?php echo htmlspecialchars($evento['hora']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pestaña de Administración -->
        <div id="admin-tab" class="tab-content">
            <h2>Panel de Administración</h2>
            
            <!-- Formulario de registro de eventos -->
            <div class="php-form">
                <h3>Registrar Nuevo Evento</h3>
                <form method="POST">
                    <input type="hidden" name="accion" value="agregar_evento">
                    
                    <div class="form-group">
                        <label for="evento-descripcion">Descripción del evento:</label>
                        <textarea id="evento-descripcion" name="descripcion" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="evento-tipo">Tipo de evento:</label>
                        <select id="evento-tipo" name="tipo" required>
                            <option value="">Seleccione tipo</option>
                            <option value="conferencia">Conferencia</option>
                            <option value="taller">Taller</option>
                            <option value="recaudacion">Recaudación de fondos</option>
                            <option value="voluntariado">Voluntariado</option>
                            <option value="cultural">Evento cultural</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="evento-lugar">Lugar:</label>
                        <input type="text" id="evento-lugar" name="lugar" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="evento-fecha">Fecha:</label>
                        <input type="date" id="evento-fecha" name="fecha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="evento-hora">Hora:</label>
                        <input type="time" id="evento-hora" name="hora" required>
                    </div>
                    
                    <button type="submit" class="php-btn">Registrar Evento</button>
                </form>
            </div>

            <!-- Mostrar eventos registrados -->
            <div class="php-form">
                <h3>Eventos Registrados (<?php echo count($todosEventos); ?>)</h3>
                <div class="eventos-php">
                    <?php foreach ($todosEventos as $evento): ?>
                        <div class="evento-php">
                            <h4>ID: <?php echo $evento['id']; ?> - <?php echo htmlspecialchars($evento['descripcion']); ?></h4>
                            <div class="evento-meta">
                                <strong>Tipo:</strong> <?php echo htmlspecialchars($evento['tipo']); ?> |
                                <strong>Lugar:</strong> <?php echo htmlspecialchars($evento['lugar']); ?> |
                                <strong>Fecha:</strong> <?php echo htmlspecialchars($evento['fecha']); ?> |
                                <strong>Hora:</strong> <?php echo htmlspecialchars($evento['hora']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        function cambiarPestaña(pestaña, elemento) {
            const pestañas = document.querySelectorAll('.tab-content');
            pestañas.forEach(tab => {
                tab.style.display = 'none';
            });

            document.querySelectorAll('.option-container').forEach(container => {
                container.classList.remove('active');
            });

            elemento.classList.add('active');

            switch(pestaña) {
                case 'projects':
                    document.getElementById('projects-tabs').style.display = 'block';
                    break;
                case 'donations':
                    document.getElementById('donations-tab').style.display = 'block';
                    break;
                case 'carrito':
                    document.getElementById('carrito-tab').style.display = 'block';
                    break;
                case 'events':
                    document.getElementById('events-tab').style.display = 'block';
                    break;
                case 'admin':
                    document.getElementById('admin-tab').style.display = 'block';
                    break;
            }
        }

        // Mostrar notificación si hay mensaje PHP
        <?php if (!empty($mensaje) && $tipoMensaje === 'exito'): ?>
            setTimeout(() => {
                sistemaNotificaciones.mostrar('donacion', '<?php echo addslashes($mensaje); ?>');
            }, 1000);
        <?php endif; ?>

        function actualizarContadorCarrito() {
        }
    </script>
</body>
</html>