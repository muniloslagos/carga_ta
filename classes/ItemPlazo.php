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
                (item_id, ano, mes, plazo_interno, fecha_carga_portal)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                plazo_interno = VALUES(plazo_interno),
                fecha_carga_portal = VALUES(fecha_carga_portal),
                fecha_actualizacion = NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiiss",
            $data['item_id'],
            $data['ano'],
            $data['mes'],
            $data['plazo_interno'],
            $data['fecha_carga_portal']
        );

        return $stmt->execute();
    }

    // Actualizar plazo
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                plazo_interno = ?,
                fecha_carga_portal = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi",
            $data['plazo_interno'],
            $data['fecha_carga_portal'],
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
     * Prioridad: personalizado en item_plazos > calculado automáticamente.
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

        // Plazo personalizado (columna plazo_interno)
        $personalizado = $this->getByItemAndMes($item_id, $ano, $mes);
        if ($personalizado && !empty($personalizado['plazo_interno'])) {
            return $personalizado['plazo_interno'];
        }

        // Calcular automáticamente
        return PlazoCalculator::calcularPlazoEnvio($periodicidad, (int)$ano, (int)$mes);
    }

    /**
     * Obtiene el plazo FINAL de PUBLICACIÓN para un item en un período.
     * Prioridad: personalizado en item_plazos (columna fecha_carga_portal) > calculado automáticamente.
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

        // Plazo personalizado (columna fecha_carga_portal reutilizada como plazo publicación)
        $personalizado = $this->getByItemAndMes($item_id, $ano, $mes);
        if ($personalizado && !empty($personalizado['fecha_carga_portal'])) {
            return $personalizado['fecha_carga_portal'];
        }

        // Calcular automáticamente (10.° día hábil)
        return PlazoCalculator::calcularPlazoPublicacion($periodicidad, (int)$ano, (int)$mes);
    }
}

