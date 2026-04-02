<?php
/**
 * Clase para gestionar envío de correos automáticos
 * Sistema de Transparencia Activa
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/EmailSender.php';
require_once __DIR__ . '/PlazoCalculator.php';

class CorreoManager {
    private $conn;
    private $email_sender;
    
    public function __construct() {
        global $db;
        $this->conn = $db->getConnection();
        $this->email_sender = new EmailSender();
    }
    
    /**
     * Enviar correo de inicio de proceso a todos los cargadores
     */
    public function enviarInicioProceso($mes, $ano) {
        // Obtener plantilla
        $plantilla = $this->obtenerPlantilla('inicio_proceso');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de inicio de proceso');
        }
        
        // Calcular fechas
        $siguiente_mes = $mes + 1;
        $siguiente_ano = $ano;
        if ($siguiente_mes > 12) {
            $siguiente_mes = 1;
            $siguiente_ano++;
        }
        
        // Calcular plazo: 6° día hábil del mes siguiente
        $plazo_dias = 6;
        $fecha_limite = PlazoCalculator::calcularNesimoDiaHabil($siguiente_ano, $siguiente_mes, $plazo_dias);
        
        // Obtener todos los cargadores con sus ítems asignados
        $cargadores = $this->obtenerCargadoresConItems();
        
        $exitosos = 0;
        $fallidos = 0;
        $detalles = [];
        
        foreach ($cargadores as $cargador) {
            try {
                // Reemplazar variables en la plantilla
                $variables = [
                    '{nombre_usuario}' => $cargador['nombre'] . ' ' . $cargador['apellido'],
                    '{mes_carga}' => $this->nombreMes($mes),
                    '{ano_carga}' => $ano,
                    '{mes_siguiente}' => $this->nombreMes($siguiente_mes),
                    '{items_asignados}' => $this->generarListaItems($cargador['items']),
                    '{plazo_dias}' => $plazo_dias,
                    '{fecha_limite}' => date('d-m-Y', strtotime($fecha_limite))
                ];
                
                $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
                $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
                
                // Enviar correo
                if ($this->email_sender->enviarCorreo($cargador['email'], $asunto, $cuerpo, $cargador['nombre'] . ' ' . $cargador['apellido'])) {
                    $exitosos++;
                    $detalles[] = [
                        'usuario_id' => $cargador['id'],
                        'email' => $cargador['email'],
                        'estado' => 'exitoso'
                    ];
                } else {
                    $fallidos++;
                    $detalles[] = [
                        'usuario_id' => $cargador['id'],
                        'email' => $cargador['email'],
                        'estado' => 'fallido',
                        'error' => $this->email_sender->getError()
                    ];
                }
                
            } catch (Exception $e) {
                $fallidos++;
                $detalles[] = [
                    'usuario_id' => $cargador['id'],
                    'email' => $cargador['email'],
                    'estado' => 'fallido',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Registrar en historial
        $this->registrarHistorial($plantilla['id'], 'masivo', null, count($cargadores), $mes, $ano, $exitosos, $fallidos, $detalles);
        
        return [
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'total' => count($cargadores)
        ];
    }
    
    /**
     * Enviar correo de inicio de proceso a un cargador específico
     */
    public function enviarInicioProcesoIndividual($usuario_id, $mes, $ano) {
        // Obtener plantilla
        $plantilla = $this->obtenerPlantilla('inicio_proceso');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de inicio de proceso');
        }
        
        // Obtener datos del cargador
        $cargador = $this->obtenerCargadorConItems($usuario_id);
        
        if (!$cargador) {
            throw new Exception('No se encontró el cargador o no tiene ítems asignados');
        }
        
        // Calcular fechas
        $siguiente_mes = $mes + 1;
        $siguiente_ano = $ano;
        if ($siguiente_mes > 12) {
            $siguiente_mes = 1;
            $siguiente_ano++;
        }
        
        // Calcular plazo: 6° día hábil del mes siguiente
        $plazo_dias = 6;
        $fecha_limite = PlazoCalculator::calcularNesimoDiaHabil($siguiente_ano, $siguiente_mes, $plazo_dias);
        
        // Reemplazar variables
        $variables = [
            '{nombre_usuario}' => $cargador['nombre'] . ' ' . $cargador['apellido'],
            '{mes_carga}' => $this->nombreMes($mes),
            '{ano_carga}' => $ano,
            '{mes_siguiente}' => $this->nombreMes($siguiente_mes),
            '{items_asignados}' => $this->generarListaItems($cargador['items']),
            '{plazo_dias}' => $plazo_dias,
            '{fecha_limite}' => date('d-m-Y', strtotime($fecha_limite))
        ];
        
        $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
        $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
        
        // Enviar correo
        if (!$this->email_sender->enviarCorreo($cargador['email'], $asunto, $cuerpo, $cargador['nombre'] . ' ' . $cargador['apellido'])) {
            throw new Exception('Error al enviar correo: ' . $this->email_sender->getError());
        }
        
        // Registrar en historial
        $detalles = [
            [
                'usuario_id' => $cargador['id'],
                'email' => $cargador['email'],
                'estado' => 'exitoso'
            ]
        ];
        $this->registrarHistorial($plantilla['id'], 'individual', $usuario_id, 1, $mes, $ano, 1, 0, $detalles);
        
        return true;
    }
    
    /**
     * Obtener plantilla por tipo
     */
    private function obtenerPlantilla($tipo) {
        $stmt = $this->conn->prepare("SELECT * FROM plantillas_correo WHERE tipo = ? AND activo = 1");
        $stmt->bind_param('s', $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Obtener todos los cargadores con sus ítems asignados
     */
    private function obtenerCargadoresConItems() {
        $query = "SELECT DISTINCT 
                    u.id, 
                    u.nombre, 
                    u.apellido, 
                    u.email
                FROM usuarios u
                INNER JOIN usuario_items ui ON u.id = ui.usuario_id
                INNER JOIN items i ON ui.item_id = i.id AND i.activo = 1
                WHERE u.perfil = 'cargador_informacion' AND u.activo = 1
                ORDER BY u.nombre, u.apellido";
        
        $result = $this->conn->query($query);
        $cargadores = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['items'] = $this->obtenerItemsUsuario($row['id']);
            $cargadores[] = $row;
        }
        
        return $cargadores;
    }
    
    /**
     * Obtener un cargador específico con sus ítems
     */
    private function obtenerCargadorConItems($usuario_id) {
        $stmt = $this->conn->prepare("SELECT id, nombre, apellido, email 
            FROM usuarios 
            WHERE id = ? AND perfil = 'cargador_informacion' AND activo = 1");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cargador = $result->fetch_assoc();
        
        if ($cargador) {
            $cargador['items'] = $this->obtenerItemsUsuario($usuario_id);
        }
        
        return $cargador;
    }
    
    /**
     * Obtener ítems asignados a un usuario
     */
    private function obtenerItemsUsuario($usuario_id) {
        $stmt = $this->conn->prepare("SELECT i.id, i.nombre, i.codigo, i.periodicidad
            FROM items i
            INNER JOIN usuario_items ui ON i.id = ui.item_id
            WHERE ui.usuario_id = ? AND i.activo = 1
            ORDER BY i.codigo, i.nombre");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Generar lista HTML de ítems
     */
    private function generarListaItems($items) {
        if (empty($items)) {
            return '<p><em>No tiene ítems asignados</em></p>';
        }
        
        $html = '<ul>';
        foreach ($items as $item) {
            $html .= '<li><strong>' . htmlspecialchars($item['codigo']) . '</strong>: ' . 
                     htmlspecialchars($item['nombre']) . ' (' . htmlspecialchars($item['periodicidad']) . ')</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Reemplazar variables en texto
     */
    private function reemplazarVariables($texto, $variables) {
        return str_replace(array_keys($variables), array_values($variables), $texto);
    }
    
    /**
     * Obtener nombre del mes
     */
    private function nombreMes($num) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$num] ?? '';
    }
    
    /**
     * Registrar envío en historial
     */
    private function registrarHistorial($plantilla_id, $tipo_envio, $destinatario_id, $destinatarios_count, $mes, $ano, $exitosos, $fallidos, $detalles) {
        global $_SESSION;
        
        $detalles_json = json_encode($detalles, JSON_UNESCAPED_UNICODE);
        
        $stmt = $this->conn->prepare("INSERT INTO historial_envios_correo 
            (plantilla_id, tipo_envio, destinatario_id, destinatarios_count, mes_periodo, ano_periodo, correos_enviados, correos_fallidos, detalles_envio, enviado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param('isiiiiiisi', 
            $plantilla_id,
            $tipo_envio,
            $destinatario_id,
            $destinatarios_count,
            $mes,
            $ano,
            $exitosos,
            $fallidos,
            $detalles_json,
            $_SESSION['user_id']
        );
        
        $stmt->execute();
        $stmt->close();
    }
}
