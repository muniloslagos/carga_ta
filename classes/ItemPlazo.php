<?php
require_once __DIR__ . '/PlazoCalculator.php';

class ItemPlazo {
    private $db;
    private $table = 'item_plazos';
    private $plazosBaseCache = [];
    private $soportaPlazosBase = null;

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

    /** Obtiene la regla base permanente de un ítem; NULL equivale a 6/10 general. */
    public function getPlazoBaseItem($item_id) {
        $item_id = (int)$item_id;
        if (array_key_exists($item_id, $this->plazosBaseCache)) {
            return $this->plazosBaseCache[$item_id];
        }
        if (!$this->tieneColumnasPlazoBase()) {
            $this->plazosBaseCache[$item_id] = null;
            return null;
        }
        $sql = "SELECT id, plazo_dias_cargador, plazo_dias_publicador
                FROM items_transparencia WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $this->plazosBaseCache[$item_id] = $stmt->get_result()->fetch_assoc() ?: null;
        return $this->plazosBaseCache[$item_id];
    }

    /** Permite conservar el plazo general mientras la migración aún no se instala. */
    public function tieneColumnasPlazoBase() {
        if ($this->soportaPlazosBase !== null) {
            return $this->soportaPlazosBase;
        }

        $resultado = $this->db->query("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'items_transparencia'
              AND COLUMN_NAME IN ('plazo_dias_cargador', 'plazo_dias_publicador')
        ");
        $fila = $resultado ? $resultado->fetch_assoc() : null;
        $this->soportaPlazosBase = $fila && (int)$fila['total'] === 2;
        return $this->soportaPlazosBase;
    }

    /** Lista únicamente los ítems activos que tienen una regla personalizada. */
    public function getItemsConPlazoBase() {
        return $this->db->query("
            SELECT i.id, i.numeracion, i.nombre, i.periodicidad,
                   i.plazo_dias_cargador, i.plazo_dias_publicador,
                   d.nombre AS direccion_nombre
            FROM items_transparencia i
            LEFT JOIN direcciones d ON d.id = i.direccion_id
            WHERE i.activo = 1
              AND i.plazo_dias_cargador IS NOT NULL
              AND i.plazo_dias_publicador IS NOT NULL
            ORDER BY i.numeracion, i.nombre
        ");
    }

    /** Lista ítems programados que todavía heredan el plazo general. */
    public function getItemsSinPlazoBase() {
        return $this->db->query("
            SELECT id, numeracion, nombre, periodicidad
            FROM items_transparencia
            WHERE activo = 1
              AND periodicidad <> 'ocurrencia'
              AND plazo_dias_cargador IS NULL
              AND plazo_dias_publicador IS NULL
            ORDER BY numeracion, nombre
        ");
    }

    public function guardarPlazoBaseItem($item_id, $plazoCargador, $plazoPublicador) {
        $sql = "UPDATE items_transparencia
                SET plazo_dias_cargador = ?, plazo_dias_publicador = ?
                WHERE id = ? AND activo = 1 AND periodicidad <> 'ocurrencia'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $plazoCargador, $plazoPublicador, $item_id);
        $resultado = $stmt->execute();
        unset($this->plazosBaseCache[(int)$item_id]);
        return $resultado;
    }

    public function eliminarPlazoBaseItem($item_id) {
        $sql = "UPDATE items_transparencia
                SET plazo_dias_cargador = NULL, plazo_dias_publicador = NULL
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $item_id);
        $resultado = $stmt->execute();
        unset($this->plazosBaseCache[(int)$item_id]);
        return $resultado;
    }

    private function getDiasBase($item_id) {
        $regla = $this->getPlazoBaseItem($item_id);
        return [
            !empty($regla['plazo_dias_cargador']) ? (int)$regla['plazo_dias_cargador'] : 6,
            !empty($regla['plazo_dias_publicador']) ? (int)$regla['plazo_dias_publicador'] : 10,
        ];
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

        // Una fecha fija para el período tiene prioridad sobre cualquier cálculo.
        if ($personalizado && !empty($personalizado['plazo_interno'])) {
            return $personalizado['plazo_interno'];
        }

        [$diasBaseCargador] = $this->getDiasBase($item_id);
        $diasExtra = $personalizado ? (int)($personalizado['dias_extra_cargador'] ?? 0) : 0;
        return PlazoCalculator::calcularPlazoEnvioConfigurado(
            $periodicidad,
            (int)$ano,
            (int)$mes,
            $diasBaseCargador,
            $diasExtra
        );
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

        // Una fecha fija para el período tiene prioridad sobre cualquier cálculo.
        if ($personalizado && !empty($personalizado['fecha_carga_portal'])) {
            return $personalizado['fecha_carga_portal'];
        }

        [, $diasBasePublicador] = $this->getDiasBase($item_id);
        $diasExtra = $personalizado ? (int)($personalizado['dias_extra_publicador'] ?? 0) : 0;
        return PlazoCalculator::calcularPlazoPublicacionConfigurado(
            $periodicidad,
            (int)$ano,
            (int)$mes,
            $diasBasePublicador,
            $diasExtra
        );
    }
}

