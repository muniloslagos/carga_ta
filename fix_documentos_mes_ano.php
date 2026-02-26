<?php
/**
 * CORREGIR DOCUMENTOS SIN MES/AÑO
 * Este script actualiza los documentos que tienen mes=NULL o ano=NULL
 * en la tabla documento_seguimiento
 */

require_once 'config/config.php';

header('Content-Type: text/html; charset=UTF-8');

// Conexión a BD
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Corregir Documentos</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔧 CORRECCIÓN DE DOCUMENTOS SIN MES/AÑO</h1>";
echo "<p>Fecha: " . date('Y-m-d H:i:s') . "</p>";

// ==========================================
// 1. IDENTIFICAR DOCUMENTOS PROBLEMÁTICOS
// ==========================================
echo "<div class='section'>";
echo "<h2>1️⃣ Documentos Sin Mes/Año</h2>";

$sql = "SELECT 
            d.id as doc_id,
            d.titulo,
            d.fecha_subida,
            d.item_id,
            i.numeracion,
            i.nombre as item_nombre,
            i.periodicidad,
            ds.mes,
            ds.ano,
            u.nombre as usuario_nombre
        FROM documentos d
        LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
        LEFT JOIN items_transparencia i ON d.item_id = i.id
        LEFT JOIN usuarios u ON d.usuario_id = u.id
        WHERE ds.mes IS NULL OR ds.ano IS NULL OR ds.mes = 0 OR ds.ano = 0
        ORDER BY d.fecha_subida DESC";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<p class='ok'>✅ No hay documentos con mes/año NULL</p>";
} else {
    echo "<p class='warning'>⚠️ Encontrados <strong>" . $result->num_rows . "</strong> documentos con problemas</p>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Item</th><th>Periodicidad</th><th>Fecha Subida</th><th>Mes Actual</th><th>Año Actual</th></tr>";
    
    $documentos = [];
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
        echo "<tr>";
        echo "<td>" . $row['doc_id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['titulo'], 0, 40)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['numeracion']) . " - " . htmlspecialchars(substr($row['item_nombre'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($row['periodicidad']) . "</td>";
        echo "<td>" . $row['fecha_subida'] . "</td>";
        echo "<td>" . ($row['mes'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['ano'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</div>";

// ==========================================
// 2. CALCULAR Y PROPONER CORRECCIÓN
// ==========================================
if (!empty($documentos)) {
    echo "<div class='section'>";
    echo "<h2>2️⃣ Corrección Propuesta</h2>";
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Título</th><th>Periodicidad</th><th>Mes Calculado</th><th>Año Calculado</th><th>Justificación</th></tr>";
    
    $correcciones = [];
    
    foreach ($documentos as $doc) {
        // Extraer mes y año de la fecha_subida
        $fecha_subida = strtotime($doc['fecha_subida']);
        $mes_subida = (int)date('n', $fecha_subida);  // 1-12
        $ano_subida = (int)date('Y', $fecha_subida);
        
        $periodicidad = $doc['periodicidad'];
        
        // Calcular mes/año según periodicidad
        switch ($periodicidad) {
            case 'mensual':
                // Para mensual: mes ANTERIOR a la fecha de subida
                $mes_calc = $mes_subida - 1;
                $ano_calc = $ano_subida;
                if ($mes_calc < 1) {
                    $mes_calc = 12;
                    $ano_calc--;
                }
                $justificacion = "Mes anterior a fecha de subida";
                break;
                
            case 'trimestral':
                // Para trimestral: primer mes del trimestre actual
                $trimestre_actual = ceil($mes_subida / 3);
                $mes_calc = ($trimestre_actual - 1) * 3 + 1;  // 1, 4, 7, 10
                $ano_calc = $ano_subida;
                $justificacion = "Trimestre " . $trimestre_actual . " (mes inicio: $mes_calc)";
                break;
                
            case 'semestral':
                // Para semestral: primer mes del semestre (1 o 7)
                $mes_calc = ($mes_subida <= 6) ? 1 : 7;
                $ano_calc = $ano_subida;
                $justificacion = "Semestre " . (($mes_calc == 1) ? "1" : "2");
                break;
                
            case 'anual':
                // Para anual: siempre enero del año de subida
                $mes_calc = 1;
                $ano_calc = $ano_subida;
                $justificacion = "Anual - Enero del año de subida";
                break;
                
            case 'ocurrencia':
                // Para ocurrencia: mes de subida
                $mes_calc = $mes_subida;
                $ano_calc = $ano_subida;
                $justificacion = "Evento único - mes de subida";
                break;
                
            default:
                $mes_calc = $mes_subida;
                $ano_calc = $ano_subida;
                $justificacion = "Por defecto - mes de subida";
        }
        
        $correcciones[] = [
            'doc_id' => $doc['doc_id'],
            'item_id' => $doc['item_id'],
            'mes' => $mes_calc,
            'ano' => $ano_calc
        ];
        
        echo "<tr>";
        echo "<td>" . $doc['doc_id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($doc['titulo'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($periodicidad) . "</td>";
        echo "<td class='ok'>" . $mes_calc . "</td>";
        echo "<td class='ok'>" . $ano_calc . "</td>";
        echo "<td>" . $justificacion . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "</div>";
    
    // ==========================================
    // 3. EJECUTAR CORRECCIÓN
    // ==========================================
    echo "<div class='section'>";
    echo "<h2>3️⃣ Ejecución de Corrección</h2>";
    
    $exitosos = 0;
    $fallidos = 0;
    
    foreach ($correcciones as $corr) {
        $doc_id = $corr['doc_id'];
        $item_id = $corr['item_id'];
        $mes = $corr['mes'];
        $ano = $corr['ano'];
        
        // Buscar si ya existe registro en documento_seguimiento
        $sql_check = "SELECT id FROM documento_seguimiento WHERE documento_id = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $result_check = $stmt->get_result();
        
        if ($result_check->num_rows > 0) {
            // UPDATE
            $sql_update = "UPDATE documento_seguimiento 
                          SET mes = ?, ano = ?, estado = 'Cargado'
                          WHERE documento_id = ?";
            $stmt_upd = $conn->prepare($sql_update);
            $stmt_upd->bind_param("iii", $mes, $ano, $doc_id);
            
            if ($stmt_upd->execute()) {
                echo "<p class='ok'>✅ Doc #$doc_id: Actualizado a mes=$mes, ano=$ano</p>";
                $exitosos++;
            } else {
                echo "<p class='error'>❌ Doc #$doc_id: Error al actualizar - " . $stmt_upd->error . "</p>";
                $fallidos++;
            }
        } else {
            // INSERT (no debería pasar porque se inserta al cargar, pero por si acaso)
            $sql_insert = "INSERT INTO documento_seguimiento 
                          (documento_id, item_id, mes, ano, estado, fecha_creacion)
                          VALUES (?, ?, ?, ?, 'Cargado', NOW())";
            $stmt_ins = $conn->prepare($sql_insert);
            $stmt_ins->bind_param("iiii", $doc_id, $item_id, $mes, $ano);
            
            if ($stmt_ins->execute()) {
                echo "<p class='ok'>✅ Doc #$doc_id: Registro creado con mes=$mes, ano=$ano</p>";
                $exitosos++;
            } else {
                echo "<p class='error'>❌ Doc #$doc_id: Error al insertar - " . $stmt_ins->error . "</p>";
                $fallidos++;
            }
        }
    }
    
    echo "<hr>";
    echo "<p><strong>RESUMEN:</strong></p>";
    echo "<p class='ok'>✅ Exitosos: <strong>$exitosos</strong></p>";
    if ($fallidos > 0) {
        echo "<p class='error'>❌ Fallidos: <strong>$fallidos</strong></p>";
    }
    
    echo "</div>";
    
    // ==========================================
    // 4. VERIFICACIÓN FINAL
    // ==========================================
    echo "<div class='section'>";
    echo "<h2>4️⃣ Verificación Final</h2>";
    
    $sql_verify = "SELECT COUNT(*) as total 
                   FROM documentos d
                   LEFT JOIN documento_seguimiento ds ON d.id = ds.documento_id
                   WHERE ds.mes IS NULL OR ds.ano IS NULL OR ds.mes = 0 OR ds.ano = 0";
    $result_verify = $conn->query($sql_verify);
    $row_verify = $result_verify->fetch_assoc();
    
    if ($row_verify['total'] == 0) {
        echo "<p class='ok'>✅ ¡PERFECTO! Ya no quedan documentos con mes/año NULL</p>";
        echo "<p>Ahora todos los documentos deberían mostrarse correctamente en el dashboard.</p>";
    } else {
        echo "<p class='warning'>⚠️ Aún quedan " . $row_verify['total'] . " documentos con problemas</p>";
        echo "<p>Ejecute el script nuevamente o revise manualmente.</p>";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>¿Qué hacer ahora?</strong></p>";
echo "<ol>";
echo "<li>Ejecutar este script en <strong>producción</strong>: <code>http://app.muniloslagos.cl/carga_ta/fix_documentos_mes_ano.php</code></li>";
echo "<li>Verificar que los documentos ahora aparezcan en el dashboard</li>";
echo "<li>Probar cargar un documento nuevo y verificar que se guarde con mes/año correcto</li>";
echo "</ol>";

echo "</body></html>";

$conn->close();
?>
