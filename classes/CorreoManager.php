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
    public function enviarInicioProceso($mes, $ano, $enviar_a_directores = false) {
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
        
        // Obtener todos los cargadores con sus ítems asignados para este mes
        $cargadores = $this->obtenerCargadoresConItems($mes);
        
        $exitosos = 0;
        $fallidos = 0;
        $detalles = [];
        
        // ENVÍO A CARGADORES
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
                        'tipo' => 'cargador',
                        'usuario_id' => $cargador['id'],
                        'email' => $cargador['email'],
                        'estado' => 'exitoso'
                    ];
                } else {
                    $fallidos++;
                    $detalles[] = [
                        'tipo' => 'cargador',
                        'usuario_id' => $cargador['id'],
                        'email' => $cargador['email'],
                        'estado' => 'fallido',
                        'error' => $this->email_sender->getError()
                    ];
                }
                
            } catch (Exception $e) {
                $fallidos++;
                $detalles[] = [
                    'tipo' => 'cargador',
                    'usuario_id' => $cargador['id'],
                    'email' => $cargador['email'],
                    'estado' => 'fallido',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // ENVÍO A DIRECTORES (opcional)
        $directores_exitosos = 0;
        $directores_fallidos = 0;
        
        if ($enviar_a_directores) {
            $directores = $this->obtenerDirectoresConCorreo();
            
            foreach ($directores as $director) {
                try {
                    // Obtener ítems de las direcciones del director para este mes
                    $items_director = $this->obtenerItemsDirector($director['id'], $mes);
                    
                    if (empty($items_director)) {
                        // Si no tiene ítems, saltar este director
                        continue;
                    }
                    
                    // Reemplazar variables
                    $variables = [
                        '{nombre_usuario}' => $director['nombre_completo'],
                        '{mes_carga}' => $this->nombreMes($mes),
                        '{ano_carga}' => $ano,
                        '{mes_siguiente}' => $this->nombreMes($siguiente_mes),
                        '{items_asignados}' => $this->generarListaItems($items_director),
                        '{plazo_dias}' => $plazo_dias,
                        '{fecha_limite}' => date('d-m-Y', strtotime($fecha_limite))
                    ];
                    
                    $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
                    $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
                    
                    // Enviar correo
                    if ($this->email_sender->enviarCorreo($director['correo'], $asunto, $cuerpo, $director['nombre_completo'])) {
                        $directores_exitosos++;
                        $detalles[] = [
                            'tipo' => 'director',
                            'director_id' => $director['id'],
                            'email' => $director['correo'],
                            'estado' => 'exitoso'
                        ];
                    } else {
                        $directores_fallidos++;
                        $detalles[] = [
                            'tipo' => 'director',
                            'director_id' => $director['id'],
                            'email' => $director['correo'],
                            'estado' => 'fallido',
                            'error' => $this->email_sender->getError()
                        ];
                    }
                    
                } catch (Exception $e) {
                    $directores_fallidos++;
                    $detalles[] = [
                        'tipo' => 'director',
                        'director_id' => $director['id'],
                        'email' => $director['correo'],
                        'estado' => 'fallido',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $exitosos += $directores_exitosos;
            $fallidos += $directores_fallidos;
        }
        
        // Registrar en historial
        $total_destinatarios = count($cargadores) + ($enviar_a_directores ? ($directores_exitosos + $directores_fallidos) : 0);
        $this->registrarHistorial($plantilla['id'], 'masivo', null, $total_destinatarios, $mes, $ano, $exitosos, $fallidos, $detalles);
        
        return [
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'total' => $total_destinatarios,
            'cargadores' => count($cargadores),
            'directores' => $enviar_a_directores ? ($directores_exitosos + $directores_fallidos) : 0
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
        
        // Obtener datos del cargador con ítems del mes
        $cargador = $this->obtenerCargadorConItems($usuario_id, $mes);
        
        if (!$cargador) {
            throw new Exception('No se encontró el cargador o no tiene ítems asignados para este mes');
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
     * Enviar correo de inicio de proceso a un director específico
     */
    public function enviarInicioProcesoDirector($director_id, $mes, $ano) {
        // Obtener plantilla
        $plantilla = $this->obtenerPlantilla('inicio_proceso');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de inicio de proceso');
        }
        
        // Obtener datos del director
        $stmt = $this->conn->prepare("SELECT id, nombres, apellidos, correo, 
                                             CONCAT(nombres, ' ', apellidos) as nombre_completo
                                      FROM directores 
                                      WHERE id = ? AND activo = 1 AND correo IS NOT NULL AND correo != ''");
        $stmt->bind_param('i', $director_id);
        $stmt->execute();
        $director = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$director) {
            throw new Exception('No se encontró el director o no tiene correo registrado');
        }
        
        // Obtener ítems de las direcciones del director para este mes
        $items_director = $this->obtenerItemsDirector($director_id, $mes);
        
        if (empty($items_director)) {
            throw new Exception('El director no tiene ítems asignados para este mes');
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
            '{nombre_usuario}' => $director['nombre_completo'],
            '{mes_carga}' => $this->nombreMes($mes),
            '{ano_carga}' => $ano,
            '{mes_siguiente}' => $this->nombreMes($siguiente_mes),
            '{items_asignados}' => $this->generarListaItems($items_director),
            '{plazo_dias}' => $plazo_dias,
            '{fecha_limite}' => date('d-m-Y', strtotime($fecha_limite))
        ];
        
        $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
        $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
        
        // Enviar correo
        if (!$this->email_sender->enviarCorreo($director['correo'], $asunto, $cuerpo, $director['nombre_completo'])) {
            throw new Exception('Error al enviar correo: ' . $this->email_sender->getError());
        }
        
        // Registrar en historial
        $detalles = [
            [
                'tipo' => 'director',
                'director_id' => $director['id'],
                'email' => $director['correo'],
                'estado' => 'exitoso'
            ]
        ];
        $this->registrarHistorial($plantilla['id'], 'individual', $director['id'], 1, $mes, $ano, 1, 0, $detalles);
        
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
    private function obtenerCargadoresConItems($mes = null) {
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
            $row['items'] = $this->obtenerItemsUsuario($row['id'], $mes);
            $cargadores[] = $row;
        }
        
        return $cargadores;
    }
    
    /**
     * Obtener un cargador específico con sus ítems
     */
    private function obtenerCargadorConItems($usuario_id, $mes = null) {
        $stmt = $this->conn->prepare("SELECT id, nombre, email 
            FROM usuarios 
            WHERE id = ? AND perfil = 'cargador_informacion' AND activo = 1");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cargador = $result->fetch_assoc();
        
        if ($cargador) {
            $cargador['items'] = $this->obtenerItemsUsuario($usuario_id, $mes);
        }
        
        return $cargador;
    }
    
    /**
     * Obtener ítems asignados a un usuario
     * Si se proporciona $mes, filtra solo los ítems que corresponden a ese mes
     */
    private function obtenerItemsUsuario($usuario_id, $mes = null) {
        $query = "SELECT i.id, i.nombre, i.numeracion, i.periodicidad, i.mes_carga_anual
            FROM items_transparencia i
            INNER JOIN item_usuarios ui ON i.id = ui.item_id
            WHERE ui.usuario_id = ? AND i.activo = 1";
        
        // Filtrar por mes si se proporciona
        if ($mes !== null) {
            // Meses para trimestrales: 3, 6, 9, 12
            $mesesTrimestral = [3, 6, 9, 12];
            $esMesTrimestral = in_array($mes, $mesesTrimestral) ? 'TRUE' : 'FALSE';
            
            // Meses para semestrales: 1, 7
            $mesesSemestral = [1, 7];
            $esMesSemestral = in_array($mes, $mesesSemestral) ? 'TRUE' : 'FALSE';
            
            $query .= " AND (
                i.periodicidad = 'mensual' 
                OR (i.periodicidad = 'trimestral' AND $esMesTrimestral)
                OR (i.periodicidad = 'semestral' AND $esMesSemestral)
                OR (i.periodicidad = 'anual' AND i.mes_carga_anual = $mes)
            )";
        }
        
        $query .= " ORDER BY i.numeracion, i.nombre";
        
        $stmt = $this->conn->prepare($query);
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
            $html .= '<li>' . htmlspecialchars($item['nombre']) . ' (' . htmlspecialchars($item['periodicidad']) . ')</li>';
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
                $mes_busqueda = intval($item['mes_carga_anual'] ?? 1);
            } elseif ($item['periodicidad'] === 'trimestral') {
                $mes_busqueda = (int)(ceil($mes / 3) * 3);
            } elseif ($item['periodicidad'] === 'semestral') {
                $mes_busqueda = $mes <= 6 ? 1 : 7;
            }
            
            // Verificar Sin Movimiento primero
            $sinMovimiento = false;
            $sinMovFecha = null;
            $checkSinMov = $this->conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
            if ($checkSinMov && $checkSinMov->num_rows > 0) {
                $stmt = $this->conn->prepare("SELECT id, fecha_creacion FROM observaciones_sin_movimiento 
                    WHERE item_id = ? AND mes = ? AND ano = ? LIMIT 1");
                $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                $stmt->execute();
                $sinMovResult = $stmt->get_result();
                if ($sinMovRow = $sinMovResult->fetch_assoc()) {
                    $sinMovimiento = true;
                    $sinMovFecha = $sinMovRow['fecha_creacion'];
                }
                $stmt->close();
            }
            
            // Buscar documento según el caso
            $documento = null;
            if ($sinMovimiento) {
                // Si es Sin Movimiento, buscar SOLO el documento placeholder
                $stmt = $this->conn->prepare("SELECT id, fecha_subida 
                    FROM documentos 
                    WHERE item_id = ? AND mes_carga = ? AND ano_carga = ? 
                    AND titulo LIKE 'Sin Movimiento%'
                    ORDER BY fecha_subida DESC
                    LIMIT 1");
                $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                $stmt->execute();
                $placeholder_result = $stmt->get_result();
                $documento = $placeholder_result->fetch_assoc();
                $stmt->close();
            } else {
                // Si NO es Sin Movimiento, buscar documento normal (excluyendo placeholders)
                $stmt = $this->conn->prepare("SELECT d.id, d.fecha_subida 
                    FROM documentos d
                    WHERE d.item_id = ? AND d.mes_carga = ? AND d.ano_carga = ?
                    AND (d.titulo NOT LIKE 'Sin Movimiento%' OR d.titulo IS NULL)
                    ORDER BY d.fecha_subida DESC
                    LIMIT 1");
                $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                $stmt->execute();
                $doc_result = $stmt->get_result();
                $documento = $doc_result->fetch_assoc();
                $stmt->close();
            }
            
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
            
            // Construir fila
            $total_items++;
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['nombre']) . '</td>';
            
            if ($verificador) {
                // Tiene verificador = publicado (puede ser Sin Movimiento o documento normal)
                $items_publicados++;
                if ($sinMovimiento) {
                    $html .= '<td style="color:green;"><strong>✓ Sin Movimiento (Publicado)</strong></td>';
                } else {
                    $html .= '<td style="color:green;"><strong>✓ Publicado</strong></td>';
                }
                $html .= '<td>' . date('d/m/Y H:i', strtotime($documento['fecha_subida'])) . '</td>';
                $html .= '<td>' . date('d/m/Y H:i', strtotime($verificador['fecha_carga_portal'])) . '</td>';
            } elseif ($documento) {
                // Tiene documento pero no verificador
                $items_cargados++;
                if ($sinMovimiento) {
                    $html .= '<td style="color:orange;"><strong>⚠ Sin Movimiento (sin publicar)</strong></td>';
                } else {
                    $html .= '<td style="color:orange;"><strong>⚠ Cargado (sin publicar)</strong></td>';
                }
                $html .= '<td>' . date('d/m/Y H:i', strtotime($documento['fecha_subida'])) . '</td>';
                $html .= '<td><em>Pendiente</em></td>';
            } elseif ($sinMovimiento) {
                // Sin Movimiento declarado pero sin documento placeholder
                $items_cargados++;
                $html .= '<td style="color:orange;"><strong>⚠ Sin Movimiento (Sin Publicar)</strong></td>';
                $html .= '<td>' . ($sinMovFecha ? date('d/m/Y H:i', strtotime($sinMovFecha)) : '<em>-</em>') . '</td>';
                $html .= '<td><em>Pendiente</em></td>';
            } else {
                // Sin documento, sin Sin Movimiento = pendiente
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
    
    /**
     * Generar token público para resumen municipal
     */
    public function generarTokenPublico($mes, $ano) {
        $token = bin2hex(random_bytes(32));
        
        $stmt = $this->conn->prepare("INSERT INTO resumen_publico_tokens (token, mes, ano, creado_por) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('siis', $token, $mes, $ano, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        return $token;
    }
    
    /**
     * Enviar correo de fin de proceso general a todos los directores
     * Cada director recibe solo los ítems de sus direcciones asignadas
     * Opcionalmente puede enviar también a auditores con resumen completo
     */
    public function enviarFinProcesoGeneral($mes, $ano, $enviar_a_auditores = false) {
        $plantilla = $this->obtenerPlantilla('fin_proceso_general');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de fin de proceso general');
        }
        
        // Generar token y enlace público
        $token = $this->generarTokenPublico($mes, $ano);
        $enlace_resumen = SITE_URL . 'resumen_publico.php?token=' . $token;
        
        // Calcular fecha del 10° día hábil
        $siguiente_mes = $mes + 1;
        $siguiente_ano = $ano;
        if ($siguiente_mes > 12) {
            $siguiente_mes = 1;
            $siguiente_ano++;
        }
        $fecha_cierre = PlazoCalculator::calcularNesimoDiaHabil($siguiente_ano, $siguiente_mes, 10);
        
        // Obtener directores con correo
        $directores = $this->obtenerDirectoresConCorreo();
        
        $exitosos = 0;
        $fallidos = 0;
        $detalles = [];
        
        // ENVÍO A DIRECTORES (resumen filtrado por sus direcciones)
        foreach ($directores as $director) {
            try {
                // Obtener nombres de direcciones asignadas al director
                $stmtDir = $this->conn->prepare("SELECT nombre FROM direcciones WHERE director_id = ? AND activa = 1 ORDER BY nombre");
                $stmtDir->bind_param('i', $director['id']);
                $stmtDir->execute();
                $dirResult = $stmtDir->get_result();
                $nombres_direcciones = [];
                while ($dd = $dirResult->fetch_assoc()) {
                    $nombres_direcciones[] = $dd['nombre'];
                }
                $stmtDir->close();
                $texto_direcciones = !empty($nombres_direcciones) ? implode(', ', $nombres_direcciones) : 'Sin dirección asignada';
                
                // Resumen solo con los ítems de las direcciones del director
                $resumen_director = $this->obtenerResumenDireccionesDirector($director['id'], $mes, $ano);
                
                $variables = [
                    '{nombre_director}' => $director['nombre_completo'],
                    '{cargo_rol}' => 'Director/a de ',
                    '{direcciones_director}' => $texto_direcciones,
                    '{mes_carga}' => $this->nombreMes($mes),
                    '{ano_carga}' => $ano,
                    '{fecha_cierre}' => date('d-m-Y', strtotime($fecha_cierre)),
                    '{resumen_general}' => $resumen_director,
                    '{enlace_resumen}' => $enlace_resumen
                ];
                
                $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
                $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
                
                if ($this->email_sender->enviarCorreo($director['correo'], $asunto, $cuerpo, $director['nombre_completo'])) {
                    $exitosos++;
                    $detalles[] = [
                        'tipo' => 'director',
                        'director_id' => $director['id'],
                        'email' => $director['correo'],
                        'estado' => 'exitoso'
                    ];
                } else {
                    $fallidos++;
                    $detalles[] = [
                        'tipo' => 'director',
                        'director_id' => $director['id'],
                        'email' => $director['correo'],
                        'estado' => 'fallido',
                        'error' => $this->email_sender->getError()
                    ];
                }
            } catch (Exception $e) {
                $fallidos++;
                $detalles[] = [
                    'tipo' => 'director',
                    'director_id' => $director['id'],
                    'email' => $director['correo'],
                    'estado' => 'fallido',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // ENVÍO A AUDITORES (resumen completo de todas las direcciones)
        $auditores_exitosos = 0;
        $auditores_fallidos = 0;
        
        if ($enviar_a_auditores) {
            $auditores = $this->obtenerAuditoresConCorreo();
            
            // Generar resumen completo del municipio
            $resumen_completo = $this->obtenerResumenGeneralMunicipio($mes, $ano);
            
            foreach ($auditores as $auditor) {
                try {
                    $variables = [
                        '{nombre_director}' => $auditor['nombre_completo'], // Usar mismo placeholder
                        '{cargo_rol}' => 'Auditor/a - ',
                        '{direcciones_director}' => 'Todas las direcciones del municipio',
                        '{mes_carga}' => $this->nombreMes($mes),
                        '{ano_carga}' => $ano,
                        '{fecha_cierre}' => date('d-m-Y', strtotime($fecha_cierre)),
                        '{resumen_general}' => $resumen_completo,
                        '{enlace_resumen}' => $enlace_resumen
                    ];
                    
                    $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
                    $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
                    
                    if ($this->email_sender->enviarCorreo($auditor['email'], $asunto, $cuerpo, $auditor['nombre_completo'])) {
                        $auditores_exitosos++;
                        $detalles[] = [
                            'tipo' => 'auditor',
                            'usuario_id' => $auditor['id'],
                            'email' => $auditor['email'],
                            'estado' => 'exitoso'
                        ];
                    } else {
                        $auditores_fallidos++;
                        $detalles[] = [
                            'tipo' => 'auditor',
                            'usuario_id' => $auditor['id'],
                            'email' => $auditor['email'],
                            'estado' => 'fallido',
                            'error' => $this->email_sender->getError()
                        ];
                    }
                } catch (Exception $e) {
                    $auditores_fallidos++;
                    $detalles[] = [
                        'tipo' => 'auditor',
                        'usuario_id' => $auditor['id'],
                        'email' => $auditor['email'],
                        'estado' => 'fallido',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $exitosos += $auditores_exitosos;
            $fallidos += $auditores_fallidos;
        }
        
        // Registrar en historial
        $total_destinatarios = count($directores) + ($enviar_a_auditores ? count($this->obtenerAuditoresConCorreo()) : 0);
        $this->registrarHistorial($plantilla['id'], 'masivo', null, $total_destinatarios, $mes, $ano, $exitosos, $fallidos, $detalles);
        
        return [
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'total' => $total_destinatarios,
            'directores' => count($directores),
            'auditores' => $enviar_a_auditores ? ($auditores_exitosos + $auditores_fallidos) : 0,
            'enlace_resumen' => $enlace_resumen
        ];
    }
    
    /**
     * Obtener directores activos con correo electrónico
     */
    private function obtenerDirectoresConCorreo() {
        $result = $this->conn->query("SELECT id, nombres, apellidos, correo, 
                                             CONCAT(nombres, ' ', apellidos) as nombre_completo
                                      FROM directores 
                                      WHERE activo = 1 AND correo IS NOT NULL AND correo != ''
                                      ORDER BY apellidos, nombres");
        $directores = [];
        while ($row = $result->fetch_assoc()) {
            $directores[] = $row;
        }
        return $directores;
    }
    
    /**
     * Obtener auditores activos con correo registrado
     */
    private function obtenerAuditoresConCorreo() {
        $result = $this->conn->query("SELECT id, nombre as nombre_completo, email
                                      FROM usuarios 
                                      WHERE perfil = 'auditor' AND activo = 1 AND email IS NOT NULL AND email != ''
                                      ORDER BY nombre");
        $auditores = [];
        while ($row = $result->fetch_assoc()) {
            $auditores[] = $row;
        }
        return $auditores;
    }
    
    /**
     * Obtener ítems de las direcciones asignadas a un director
     */
    private function obtenerItemsDirector($director_id, $mes = null) {
        // Obtener direcciones del director
        $stmt = $this->conn->prepare("SELECT id FROM direcciones WHERE director_id = ? AND activa = 1");
        $stmt->bind_param('i', $director_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $direccion_ids = [];
        while ($row = $resultado->fetch_assoc()) {
            $direccion_ids[] = $row['id'];
        }
        $stmt->close();
        
        if (empty($direccion_ids)) {
            return [];
        }
        
        // Obtener ítems de esas direcciones
        $placeholders = implode(',', array_fill(0, count($direccion_ids), '?'));
        $types = str_repeat('i', count($direccion_ids));
        
        $sql = "SELECT id, nombre, periodicidad, mes_carga_anual FROM items_transparencia 
                WHERE direccion_id IN ($placeholders) AND activo = 1";
        
        // Filtrar por mes si se proporciona
        if ($mes !== null) {
            // Meses para trimestrales: 3, 6, 9, 12
            $mesesTrimestral = [3, 6, 9, 12];
            $esMesTrimestral = in_array($mes, $mesesTrimestral) ? 'TRUE' : 'FALSE';
            
            // Meses para semestrales: 1, 7
            $mesesSemestral = [1, 7];
            $esMesSemestral = in_array($mes, $mesesSemestral) ? 'TRUE' : 'FALSE';
            
            $sql .= " AND (
                periodicidad = 'mensual' 
                OR (periodicidad = 'trimestral' AND $esMesTrimestral)
                OR (periodicidad = 'semestral' AND $esMesSemestral)
                OR (periodicidad = 'anual' AND mes_carga_anual = $mes)
            )";
        }
        
        $sql .= " ORDER BY nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$direccion_ids);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $items = [];
        while ($row = $resultado->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        
        return $items;
    }
    
    /**
     * Generar resumen general de todos los ítems del municipio para un período
     */
    private function obtenerResumenGeneralMunicipio($mes, $ano) {
        // Obtener todos los items activos agrupados por dirección
        $result = $this->conn->query("SELECT i.id, i.nombre, i.periodicidad, i.direccion_id,
                                             i.mes_carga_anual, d.nombre as direccion_nombre
                                      FROM items_transparencia i
                                      LEFT JOIN direcciones d ON i.direccion_id = d.id
                                      WHERE i.activo = 1
                                      ORDER BY d.nombre, i.nombre");
        
        $items_por_direccion = [];
        while ($item = $result->fetch_assoc()) {
            $dir_nombre = $item['direccion_nombre'] ?? 'Sin Dirección';
            if (!isset($items_por_direccion[$dir_nombre])) {
                $items_por_direccion[$dir_nombre] = [];
            }
            $items_por_direccion[$dir_nombre][] = $item;
        }
        
        $html = '';
        $total_general = 0;
        $pub_general = 0;
        $car_general = 0;
        $pen_general = 0;
        
        foreach ($items_por_direccion as $dir_nombre => $items) {
            $html .= '<h4 style="margin-top:15px; color:#1a3a5c;">' . htmlspecialchars($dir_nombre) . '</h4>';
            $html .= '<table style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:15px;" border="1" cellpadding="6">';
            $html .= '<thead><tr style="background-color:#e9ecef;">';
            $html .= '<th>Ítem</th><th>Estado</th><th>Fecha Carga</th><th>Fecha Publicación</th>';
            $html .= '</tr></thead><tbody>';
            
            foreach ($items as $item) {
                // Determinar mes de búsqueda según periodicidad
                $mes_busqueda = $mes;
                if ($item['periodicidad'] === 'anual') {
                    $mes_busqueda = intval($item['mes_carga_anual'] ?? 1);
                } elseif ($item['periodicidad'] === 'trimestral') {
                    $mes_busqueda = (int)(ceil($mes / 3) * 3);
                } elseif ($item['periodicidad'] === 'semestral') {
                    $mes_busqueda = $mes <= 6 ? 1 : 7;
                }
                
                // Verificar Sin Movimiento
                $sinMovimiento = false;
                $sinMovFecha = null;
                $checkSinMov = $this->conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
                if ($checkSinMov && $checkSinMov->num_rows > 0) {
                    $stmt = $this->conn->prepare("SELECT id, fecha_creacion FROM observaciones_sin_movimiento 
                        WHERE item_id = ? AND mes = ? AND ano = ? LIMIT 1");
                    $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                    $stmt->execute();
                    $smResult = $stmt->get_result();
                    if ($smRow = $smResult->fetch_assoc()) {
                        $sinMovimiento = true;
                        $sinMovFecha = $smRow['fecha_creacion'];
                    }
                    $stmt->close();
                }
                
                // Buscar documento
                $documento = null;
                if ($sinMovimiento) {
                    $stmt = $this->conn->prepare("SELECT id, fecha_subida FROM documentos 
                        WHERE item_id = ? AND mes_carga = ? AND ano_carga = ? AND titulo LIKE 'Sin Movimiento%'
                        ORDER BY fecha_subida DESC LIMIT 1");
                    $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                    $stmt->execute();
                    $documento = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                } else {
                    $stmt = $this->conn->prepare("SELECT id, fecha_subida FROM documentos 
                        WHERE item_id = ? AND mes_carga = ? AND ano_carga = ?
                        AND (titulo NOT LIKE 'Sin Movimiento%' OR titulo IS NULL)
                        ORDER BY fecha_subida DESC LIMIT 1");
                    $stmt->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                    $stmt->execute();
                    $documento = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
                
                // Buscar verificador
                $verificador = null;
                if ($documento) {
                    $stmt = $this->conn->prepare("SELECT fecha_carga_portal FROM verificadores_publicador 
                        WHERE documento_id = ? ORDER BY fecha_carga_portal DESC LIMIT 1");
                    $stmt->bind_param('i', $documento['id']);
                    $stmt->execute();
                    $verificador = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
                
                $total_general++;
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['nombre']) . '</td>';
                
                if ($verificador) {
                    $pub_general++;
                    $label = $sinMovimiento ? '✓ Sin Movimiento (Publicado)' : '✓ Publicado';
                    $html .= '<td style="color:green;"><strong>' . $label . '</strong></td>';
                    $html .= '<td>' . date('d/m/Y', strtotime($documento['fecha_subida'])) . '</td>';
                    $html .= '<td>' . date('d/m/Y', strtotime($verificador['fecha_carga_portal'])) . '</td>';
                } elseif ($documento) {
                    $car_general++;
                    $label = $sinMovimiento ? '⚠ Sin Movimiento (Sin Publicar)' : '⚠ Cargado (Sin Publicar)';
                    $html .= '<td style="color:orange;"><strong>' . $label . '</strong></td>';
                    $html .= '<td>' . date('d/m/Y', strtotime($documento['fecha_subida'])) . '</td>';
                    $html .= '<td><em>Pendiente</em></td>';
                } elseif ($sinMovimiento) {
                    $car_general++;
                    $html .= '<td style="color:orange;"><strong>⚠ Sin Movimiento (Sin Publicar)</strong></td>';
                    $html .= '<td>' . ($sinMovFecha ? date('d/m/Y', strtotime($sinMovFecha)) : '-') . '</td>';
                    $html .= '<td><em>Pendiente</em></td>';
                } else {
                    $pen_general++;
                    $html .= '<td style="color:red;"><strong>✗ Pendiente</strong></td>';
                    $html .= '<td colspan="2"><em>Sin carga</em></td>';
                }
                
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        // Resumen totales
        $html .= '<div style="margin-top:20px; padding:15px; background-color:#f8f9fa; border-radius:5px;">';
        $html .= '<h4 style="margin-top:0;">Resumen General del Municipio:</h4>';
        $html .= '<ul>';
        $html .= '<li><strong>Total de ítems:</strong> ' . $total_general . '</li>';
        $html .= '<li><strong style="color:green;">Publicados:</strong> ' . $pub_general . '</li>';
        $html .= '<li><strong style="color:orange;">Cargados (pendientes de publicar):</strong> ' . $car_general . '</li>';
        $html .= '<li><strong style="color:red;">Pendientes de carga:</strong> ' . $pen_general . '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generar resumen solo de las direcciones asignadas a un director
     */
    private function obtenerResumenDireccionesDirector($director_id, $mes, $ano) {
        // Obtener direcciones asignadas al director
        $stmt = $this->conn->prepare("SELECT id, nombre FROM direcciones WHERE director_id = ? AND activa = 1 ORDER BY nombre");
        $stmt->bind_param('i', $director_id);
        $stmt->execute();
        $direcciones = $stmt->get_result();
        $stmt->close();
        
        $dir_ids = [];
        $dir_nombres = [];
        while ($d = $direcciones->fetch_assoc()) {
            $dir_ids[] = $d['id'];
            $dir_nombres[$d['id']] = $d['nombre'];
        }
        
        if (empty($dir_ids)) {
            return '<p><em>No tiene direcciones asignadas.</em></p>';
        }
        
        // Obtener ítems de esas direcciones
        $placeholders = implode(',', array_fill(0, count($dir_ids), '?'));
        $types = str_repeat('i', count($dir_ids));
        
        $stmt = $this->conn->prepare("SELECT i.id, i.nombre, i.periodicidad, i.direccion_id, i.mes_carga_anual
            FROM items_transparencia i
            WHERE i.activo = 1 AND i.direccion_id IN ($placeholders)
            ORDER BY i.direccion_id, i.nombre");
        $stmt->bind_param($types, ...$dir_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        $items_por_direccion = [];
        while ($item = $result->fetch_assoc()) {
            $did = $item['direccion_id'];
            if (!isset($items_por_direccion[$did])) {
                $items_por_direccion[$did] = [];
            }
            $items_por_direccion[$did][] = $item;
        }
        
        $html = '';
        $total = 0;
        $pub = 0;
        $car = 0;
        $pen = 0;
        
        foreach ($items_por_direccion as $did => $items) {
            $html .= '<h4 style="margin-top:15px; color:#1a3a5c;">' . htmlspecialchars($dir_nombres[$did]) . '</h4>';
            $html .= '<table style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:15px;" border="1" cellpadding="6">';
            $html .= '<thead><tr style="background-color:#e9ecef;">';
            $html .= '<th>Ítem</th><th>Estado</th><th>Fecha Carga</th><th>Fecha Publicación</th>';
            $html .= '</tr></thead><tbody>';
            
            foreach ($items as $item) {
                // Determinar mes de búsqueda según periodicidad
                $mes_busqueda = $mes;
                if ($item['periodicidad'] === 'anual') {
                    $mes_busqueda = intval($item['mes_carga_anual'] ?? 1);
                } elseif ($item['periodicidad'] === 'trimestral') {
                    $mes_busqueda = (int)(ceil($mes / 3) * 3);
                } elseif ($item['periodicidad'] === 'semestral') {
                    $mes_busqueda = $mes <= 6 ? 1 : 7;
                }
                
                // Verificar Sin Movimiento
                $sinMovimiento = false;
                $sinMovFecha = null;
                $checkSinMov = $this->conn->query("SHOW TABLES LIKE 'observaciones_sin_movimiento'");
                if ($checkSinMov && $checkSinMov->num_rows > 0) {
                    $stmtSM = $this->conn->prepare("SELECT id, fecha_creacion FROM observaciones_sin_movimiento 
                        WHERE item_id = ? AND mes = ? AND ano = ? LIMIT 1");
                    $stmtSM->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                    $stmtSM->execute();
                    $smResult = $stmtSM->get_result();
                    if ($smRow = $smResult->fetch_assoc()) {
                        $sinMovimiento = true;
                        $sinMovFecha = $smRow['fecha_creacion'];
                    }
                    $stmtSM->close();
                }
                
                // Buscar documento
                $documento = null;
                if ($sinMovimiento) {
                    $stmtD = $this->conn->prepare("SELECT id, fecha_subida FROM documentos 
                        WHERE item_id = ? AND mes_carga = ? AND ano_carga = ? AND titulo LIKE 'Sin Movimiento%'
                        ORDER BY fecha_subida DESC LIMIT 1");
                    $stmtD->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                    $stmtD->execute();
                    $documento = $stmtD->get_result()->fetch_assoc();
                    $stmtD->close();
                } else {
                    $stmtD = $this->conn->prepare("SELECT id, fecha_subida FROM documentos 
                        WHERE item_id = ? AND mes_carga = ? AND ano_carga = ?
                        AND (titulo NOT LIKE 'Sin Movimiento%' OR titulo IS NULL)
                        ORDER BY fecha_subida DESC LIMIT 1");
                    $stmtD->bind_param('iii', $item['id'], $mes_busqueda, $ano);
                    $stmtD->execute();
                    $documento = $stmtD->get_result()->fetch_assoc();
                    $stmtD->close();
                }
                
                // Buscar verificador
                $verificador = null;
                if ($documento) {
                    $stmtV = $this->conn->prepare("SELECT fecha_carga_portal FROM verificadores_publicador 
                        WHERE documento_id = ? ORDER BY fecha_carga_portal DESC LIMIT 1");
                    $stmtV->bind_param('i', $documento['id']);
                    $stmtV->execute();
                    $verificador = $stmtV->get_result()->fetch_assoc();
                    $stmtV->close();
                }
                
                $total++;
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['nombre']) . '</td>';
                
                if ($verificador) {
                    $pub++;
                    $label = $sinMovimiento ? '✓ Sin Movimiento (Publicado)' : '✓ Publicado';
                    $html .= '<td style="color:green;"><strong>' . $label . '</strong></td>';
                    $html .= '<td>' . date('d/m/Y', strtotime($documento['fecha_subida'])) . '</td>';
                    $html .= '<td>' . date('d/m/Y', strtotime($verificador['fecha_carga_portal'])) . '</td>';
                } elseif ($documento) {
                    $car++;
                    $label = $sinMovimiento ? '⚠ Sin Movimiento (Sin Publicar)' : '⚠ Cargado (Sin Publicar)';
                    $html .= '<td style="color:orange;"><strong>' . $label . '</strong></td>';
                    $html .= '<td>' . date('d/m/Y', strtotime($documento['fecha_subida'])) . '</td>';
                    $html .= '<td><em>Pendiente</em></td>';
                } elseif ($sinMovimiento) {
                    $car++;
                    $html .= '<td style="color:orange;"><strong>⚠ Sin Movimiento (Sin Publicar)</strong></td>';
                    $html .= '<td>' . ($sinMovFecha ? date('d/m/Y', strtotime($sinMovFecha)) : '-') . '</td>';
                    $html .= '<td><em>Pendiente</em></td>';
                } else {
                    $pen++;
                    $html .= '<td style="color:red;"><strong>✗ Pendiente</strong></td>';
                    $html .= '<td colspan="2"><em>Sin carga</em></td>';
                }
                
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        // Resumen
        $html .= '<div style="margin-top:20px; padding:15px; background-color:#f8f9fa; border-radius:5px;">';
        $html .= '<h4 style="margin-top:0;">Resumen de sus Direcciones:</h4>';
        $html .= '<ul>';
        $html .= '<li><strong>Total de ítems:</strong> ' . $total . '</li>';
        $html .= '<li><strong style="color:green;">Publicados:</strong> ' . $pub . '</li>';
        $html .= '<li><strong style="color:orange;">Cargados (pendientes de publicar):</strong> ' . $car . '</li>';
        $html .= '<li><strong style="color:red;">Pendientes de carga:</strong> ' . $pen . '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Enviar correo al cargador cuando el publicador observa su documento
     */
    public function enviarDocumentoObservado($observacion_id) {
        // Obtener datos de la observación
        $stmt = $this->conn->prepare("
            SELECT o.*, 
                   d.id as documento_id, d.titulo as documento_titulo,
                   i.nombre as item_nombre, i.numeracion as item_numeracion,
                   u_cargador.nombre as cargador_nombre, u_cargador.email as cargador_email,
                   u_publicador.nombre as publicador_nombre
            FROM observaciones_documentos o
            INNER JOIN documentos d ON o.documento_id = d.id
            INNER JOIN items_transparencia i ON o.item_id = i.id
            INNER JOIN usuarios u_cargador ON o.cargador_id = u_cargador.id
            INNER JOIN usuarios u_publicador ON o.observado_por = u_publicador.id
            WHERE o.id = ?
        ");
        $stmt->bind_param('i', $observacion_id);
        $stmt->execute();
        $obs = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$obs) {
            throw new Exception('No se encontró la observación');
        }
        
        // Obtener plantilla
        $plantilla = $this->obtenerPlantilla('documento_observado');
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de documento observado');
        }
        
        // Construir enlace directo al mes/año del documento
        $enlace_sistema = SITE_URL . 'usuario/dashboard.php?mes=' . $obs['mes'] . '&ano=' . $obs['ano'];
        
        // Variables para reemplazar
        $variables = [
            '{nombre_cargador}' => $obs['cargador_nombre'],
            '{item_nombre}' => $obs['item_nombre'],
            '{item_numeracion}' => $obs['item_numeracion'],
            '{mes_carga}' => $this->nombreMes($obs['mes']),
            '{ano_carga}' => $obs['ano'],
            '{observacion}' => nl2br(htmlspecialchars($obs['observacion'])),
            '{enlace_sistema}' => $enlace_sistema,
            '{observado_por}' => $obs['publicador_nombre']
        ];
        
        $asunto = $this->reemplazarVariables($plantilla['asunto'], $variables);
        $cuerpo = $this->reemplazarVariables($plantilla['cuerpo'], $variables);
        
        // Enviar correo
        $exito = $this->email_sender->enviarCorreo(
            $obs['cargador_email'],
            $asunto,
            $cuerpo,
            $obs['cargador_nombre']
        );
        
        if (!$exito) {
            throw new Exception('Error al enviar correo: ' . $this->email_sender->getError());
        }
        
        // Registrar en historial
        $detalles = [
            [
                'observacion_id' => $observacion_id,
                'documento_id' => $obs['documento_id'],
                'item_id' => $obs['item_id'],
                'cargador_id' => $obs['cargador_id'],
                'email' => $obs['cargador_email'],
                'estado' => 'exitoso'
            ]
        ];
        $this->registrarHistorial(
            $plantilla['id'], 
            'individual', 
            $obs['cargador_id'], 
            1, 
            $obs['mes'], 
            $obs['ano'], 
            1, 
            0, 
            $detalles
        );
        
        return true;
    }
    
    /**
     * Enviar correo de recuperación de contraseña
     */
    public function enviarRecuperacionPassword($usuario_id) {
        $plantilla = $this->obtenerPlantilla('recuperar_password');
        
        if (!$plantilla) {
            throw new Exception('No se encontró la plantilla de recuperación de contraseña');
        }
        
        // Obtener datos del usuario
        $stmt = $this->conn->prepare("SELECT id, nombre, email FROM usuarios WHERE id = ? AND activo = 1");
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$usuario) {
            throw new Exception('Usuario no encontrado o inactivo');
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        
        // Calcular expiración (1 hora)
        $fecha_expiracion = date('Y-m-d H:i:s', time() + 3600);
        
        // Guardar token en la base de datos
        $stmt = $this->conn->prepare("INSERT INTO password_reset_tokens (usuario_id, token, fecha_expiracion) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $usuario_id, $token, $fecha_expiracion);
        $stmt->execute();
        $stmt->close();
        
        // Generar enlace de recuperación
        $enlace_recuperacion = SITE_URL . 'resetear_password.php?token=' . $token;
        
        // Formatear hora de expiración
        $hora_expiracion = date('H:i', time() + 3600);
        
        // Reemplazar variables en el correo
        $variables = [
            '{nombre_usuario}' => $usuario['nombre'],
            '{enlace_recuperacion}' => $enlace_recuperacion,
            '{fecha_expiracion}' => $hora_expiracion
        ];
        
        $asunto_final = $plantilla['asunto'];
        $cuerpo_final = str_replace(array_keys($variables), array_values($variables), $plantilla['cuerpo']);
        
        // Enviar correo
        $emailSender = new EmailSender($this->conn);
        $resultado = $emailSender->enviarCorreo(
            $usuario['email'],
            $asunto_final,
            $cuerpo_final,
            $usuario['nombre']
        );
        
        if (!$resultado) {
            throw new Exception('No se pudo enviar el correo de recuperación');
        }
        
        return true;
    }
}
