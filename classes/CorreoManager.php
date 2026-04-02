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
                    '{nombre_usuario}' => $cargador['nombre'],
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
                if ($this->email_sender->enviarCorreo($cargador['email'], $asunto, $cuerpo, $cargador['nombre'])) {
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
            '{nombre_usuario}' => $cargador['nombre'],
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
        if (!$this->email_sender->enviarCorreo($cargador['email'], $asunto, $cuerpo, $cargador['nombre'])) {
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
                    u.email
                FROM usuarios u
                INNER JOIN item_usuarios ui ON u.id = ui.usuario_id
                INNER JOIN items_transparencia i ON ui.item_id = i.id AND i.activo = 1
                WHERE u.perfil = 'cargador_informacion' AND u.activo = 1
                ORDER BY u.nombre";
        
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
        $stmt = $this->conn->prepare("SELECT id, nombre, email 
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
        $stmt = $this->conn->prepare("SELECT i.id, i.nombre, i.numeracion, i.periodicidad
            FROM items_transparencia i
            INNER JOIN item_usuarios ui ON i.id = ui.item_id
            WHERE ui.usuario_id = ? AND i.activo = 1
            ORDER BY i.numeracion, i.nombre");
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
            $html .= '<li><strong>' . htmlspecialchars($item['numeracion']) . '</strong>: ' . 
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
    
    /**
     * Enviar correo de fin de proceso a todos los cargadores
     */
    public function enviarFinProcesoCargadores($mes, $ano) {
        // Obtener plantilla
        $plantilla = $this->obtenerPlantilla('fin_proceso_cargadores');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de fin de proceso cargadores');
        }
        
        // Calcular fecha límite que ya venció
        $siguiente_mes = $mes + 1;
        $siguiente_ano = $ano;
        if ($siguiente_mes > 12) {
            $siguiente_mes = 1;
            $siguiente_ano++;
        }
        $plazo_dias = 6;
        $fecha_limite = PlazoCalculator::calcularNesimoDiaHabil($siguiente_ano, $siguiente_mes, $plazo_dias);
        
        // Obtener todos los cargadores con sus ítems asignados
        $cargadores = $this->obtenerCargadoresConItems();
        
        $exitosos = 0;
        $fallidos = 0;
        $detalles = [];
        
        foreach ($cargadores as $cargador) {
            try {
                // Obtener resumen de carga del usuario
                $resumen_carga = $this->obtenerResumenCargaUsuario($cargador['id'], $mes, $ano);
                
                // Reemplazar variables en la plantilla
                $variables = [
                    '{nombre_usuario}' => $cargador['nombre'],
                    '{mes_carga}' => $this->nombreMes($mes),
                    '{ano_carga}' => $ano,
                    '{resumen_carga}' => $resumen_carga,
                    '{fecha_limite}' => date('d-m-Y', strtotime($fecha_limite))
                ];
                
                $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
                $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
                
                // Enviar correo
                if ($this->email_sender->enviarCorreo($cargador['email'], $asunto, $cuerpo, $cargador['nombre'])) {
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
     * Enviar correo de fin de proceso a un cargador específico
     */
    public function enviarFinProcesoCargadoresIndividual($usuario_id, $mes, $ano) {
        // Obtener plantilla
        $plantilla = $this->obtenerPlantilla('fin_proceso_cargadores');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de fin de proceso cargadores');
        }
        
        // Obtener datos del cargador
        $cargador = $this->obtenerCargadorConItems($usuario_id);
        
        if (!$cargador) {
            throw new Exception('No se encontró el cargador o no tiene ítems asignados');
        }
        
        // Calcular fecha límite
        $siguiente_mes = $mes + 1;
        $siguiente_ano = $ano;
        if ($siguiente_mes > 12) {
            $siguiente_mes = 1;
            $siguiente_ano++;
        }
        $plazo_dias = 6;
        $fecha_limite = PlazoCalculator::calcularNesimoDiaHabil($siguiente_ano, $siguiente_mes, $plazo_dias);
        
        // Obtener resumen de carga
        $resumen_carga = $this->obtenerResumenCargaUsuario($usuario_id, $mes, $ano);
        
        // Reemplazar variables
        $variables = [
            '{nombre_usuario}' => $cargador['nombre'],
            '{mes_carga}' => $this->nombreMes($mes),
            '{ano_carga}' => $ano,
            '{resumen_carga}' => $resumen_carga,
            '{fecha_limite}' => date('d-m-Y', strtotime($fecha_limite))
        ];
        
        $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
        $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
        
        // Enviar correo
        if (!$this->email_sender->enviarCorreo($cargador['email'], $asunto, $cuerpo, $cargador['nombre'])) {
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
     * Obtener resumen de carga de un usuario para un período
     * Muestra: documentos cargados con fecha de envío y publicación, documentos pendientes
     */
    private function obtenerResumenCargaUsuario($usuario_id, $mes, $ano) {
        // Obtener todos los ítems asignados al usuario
        $items = $this->obtenerItemsUsuario($usuario_id);
        
        if (empty($items)) {
            return '<p><em>No tiene ítems asignados</em></p>';
        }
        
        $html = '<table style="width:100%; border-collapse:collapse; font-size:14px;" border="1" cellpadding="8">';
        $html .= '<thead><tr style="background-color:#f8f9fa;">';
        $html .= '<th>Ítem</th>';
        $html .= '<th>Estado</th>';
        $html .= '<th>Fecha Envío</th>';
        $html .= '<th>Fecha Publicación</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        $total_items = 0;
        $items_cargados = 0;
        $items_publicados = 0;
        $items_pendientes = 0;
        
        foreach ($items as $item) {
            // Determinar mes a usar según periodicidad
            $mes_busqueda = $mes;
            if ($item['periodicidad'] === 'anual') {
                $mes_busqueda = 1; // Enero para anuales
            }
            
            // Buscar documento
            $stmt = $this->conn->prepare("SELECT d.id, d.fecha_envio 
                FROM documentos d
                WHERE d.item_id = ? AND d.mes_carga = ? AND d.ano_carga = ?
                ORDER BY d.fecha_envio DESC
                LIMIT 1");
            $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
            $stmt->execute();
            $doc_result = $stmt->get_result();
            $documento = $doc_result->fetch_assoc();
            $stmt->close();
            
            // Buscar verificador (publicación)
            $verificador = null;
            if ($documento) {
                $stmt = $this->conn->prepare("SELECT fecha_carga_portal 
                    FROM verificadores_publicador 
                    WHERE documento_id = ?
                    ORDER BY fecha_carga_portal DESC
                    LIMIT 1");
                $stmt->bind_param('i', $documento['id']);
                $stmt->execute();
                $verif_result = $stmt->get_result();
                $verificador = $verif_result->fetch_assoc();
                $stmt->close();
            }
            
            // Verificar Si Movimiento
            $sinMovimiento = false;
            $checkSinMov = $this->conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
            if ($checkSinMov && $checkSinMov->num_rows > 0) {
                $stmt = $this->conn->prepare("SELECT id FROM observaciones_sin_movimiento 
                    WHERE item_id = ? AND mes = ? AND ano = ? LIMIT 1");
                $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                $stmt->execute();
                $sinMovResult = $stmt->get_result();
                $sinMovimiento = ($sinMovResult->num_rows > 0);
                $stmt->close();
            }
            
            // Construir fila
            $total_items++;
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($item['numeracion']) . '</strong><br>' . 
                     htmlspecialchars($item['nombre']) . '</td>';
            
            if ($verificador) {
                $items_publicados++;
                $html .= '<td style="color:green;"><strong>✓ Publicado</strong></td>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($documento['fecha_envio'])) . '</td>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])) . '</td>';
            } elseif ($documento) {
                $items_cargados++;
                $html .= '<td style="color:orange;"><strong>⚠ Cargado (sin publicar)</strong></td>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($documento['fecha_envio'])) . '</td>';
                $html .= '<td><em>Pendiente</em></td>';
            } elseif ($sinMovimiento) {
                $items_cargados++;
                $html .= '<td style="color:green;"><strong>✓ Sin Movimiento</strong></td>';
                $html .= '<td colspan="2"><em>Sin movimiento registrado</em></td>';
            } else {
                $items_pendientes++;
                $html .= '<td style="color:red;"><strong>✗ Pendiente</strong></td>';
                $html .= '<td colspan="2"><em>Sin carga</em></td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Agregar resumen
        $html .= '<div style="margin-top:20px; padding:15px; background-color:#f8f9fa; border-radius:5px;">';
        $html .= '<h4 style="margin-top:0;">Resumen:</h4>';
        $html .= '<ul>';
        $html .= '<li><strong>Total de ítems:</strong> ' . $total_items . '</li>';
        $html .= '<li><strong style="color:green;">Publicados:</strong> ' . $items_publicados . '</li>';
        $html .= '<li><strong style="color:orange;">Cargados (pendientes de publicar):</strong> ' . $items_cargados . '</li>';
        $html .= '<li><strong style="color:red;">Pendientes de carga:</strong> ' . $items_pendientes . '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
}
