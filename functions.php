<?php
// Habilitar la visualización de errores PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ============================================================
   Conexión a la base de datos
   ============================================================ */

/**
 * Obtiene la conexión a la base de datos.
 * 
 * Incluye el archivo de configuración y verifica errores de conexión.
 *
 * @return mysqli Conexión a la base de datos.
 */
function getDatabaseConnection() {
    include 'db_config.php';
    if ($conn->connect_error) {
        die("Error de conexión a la base de datos: " . $conn->connect_error);
    }
    return $conn;
}

/* ============================================================
   Consultas SELECT
   ============================================================ */

/**
 * Recupera las páginas creadas desde la base de datos de forma paginada.
 *
 * Calcula el total de páginas usando el límite especificado y retorna un array
 * con la clave 'paginas' (los registros) y 'totalPages' (el total de páginas).
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $limit Cantidad de registros por página.
 * @param int $offset Offset para la consulta.
 * @return array Array con las páginas y el total de páginas.
 */
function getPaginas($conn, $limit, $offset) {
    // Contar el total de registros
    $countSql = "SELECT COUNT(*) as total FROM paginas";
    $resultCount = $conn->query($countSql);
    if (!$resultCount) {
        die("Error en la consulta SQL (getPaginas count): " . $conn->error);
    }
    $rowCount = $resultCount->fetch_assoc();
    $totalRows = intval($rowCount['total']);
    $totalPages = ceil($totalRows / $limit);

    // Obtener los registros para la tanda actual, incluyendo el nombre de la página padre
    $sql = "SELECT p.id, p.nombre, p.url, p.pagina_padre_id, pp.nombre as padre_nombre 
            FROM paginas p 
            LEFT JOIN paginas pp ON p.pagina_padre_id = pp.id 
            LIMIT $limit OFFSET $offset";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta SQL (getPaginas): " . $conn->error);
    }
    $paginas = [];
    while ($row = $result->fetch_assoc()) {
        $paginas[] = $row;
    }
    return array('paginas' => $paginas, 'totalPages' => $totalPages);
}


/**
 * Recupera las etiquetas SEO.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @return mysqli_result Resultado de la consulta con las etiquetas.
 */
function getEtiquetas($conn) {
    $sql = "SELECT 
                e.id, 
                e.etiqueta, 
                e.estado,
                ep.pagina_id,
                p.nombre AS pagina_nombre
            FROM etiquetas_seo e
            LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
            LEFT JOIN paginas p ON ep.pagina_id = p.id";
    
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta SQL (getEtiquetas): " . $conn->error);
    }
    return $result;
}


/**
 * Obtiene las páginas para el selector de página principal.
 *
 * Ejecuta la consulta "SELECT id, nombre FROM paginas" y retorna el resultado.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @return mysqli_result Resultado de la consulta.
 */
function getSelectorPaginas($conn) {
    $sql = "SELECT id, nombre FROM paginas";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta SQL (getSelectorPaginas): " . $conn->error);
    }
    return $result;
}

/**
 * Recupera los IDs de las etiquetas asociadas a una página.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $page_id ID de la página.
 * @return array Array de IDs de etiquetas asociados a la página.
 */
function getPageKeywords($conn, $page_id) {
    $tags = [];
    $page_id = intval($page_id);
    if ($page_id > 0) {
        $sql = "SELECT e.id, e.etiqueta 
                FROM etiquetas_seo e 
                INNER JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id 
                WHERE ep.pagina_id = $page_id";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tags[] = $row;
            }
        }
    }
    return $tags;
}


/**
 * Recupera las etiquetas que no tienen página asignada.
 *
 * Realiza un LEFT JOIN entre 'etiquetas_seo' y 'etiquetas_paginas' y retorna solo aquellas
 * etiquetas que no están asociadas a ninguna página.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @return mysqli_result Resultado de la consulta con las etiquetas no asignadas.
 */
function getEtiquetasNoAsignadas($conn) {
    $sql = "SELECT e.id, e.etiqueta, e.estado 
            FROM etiquetas_seo e 
            LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id 
            WHERE ep.etiqueta_id IS NULL";
    $result = $conn->query($sql);
    if (!$result) {
        die("Error en la consulta SQL (getEtiquetasNoAsignadas): " . $conn->error);
    }
    return $result;
}

/* ============================================================
   Procesamiento de Archivos
   ============================================================ */

/**
 * Procesa la importación de un archivo Excel y agrega las etiquetas a la base de datos.
 *
 * @param array $file Información del archivo subido ($_FILES["file"]).
 * @param mysqli $conn Conexión a la base de datos.
 * @return string Mensaje que indica el resultado del procesamiento.
 */
function processExcelFile($file, $conn) {
    // Incluir la librería PhpSpreadsheet
    require 'vendor/autoload.php';
    
    $filename = $file["name"];
    $file_tmp = $file["tmp_name"];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Solo se permiten archivos XLSX
    $allowed_ext = array("xlsx");
    if (!in_array($file_ext, $allowed_ext)) {
        return "Error: Formato de archivo no válido. Solo se permiten archivos XLSX.";
    }

    // Verificar y crear el directorio "uploads" si no existe
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filepath = $uploadDir . $filename;

    // Mover el archivo subido al directorio de uploads
    if (!move_uploaded_file($file_tmp, $filepath)) {
        return "Error al mover el archivo subido.";
    }

    try {
        // Cargar el archivo Excel usando PhpSpreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        // Recorrer cada celda y agregar etiquetas a la base de datos
        foreach ($data as $row) {
            foreach ($row as $cell) {
                $etiqueta = trim($cell);
                if (!empty($etiqueta)) {
                    $sql = "INSERT IGNORE INTO etiquetas_seo (etiqueta) VALUES ('$etiqueta')";
                    $conn->query($sql);
                }
            }
        }
        return "Archivo procesado correctamente.";
    } catch (Exception $e) {
        return "Error al procesar el archivo: " . $e->getMessage();
    }
}

/* ============================================================
   Operaciones DELETE
   ============================================================ */

/**
 * Elimina una página de la base de datos.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $pagina_id ID de la página a eliminar.
 * @return string Mensaje indicando el resultado de la eliminación.
 */
function deletePage($conn, $pagina_id) {
    $pagina_id = intval($pagina_id);
    $sql_delete_page = "DELETE FROM paginas WHERE id = $pagina_id";
    if ($conn->query($sql_delete_page) === TRUE) {
        return "Página eliminada correctamente.";
    } else {
        return "Error al eliminar página: " . $conn->error;
    }
}

/**
 * Elimina una etiqueta de la base de datos.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $etiqueta_id ID de la etiqueta a eliminar.
 * @return string Mensaje indicando el resultado de la eliminación.
 */
function deleteLabel($conn, $etiqueta_id) {
    $etiqueta_id = intval($etiqueta_id);
    $sql_delete_etiqueta = "DELETE FROM etiquetas_seo WHERE id = $etiqueta_id";
    if ($conn->query($sql_delete_etiqueta) === TRUE) {
        return "Etiqueta eliminada correctamente.";
    } else {
        return "Error al eliminar la etiqueta: " . $conn->error;
    }
}

/**
 * Elimina la asociación entre una página y una etiqueta (palabra clave).
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $pagina_id ID de la página.
 * @param int $etiqueta_id ID de la etiqueta.
 * @return string Mensaje indicando el resultado de la eliminación.
 */
function deleteKeywordPage($conn, $pagina_id, $etiqueta_id) {
    $pagina_id = intval($pagina_id);
    $etiqueta_id = intval($etiqueta_id);
    $sql_delete_keyword = "DELETE FROM etiquetas_paginas WHERE pagina_id = $pagina_id AND etiqueta_id = $etiqueta_id";
    if ($conn->query($sql_delete_keyword) === TRUE) {
        return "Palabra clave eliminada correctamente.";
    } else {
        return "Error al eliminar palabra clave: " . $conn->error;
    }
}



/* ============================================================
   Operaciones INSERT (CREATE)
   ============================================================ */

/**
 * Crea una página en la base de datos.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param string $nombre Nombre de la página.
 * @param string $url URL de la página.
 * @param mixed $parent_page_id ID de la página principal (opcional).
 * @return string Mensaje indicando el resultado de la operación.
 */
function createPage($conn, $nombre, $url, $parent_page_id) {
    if (empty($nombre) || empty($url)) {
        return "Error: Nombre, URL y título son campos obligatorios.";
    }
    $nombre = $conn->real_escape_string($nombre);
    $url = $conn->real_escape_string($url);
    $parent_value = !empty($parent_page_id) ? "'" . $conn->real_escape_string($parent_page_id) . "'" : "NULL";
    $sql = "INSERT INTO paginas (nombre, url, pagina_padre_id)
            VALUES ('$nombre', '$url', $parent_value)";
    if ($conn->query($sql) === TRUE) {
        return "Página creada correctamente.";
    } else {
        return "Error: " . $conn->error;
    }
}

/**
 * Crea una etiqueta en la base de datos.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param string $nombre Nombre de la etiqueta.
 * @return string Mensaje que indica el resultado de la operación.
 */
function createLabel($conn, $nombre) {
    if (empty($nombre)) {
        return "Error: El nombre de la etiqueta es obligatorio.";
    }
    $nombre = $conn->real_escape_string($nombre);
    $sql = "INSERT INTO etiquetas_seo (etiqueta) VALUES ('$nombre')";
    if ($conn->query($sql) === TRUE) {
        return "Etiqueta creada correctamente.";
    } else {
        return "Error: " . $conn->error;
    }
}

/**
 * Alterna la asociación entre una página y una etiqueta.
 *
 * Si la asociación ya existe, la elimina; de lo contrario, la inserta.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param int $pagina_id ID de la página.
 * @param int $etiqueta_id ID de la etiqueta.
 * @return string Mensaje indicando el resultado de la operación.
 */
function toggleKeywordPage($conn, $pagina_id, $etiqueta_id) {
    $pagina_id = intval($pagina_id);
    $etiqueta_id = intval($etiqueta_id);
    $check_sql = "SELECT * FROM etiquetas_paginas WHERE pagina_id = $pagina_id AND etiqueta_id = $etiqueta_id";
    $check_result = $conn->query($check_sql);
    if ($check_result && $check_result->num_rows > 0) {
        $delete_sql = "DELETE FROM etiquetas_paginas WHERE pagina_id = $pagina_id AND etiqueta_id = $etiqueta_id";
        if ($conn->query($delete_sql) === TRUE) {
            return "Etiqueta desasociada correctamente";
        } else {
            return "Error al desasociar etiqueta: " . $conn->error;
        }
    } else {
        $insert_sql = "INSERT INTO etiquetas_paginas (pagina_id, etiqueta_id) VALUES ($pagina_id, $etiqueta_id)";
        if ($conn->query($insert_sql) === TRUE) {
            return "Etiqueta añadida correctamente";
        } else {
            return "Error al añadir etiqueta: " . $conn->error;
        }
    }
}

/**
 * Busca páginas cuyo nombre coincida con el término.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param string $termino Término de búsqueda.
 * @return array Array con resultados de páginas.
 */
function buscarPaginas($conn, $termino) {
    $termino = $conn->real_escape_string($termino);
    $sql = "
        SELECT p.id, p.nombre, p.url, pp.nombre AS padre_nombre
        FROM paginas p
        LEFT JOIN paginas pp ON p.pagina_padre_id = pp.id
        WHERE LOWER(p.nombre) LIKE LOWER('%$termino%')
        ORDER BY p.nombre ASC
        LIMIT 100
    ";
    $result = $conn->query($sql);
    $paginas = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $paginas[] = $row;
        }
    }
    return $paginas;
}
function obtener_etiquetas_paginadas($limite, $offset, $mostrar_todas = false) {
    $conn = getDatabaseConnection();

    if ($mostrar_todas) {
        $totalSql = "SELECT COUNT(*) as total FROM etiquetas_seo";
        $sql = "SELECT 
                    e.id, 
                    e.etiqueta, 
                    e.estado,
                    ep.pagina_id,
                    p.nombre AS pagina_nombre
                FROM etiquetas_seo e
                LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
                LEFT JOIN paginas p ON ep.pagina_id = p.id
                ORDER BY e.etiqueta ASC
                LIMIT $limite OFFSET $offset";
    } else {
        $totalSql = "SELECT COUNT(*) as total
                     FROM etiquetas_seo e
                     LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
                     WHERE ep.etiqueta_id IS NULL";

        $sql = "SELECT e.id, e.etiqueta, e.estado
                FROM etiquetas_seo e
                LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
                WHERE ep.etiqueta_id IS NULL
                ORDER BY e.etiqueta ASC
                LIMIT $limite OFFSET $offset";
    }

    $totalResult = $conn->query($totalSql);
    $total = $totalResult->fetch_assoc()['total'];
    $totalPages = ceil($total / $limite);

    $resultado = $conn->query($sql);
    $etiquetas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $etiquetas[] = $fila;
    }

    // También cargamos las páginas para el selector
    $paginas = [];
    $resPaginas = $conn->query("SELECT id, nombre FROM paginas ORDER BY nombre ASC");
    while ($p = $resPaginas->fetch_assoc()) {
        $paginas[] = $p;
    }

    return [
        'etiquetas' => $etiquetas,
        'totalPages' => $totalPages,
        'paginas' => $paginas
    ];
}
function buscarEtiquetas($termino, $mostrar_todas = false) {
    $conn = getDatabaseConnection();
    $termino = $conn->real_escape_string($termino);

    if ($mostrar_todas) {
        $sql = "SELECT 
                    e.id, 
                    e.etiqueta, 
                    e.estado,
                    ep.pagina_id,
                    p.nombre AS pagina_nombre
                FROM etiquetas_seo e
                LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
                LEFT JOIN paginas p ON ep.pagina_id = p.id
                WHERE LOWER(e.etiqueta) LIKE LOWER('%$termino%')
                ORDER BY e.etiqueta ASC
                LIMIT 100";
    } else {
        $sql = "SELECT 
                    e.id, 
                    e.etiqueta, 
                    e.estado
                FROM etiquetas_seo e
                LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
                WHERE ep.etiqueta_id IS NULL
                  AND LOWER(e.etiqueta) LIKE LOWER('%$termino%')
                ORDER BY e.etiqueta ASC
                LIMIT 100";
    }

    $resultado = $conn->query($sql);
    $etiquetas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $etiquetas[] = $fila;
    }

    return ['etiquetas' => $etiquetas];
}



?>
