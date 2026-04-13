<?php
require_once __DIR__ . '/PlazoCalculator.php';

class ItemPlazo {
    private $db;
    private $table = 'item_plazos';

    public function __construct($db) {
        $this->db = $db;
    }

    // Obtener plazo de un item para un mes/año específico
    public function getByItemAndMes($item_id, $ano, $mes) {
        $sql = "SELECT * FROM {$this->table} WHERE item_id = ? AND ano = ? AND mes = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $item_id, $ano, $mes);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Obtener todos los plazos de un item
    public function getByItem($item_id) {
        $sql = "SELECT * FROM {$this->table} WHERE item_id = ? ORDER BY ano DESC, mes DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Crear plazo
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (item_id, ano, mes, plazo_interno, fecha_carga_portal, dias_extra_cargador, dias_extra_publicador, motivo_extension)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                plazo_interno = VALUES(plazo_interno),
                fecha_carga_portal = VALUES(fecha_carga_portal),
                dias_extra_cargador = VALUES(dias_extra_cargador),
                dias_extra_publicador = VALUES(dias_extra_publicador),
                motivo_extension = VALUES(motivo_extension),
                fecha_actualizacion = NOW()";

        $stmt = $this->db->prepare($sql);
        $motivo = $data['motivo_extension'] ?? null;
        $dias_extra_cargador = (int)($data['dias_extra_cargador'] ?? 0);
        $dias_extra_publicador = (int)($data['dias_extra_publicador'] ?? 0);
        $plazo_interno = $data['plazo_interno'] ?? null;
        $fecha_carga_portal = $data['fecha_carga_portal'] ?? null;
        $stmt->bind_param("iiissiis",
            $data['item_id'],
            $data['ano'],
            $data['mes'],
            $plazo_interno,
            $fecha_carga_portal,
            $dias_extra_cargador,
            $dias_extra_publicador,
            $motivo
        );

        return $stmt->execute();
    }

    // Actualizar plazo
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                plazo_interno = ?,
                fecha_carga_portal = ?,
                dias_extra_cargador = ?,
                dias_extra_publicador = ?,
                motivo_extension = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $motivo = $data['motivo_extension'] ?? null;
        $dias_extra_cargador = (int)($data['dias_extra_cargador'] ?? 0);
        $dias_extra_publicador = (int)($data['dias_extra_publicador'] ?? 0);
        $plazo_interno = $data['plazo_interno'] ?? null;
        $fecha_carga_portal = $data['fecha_carga_portal'] ?? null;
        $stmt->bind_param("ssiisi",
            $plazo_interno,
            $fecha_carga_portal,
            $dias_extra_cargador,
            $dias_extra_publicador,
            $motivo,
            $id
        );

        return $stmt->execute();
    }

    // Obtener plazo actual (mes anterior del mes actual)
    public function getPlazoActual($item_id) {
        // Mes anterior al mes actual
        $mesActual = (int)date('m');
        $anoActual = (int)date('Y');
        
        $mesAnterior = $mesActual - 1;
        $anoAnterior = $anoActual;
        
        if ($mesAnterior < 1) {
            $mesAnterior = 12;
            $anoAnterior = $anoActual - 1;
        }

        return $this->getByItemAndMes($item_id, $anoAnterior, $mesAnterior);
    }

    // Obtener meses disponibles para un item (que tengan plazo configurado)
    public function getMesesDisponibles($item_id) {
        $sql = "SELECT DISTINCT ano, mes FROM {$this->table} 
                WHERE item_id = ? 
                ORDER BY ano DESC, mes DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Obtiene el plazo FINAL de ENVÍO para un item en un período.
     * Si hay días extra configurados, calcula: N-ésimo día hábil con (6 + dias_extra).
     * Si hay fecha fija (plazo_interno), la usa directamente.
     * Si no hay nada, calcula automáticamente (6° día hábil).
     * @return string|null  'Y-m-d' o null
     */
    public function getPlazoFinal($item_id, $ano, $mes, $periodicidad = null) {
        if (!$periodicidad) {
            $sql = "SELECT periodicidad FROM items_transparencia WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $periodicidad = $row['periodicidad'] ?? 'mensual';
        }

        $personalizado = $this->getByItemAndMes($item_id, $ano, $mes);

        // Días extra configurados → calcular base + extra
        if ($personalizado && !empty($personalizado['dias_extra_cargador'])) {
            $diasExtra = (int)$personalizado['dias_extra_cargador'];
            return PlazoCalculator::calcularPlazoEnvioConExtra($periodicidad, (int)$ano, (int)$mes, $diasExtra);
        }

        // Fecha fija legacy (plazo_interno)
        if ($personalizado && !empty($personalizado['plazo_interno'])) {
            return $personalizado['plazo_interno'];
        }

        // Calcular automáticamente (6° día hábil)
        return PlazoCalculator::calcularPlazoEnvio($periodicidad, (int)$ano, (int)$mes);
    }

    /**
     * Obtiene el plazo FINAL de PUBLICACIÓN para un item en un período.
     * Si hay días extra configurados, calcula: N-ésimo día hábil con (10 + dias_extra).
     * Si hay fecha fija (fecha_carga_portal), la usa directamente.
     * Si no hay nada, calcula automáticamente (10° día hábil).
     * @return string|null 'Y-m-d' o null
     */
    public function getPlazoPublicacionFinal($item_id, $ano, $mes, $periodicidad = null) {
        if (!$periodicidad) {
            $sql = "SELECT periodicidad FROM items_transparencia WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $periodicidad = $row['periodicidad'] ?? 'mensual';
        }

        $personalizado = $this->getByItemAndMes($item_id, $ano, $mes);

        // Días extra configurados → calcular base + extra
        if ($personalizado && !empty($personalizado['dias_extra_publicador'])) {
            $diasExtra = (int)$personalizado['dias_extra_publicador'];
            return PlazoCalculator::calcularPlazoPublicacionConExtra($periodicidad, (int)$ano, (int)$mes, $diasExtra);
        }

        // Fecha fija legacy (fecha_carga_portal)
        if ($personalizado && !empty($personalizado['fecha_carga_portal'])) {
            return $personalizado['fecha_carga_portal'];
        }

        // Calcular automáticamente (10° día hábil)
        return PlazoCalculator::calcularPlazoPublicacion($periodicidad, (int)$ano, (int)$mes);
    }
}

