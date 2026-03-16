<?php
/**
 * Clase para calcular plazos automÃ¡ticos segÃºn la periodicidad
 * 
 * REGLAS VIGENTES:
 *  Plazo EnvÃ­o (cargador):     6.Â° dÃ­a hÃ¡bil del mes correspondiente
 *  Plazo PublicaciÃ³n (publicador): 10.Â° dÃ­a hÃ¡bil del mes correspondiente (4 hÃ¡biles despuÃ©s del envÃ­o)
 *
 *  MENSUAL     : 6.Â° / 10.Â° hÃ¡bil del mes M+2 (enero â†’ marzo, noviembre â†’ enero siguiente)
 *  ANUAL       : 6.Â° / 10.Â° hÃ¡bil de febrero del aÃ±o en curso
 *  TRIMESTRAL  : Q1â†’feb, Q2â†’jul, Q3â†’oct, Q4â†’ene siguiente
 *  SEMESTRAL   : H1â†’jul, H2â†’ene siguiente
 *  OCURRENCIA  : sin plazo automÃ¡tico (solo configurable)
 */
class PlazoCalculator {

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  DÃAS HÃBILES (sin feriados â€” solo lun-vie)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Calcula el N-Ã©simo dÃ­a hÃ¡bil de un mes dado (lunes a viernes).
     * @param int $year
     * @param int $month  1-12
     * @param int $n      1=primer hÃ¡bil, 6=sexto hÃ¡bil, 10=dÃ©cimo hÃ¡bil, etc.
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
        // Fallback: si n supera los hÃ¡biles del mes devolver Ãºltimo hÃ¡bil
        return sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    }

    /**
     * Avanza un nÃºmero determinado de dÃ­as hÃ¡biles a partir de una fecha.
     * @param string $desde  'Y-m-d'
     * @param int    $dias   dÃ­as hÃ¡biles a avanzar
     * @return string 'Y-m-d'
     */
    public static function avanzarDiasHabiles(string $desde, int $dias): string {
        $ts = strtotime($desde);
        $avanzados = 0;
        while ($avanzados < $dias) {
            $ts += 86400; // +1 dÃ­a
            $dow = (int) date('N', $ts);
            if ($dow <= 5) {
                $avanzados++;
            }
        }
        return date('Y-m-d', $ts);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  MES DEADLINE segÃºn periodicidad
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Devuelve [year, month] del mes de vencimiento para plazo ENVÃO (6.Â° hÃ¡bil).
     */
    private static function mesDeadlineEnvio(string $periodicidad, int $ano, int $mes): array {
        $p = strtolower($periodicidad);

        if ($p === 'mensual') {
            // M+1: enero→febrero, diciembre→enero siguiente
            $deadlineMonth = $mes + 1;
            $deadlineYear  = $ano;
            if ($deadlineMonth > 12) {
                $deadlineMonth -= 12;
                $deadlineYear++;
            }
            return [$deadlineYear, $deadlineMonth];
        }

        if ($p === 'anual') {
            return [$ano, 2]; // Siempre febrero del aÃ±o en curso
        }

        if ($p === 'trimestral') {
            $q = (int) ceil($mes / 3);
            switch ($q) {
                case 1: return [$ano,       2]; // Q1  â†’ 6-feb
                case 2: return [$ano,       7]; // Q2  â†’ 6-jul
                case 3: return [$ano,      10]; // Q3  â†’ 6-oct
                case 4: return [$ano + 1,   1]; // Q4  â†’ 6-ene siguiente
            }
        }

        if ($p === 'semestral') {
            $sem = ($mes <= 6) ? 1 : 2;
            if ($sem === 1) return [$ano,       7]; // H1 â†’ 6-jul
            else            return [$ano + 1,   1]; // H2 â†’ 6-ene siguiente
        }

        return [$ano, $mes]; // ocurrencia / fallback
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  API PÃšBLICA
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Plazo de ENVÃO automÃ¡tico (6.Â° dÃ­a hÃ¡bil).
     * @return string|null  'Y-m-d' o null si no hay plazo automÃ¡tico (ocurrencia)
     */
    public static function calcularPlazoEnvio(string $periodicidad, int $ano, int $mes): ?string {
        if (strtolower($periodicidad) === 'ocurrencia') {
            return null;
        }
        [$dy, $dm] = self::mesDeadlineEnvio($periodicidad, $ano, $mes);
        return self::calcularNesimoDiaHabil($dy, $dm, 6);
    }

    /**
     * Plazo de PUBLICACIÃ“N automÃ¡tico (10.Â° dÃ­a hÃ¡bil = 4 hÃ¡biles despuÃ©s del plazo envÃ­o).
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
     * Compatibilidad hacia atrÃ¡s: equivale a calcularPlazoEnvio.
     * (El parÃ¡metro $plazoConfigurable ya no se usa; la BD lo gestiona ItemPlazo)
     */
    public static function calcularPlazo(
        string $periodicidad, int $ano, int $mes, ?string $plazoConfigurable = null
    ): ?string {
        if ($plazoConfigurable) {
            return $plazoConfigurable; // prioridad al valor manual del admin
        }
        return self::calcularPlazoEnvio($periodicidad, $ano, $mes);
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  HELPERS (usados en dashboard)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /** Â¿La fecha estÃ¡ dentro del plazo? */
    public static function estaEnPlazo(?string $plazo, ?string $fechaEjecucion = null): ?bool {
        if (!$plazo) return null;
        $fecha = $fechaEjecucion ?? date('Y-m-d');
        return strtotime($fecha) <= strtotime($plazo);
    }

    /** Etiqueta descriptiva del perÃ­odo */
    public static function describirPeriodo(string $periodicidad, int $ano, int $mes): string {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        switch (strtolower($periodicidad)) {
            case 'mensual':    return ($meses[$mes] ?? "Mes $mes") . " $ano";
            case 'anual':      return "AÃ±o $ano";
            case 'trimestral': return 'Q' . (int) ceil($mes / 3) . " $ano";
            case 'semestral':  return 'S' . ($mes <= 6 ? 1 : 2) . " $ano";
            default:           return "PerÃ­odo $ano";
        }
    }

    public static function obtenerTrimestre(int $mes): int { return (int) ceil($mes / 3); }
    public static function obtenerSemestre(int $mes): int  { return $mes <= 6 ? 1 : 2; }
}
