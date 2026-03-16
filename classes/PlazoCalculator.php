<?php
/**
 * Clase para calcular plazos automáticos según la periodicidad
 * 
 * REGLAS VIGENTES:
 *  Plazo Envío (cargador):     6.° día hábil del mes correspondiente
 *  Plazo Publicación (publicador): 10.° día hábil del mes correspondiente (4 hábiles después del envío)
 *
 *  MENSUAL     : 6.° / 10.° hábil del mes M+2 (enero → marzo, noviembre → enero siguiente)
 *  ANUAL       : 6.° / 10.° hábil de febrero del año en curso
 *  TRIMESTRAL  : Q1→feb, Q2→jul, Q3→oct, Q4→ene siguiente
 *  SEMESTRAL   : H1→jul, H2→ene siguiente
 *  OCURRENCIA  : sin plazo automático (solo configurable)
 */
class PlazoCalculator {

    // ─────────────────────────────────────────────
    //  DÍAS HÁBILES (sin feriados — solo lun-vie)
    // ─────────────────────────────────────────────

    /**
     * Calcula el N-ésimo día hábil de un mes dado (lunes a viernes).
     * @param int $year
     * @param int $month  1-12
     * @param int $n      1=primer hábil, 6=sexto hábil, 10=décimo hábil, etc.
     * @return string  'Y-m-d'
     */
    public static function calcularNesimoDiaHabil(int $year, int $month, int $n): string {
        $count = 0;
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow = (int) date('N', mktime(0, 0, 0, $month, $d, $year)); // 1=lun 7=dom
            if ($dow <= 5) { // lun-vie
                $count++;
                if ($count === $n) {
                    return sprintf('%04d-%02d-%02d', $year, $month, $d);
                }
            }
        }
        // Fallback: si n supera los hábiles del mes devolver último hábil
        return sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    }

    /**
     * Avanza un número determinado de días hábiles a partir de una fecha.
     * @param string $desde  'Y-m-d'
     * @param int    $dias   días hábiles a avanzar
     * @return string 'Y-m-d'
     */
    public static function avanzarDiasHabiles(string $desde, int $dias): string {
        $ts = strtotime($desde);
        $avanzados = 0;
        while ($avanzados < $dias) {
            $ts += 86400; // +1 día
            $dow = (int) date('N', $ts);
            if ($dow <= 5) {
                $avanzados++;
            }
        }
        return date('Y-m-d', $ts);
    }

    // ─────────────────────────────────────────────
    //  MES DEADLINE según periodicidad
    // ─────────────────────────────────────────────

    /**
     * Devuelve [year, month] del mes de vencimiento para plazo ENVÍO (6.° hábil).
     */
    private static function mesDeadlineEnvio(string $periodicidad, int $ano, int $mes): array {
        $p = strtolower($periodicidad);

        if ($p === 'mensual') {
            // M+2: enero→marzo, noviembre→enero siguiente, diciembre→febrero siguiente
            $deadlineMonth = $mes + 2;
            $deadlineYear  = $ano;
            if ($deadlineMonth > 12) {
                $deadlineMonth -= 12;
                $deadlineYear++;
            }
            return [$deadlineYear, $deadlineMonth];
        }

        if ($p === 'anual') {
            return [$ano, 2]; // Siempre febrero del año en curso
        }

        if ($p === 'trimestral') {
            $q = (int) ceil($mes / 3);
            switch ($q) {
                case 1: return [$ano,       2]; // Q1  → 6-feb
                case 2: return [$ano,       7]; // Q2  → 6-jul
                case 3: return [$ano,      10]; // Q3  → 6-oct
                case 4: return [$ano + 1,   1]; // Q4  → 6-ene siguiente
            }
        }

        if ($p === 'semestral') {
            $sem = ($mes <= 6) ? 1 : 2;
            if ($sem === 1) return [$ano,       7]; // H1 → 6-jul
            else            return [$ano + 1,   1]; // H2 → 6-ene siguiente
        }

        return [$ano, $mes]; // ocurrencia / fallback
    }

    // ─────────────────────────────────────────────
    //  API PÚBLICA
    // ─────────────────────────────────────────────

    /**
     * Plazo de ENVÍO automático (6.° día hábil).
     * @return string|null  'Y-m-d' o null si no hay plazo automático (ocurrencia)
     */
    public static function calcularPlazoEnvio(string $periodicidad, int $ano, int $mes): ?string {
        if (strtolower($periodicidad) === 'ocurrencia') {
            return null;
        }
        [$dy, $dm] = self::mesDeadlineEnvio($periodicidad, $ano, $mes);
        return self::calcularNesimoDiaHabil($dy, $dm, 6);
    }

    /**
     * Plazo de PUBLICACIÓN automático (10.° día hábil = 4 hábiles después del plazo envío).
     * @return string|null  'Y-m-d' o null si es ocurrencia
     */
    public static function calcularPlazoPublicacion(string $periodicidad, int $ano, int $mes): ?string {
        if (strtolower($periodicidad) === 'ocurrencia') {
            return null;
        }
        [$dy, $dm] = self::mesDeadlineEnvio($periodicidad, $ano, $mes);
        return self::calcularNesimoDiaHabil($dy, $dm, 10);
    }

    /**
     * Compatibilidad hacia atrás: equivale a calcularPlazoEnvio.
     * (El parámetro $plazoConfigurable ya no se usa; la BD lo gestiona ItemPlazo)
     */
    public static function calcularPlazo(
        string $periodicidad, int $ano, int $mes, ?string $plazoConfigurable = null
    ): ?string {
        if ($plazoConfigurable) {
            return $plazoConfigurable; // prioridad al valor manual del admin
        }
        return self::calcularPlazoEnvio($periodicidad, $ano, $mes);
    }

    // ─────────────────────────────────────────────
    //  HELPERS (usados en dashboard)
    // ─────────────────────────────────────────────

    /** ¿La fecha está dentro del plazo? */
    public static function estaEnPlazo(?string $plazo, ?string $fechaEjecucion = null): ?bool {
        if (!$plazo) return null;
        $fecha = $fechaEjecucion ?? date('Y-m-d');
        return strtotime($fecha) <= strtotime($plazo);
    }

    /** Etiqueta descriptiva del período */
    public static function describirPeriodo(string $periodicidad, int $ano, int $mes): string {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        switch (strtolower($periodicidad)) {
            case 'mensual':    return ($meses[$mes] ?? "Mes $mes") . " $ano";
            case 'anual':      return "Año $ano";
            case 'trimestral': return 'Q' . (int) ceil($mes / 3) . " $ano";
            case 'semestral':  return 'S' . ($mes <= 6 ? 1 : 2) . " $ano";
            default:           return "Período $ano";
        }
    }

    public static function obtenerTrimestre(int $mes): int { return (int) ceil($mes / 3); }
    public static function obtenerSemestre(int $mes): int  { return $mes <= 6 ? 1 : 2; }
}

    
    /**
     * Calcula el plazo para un item según su periodicidad
     * 
     * @param string $periodicidad: 'mensual', 'anual', 'trimestral', 'semestral', 'ocurrencia'
     * @param int $ano: año del período
     * @param int $mes: mes del período (1-12)
     * @param string|null $plazoConfigurable: plazo configurado por admin (para mensual y anual)
     * @return string|null: fecha en formato 'Y-m-d' o null si es ocurrencia
     */
    public static function calcularPlazo($periodicidad, $ano, $mes, $plazoConfigurable = null) {
        switch (strtolower($periodicidad)) {
            case 'mensual':
                // Para mensual, devolver el plazo configurado por el admin
                return $plazoConfigurable;
            
            case 'anual':
                // Para anual, devolver el plazo configurado por el admin
                return $plazoConfigurable;
            
            case 'trimestral':
                return self::calcularPlazoTrimestral($ano, $mes);
            
            case 'semestral':
                return self::calcularPlazoSemestral($ano, $mes);
            
            case 'ocurrencia':
                // Ocurrencia no tiene plazo fijo, es configurable
                return $plazoConfigurable;
            
            default:
                return null;
        }
    }
    
    /**
     * Calcula el plazo trimestral FIJO
     * 7 de abril (Q2), 7 de julio (Q3), 7 de noviembre (Q4), 7 de enero siguiente (Q1)
     */
    private static function calcularPlazoTrimestral($ano, $mes) {
        // Determinar el trimestre
        $trimestre = ceil($mes / 3);
        
        switch ($trimestre) {
            case 1: // Q1: Enero-Marzo → Plazo: 7 de abril
                return "$ano-04-07";
            
            case 2: // Q2: Abril-Junio → Plazo: 7 de julio
                return "$ano-07-07";
            
            case 3: // Q3: Julio-Septiembre → Plazo: 7 de noviembre
                return "$ano-11-07";
            
            case 4: // Q4: Octubre-Diciembre → Plazo: 7 de enero del año siguiente
                $anoSiguiente = $ano + 1;
                return "$anoSiguiente-01-07";
            
            default:
                return null;
        }
    }
    
    /**
     * Calcula el plazo semestral FIJO
     * 7 de agosto (S2), 7 de enero del año siguiente (S1)
     */
    private static function calcularPlazoSemestral($ano, $mes) {
        // Determinar el semestre
        $semestre = $mes <= 6 ? 1 : 2;
        
        if ($semestre === 1) {
            // S1: Enero-Junio → Plazo: 7 de agosto
            return "$ano-08-07";
        } else {
            // S2: Julio-Diciembre → Plazo: 7 de enero del año siguiente
            $anoSiguiente = $ano + 1;
            return "$anoSiguiente-01-07";
        }
    }
    
    /**
     * Obtiene el trimestre de un mes
     */
    public static function obtenerTrimestre($mes) {
        return ceil($mes / 3);
    }
    
    /**
     * Obtiene el semestre de un mes
     */
    public static function obtenerSemestre($mes) {
        return $mes <= 6 ? 1 : 2;
    }
    
    /**
     * Describe el período según la periodicidad
     */
    public static function describirPeriodo($periodicidad, $ano, $mes) {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        
        switch (strtolower($periodicidad)) {
            case 'mensual':
                return $meses[$mes] . ' ' . $ano;
            
            case 'anual':
                return 'Año ' . $ano;
            
            case 'trimestral':
                $trimestre = self::obtenerTrimestre($mes);
                return 'Q' . $trimestre . ' ' . $ano;
            
            case 'semestral':
                $semestre = self::obtenerSemestre($mes);
                return 'S' . $semestre . ' ' . $ano;
            
            case 'ocurrencia':
                return 'Ocurrencia Libre';
            
            default:
                return '';
        }
    }
}
?>
