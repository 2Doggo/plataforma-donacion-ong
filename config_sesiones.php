<!-- conifg_sesiones.php -->

<?php
// Configuración segura de cookies de sesión
// Previene ataques de robo de cookies y man-in-the-middle
ini_set('session.cookie_httponly', 1);  // Cookie solo accesible por HTTP, no por JavaScript
ini_set('session.cookie_secure', 1);    // Cookie solo se envía por HTTPS
ini_set('session.cookie_samesite', 'Strict'); // Previene ataques CSRF
ini_set('session.use_only_cookies', 1); // Solo usar cookies para ID de sesión

// Configuración para prevenir expiración prematura
// session.gc_maxlifetime especifica cuándo la información se considera 'basura'
ini_set('session.gc_maxlifetime', 3600);     // 1 hora de vida útil
ini_set('session.cookie_lifetime', 3600);    // Cookie expira en 1 hora
ini_set('session.cache_expire', 60);         // Cache de páginas de sesión: 60 minutos

// Configuración de limpieza automática optimizada
ini_set('session.gc_probability', 1);        // Probabilidad de limpieza
ini_set('session.gc_divisor', 100);          // Cada 100 solicitudes se ejecuta limpieza

// Función para extender sesión automáticamente en actividad del usuario
function extenderSesion() {
    if (isset($_SESSION['ultimo_acceso'])) {
        $inactividad = time() - $_SESSION['ultimo_acceso'];
        
        // Si han pasado más de 30 minutos sin actividad, renovar sesión
        if ($inactividad > 1800) {
            session_regenerate_id(true);
            $_SESSION['ultimo_acceso'] = time();
            
            // Log de renovación de sesión para auditoría
            error_log("Sesión renovada para usuario en: " . date('Y-m-d H:i:s'));
        }
    } else {
        $_SESSION['ultimo_acceso'] = time();
    }
}

// Función de limpieza segura de sesión
function limpiarSesionSegura() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Eliminar cookie de sesión del navegador
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

// Función para verificar integridad de sesión (previene session hijacking)
function verificarIntegridadSesion() {
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        // Posible ataque de hijacking
        limpiarSesionSegura();
        die("Sesión inválida detectada");
    }
}

// Inicializar sesión con configuración segura
function iniciarSesionSegura() {
    session_start();
    
    // Regenerar ID de sesión periódicamente (cada 5 minutos)
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 300) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    extenderSesion();
    verificarIntegridadSesion();
}

// Función para generar token CSRF
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>