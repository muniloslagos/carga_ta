<?php
/**
 * Clase para calcular plazos automáticos según la periodicidad
 */
class PlazoCalculator {
    
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
