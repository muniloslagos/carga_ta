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
     * Obtiene el plazo FINAL para un item en un período
     * Considera: plazos automáticos (trimestral, semestral) y personalizados
     * @return string fecha en formato 'Y-m-d' o null
     */
    public function getPlazoFinal($item_id, $ano, $mes, $periodicidad = null) {
        // Si no se proporciona periodicidad, obtenerla de la BD
        if (!$periodicidad) {
            $sql = "SELECT i.periodicidad FROM items i WHERE i.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            $periodicidad = $item['periodicidad'] ?? 'mensual';
        }

        // Obtener plazo personalizado si existe
        $plazoPersonalizado = $this->getByItemAndMes($item_id, $ano, $mes);
        if ($plazoPersonalizado && !empty($plazoPersonalizado['plazo_interno'])) {
            return $plazoPersonalizado['plazo_interno'];
        }

        // Si no hay personalizado, calcular automático
        return PlazoCalculator::calcularPlazo($periodicidad, $ano, $mes, null);
    }
}
?>
