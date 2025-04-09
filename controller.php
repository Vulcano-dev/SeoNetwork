<?php
include 'functions.php';

$conn = getDatabaseConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    // ---------------------------
    // Operaciones POST
    // ---------------------------
    case 'add_keyword_page':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pagina_id = $_POST['pagina_id'];
            $etiqueta_id = $_POST['etiqueta_id'];
            echo toggleKeywordPage($conn, $pagina_id, $etiqueta_id);
        }
        break;

    case 'create_label':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nombre = isset($_POST["labelName"]) ? trim($_POST["labelName"]) : '';
            if (empty($nombre)) {
                echo "Error: El nombre de la etiqueta es obligatorio.";
                break;
            }
            echo createLabel($conn, $nombre);
        }
        break;

   case 'create_page':
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = $_POST["pageName"] ?? '';
        $url = $_POST["pageUrl"] ?? '';
        $parent_page_id = $_POST["parentPage"] ?? '';
        echo createPage($conn, $nombre, $url, $parent_page_id);
        exit;
    }
    break;
    case 'buscar_etiquetas':
    $query = isset($_GET['query']) ? $_GET['query'] : '';
    $mostrar_todas = isset($_GET['all']) && $_GET['all'] == 1;
    $resultado = buscarEtiquetas($query, $mostrar_todas);
    header('Content-Type: application/json');
    echo json_encode($resultado);
    break;


    case 'get_etiquetas_paginadas':
    $pagina = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $mostrar_todas = isset($_GET['all']) && $_GET['all'] == 1;
    $limite = 20;
    $offset = ($pagina - 1) * $limite;
    $resultado = obtener_etiquetas_paginadas($limite, $offset, $mostrar_todas);
    header('Content-Type: application/json');
    echo json_encode($resultado);
    break;

case 'buscar_paginas':
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        header('Content-Type: application/json');
        $termino = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';
        echo json_encode(buscarPaginas($conn, $termino));
        exit;
    }
    break;


    case 'delete_keyword_page':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $pagina_id = $_POST["pagina_id"];
            $etiqueta_id = $_POST["etiqueta_id"];
            echo deleteKeywordPage($conn, $pagina_id, $etiqueta_id);
        }
        break;

    case 'delete_label':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $etiqueta_id = $_POST["etiqueta_id"];
            echo deleteLabel($conn, $etiqueta_id);
        }
        break;

    case 'delete_page':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $pagina_id = $_POST["pagina_id"];
            echo deletePage($conn, $pagina_id);
        }
        break;
        
    case 'import_excel':
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
            echo processExcelFile($_FILES["file"], $conn);
        }
        break;

    case 'update_label':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $etiqueta_id = $_POST['etiqueta_id'] ?? '';
            $etiqueta = $_POST['etiqueta'] ?? '';
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
            $pagina_id = $_POST['pagina_id'] ?? '';

            if (!empty($pagina_id)) {
                $nuevo_estado = 'verde';
            }

            $etiqueta = $conn->real_escape_string($etiqueta);
            $nuevo_estado = $conn->real_escape_string($nuevo_estado);
            $pagina_id = $conn->real_escape_string($pagina_id);
            $etiqueta_id = $conn->real_escape_string($etiqueta_id);

            $sql_update_etiqueta = "UPDATE etiquetas_seo SET etiqueta = '$etiqueta', estado = '$nuevo_estado' WHERE id = '$etiqueta_id'";
            if (!$conn->query($sql_update_etiqueta)) {
                echo "Error al actualizar etiqueta: " . $conn->error;
                exit;
            }

            $sql_delete_asociaciones = "DELETE FROM etiquetas_paginas WHERE etiqueta_id = '$etiqueta_id'";
            $conn->query($sql_delete_asociaciones);

            if (!empty($pagina_id)) {
                $sql_insert_asociacion = "INSERT INTO etiquetas_paginas (etiqueta_id, pagina_id) VALUES ('$etiqueta_id', '$pagina_id')";
                if (!$conn->query($sql_insert_asociacion)) {
                    echo "Error al actualizar asociación: " . $conn->error;
                    exit;
                }
            }
            echo "Etiqueta actualizada correctamente.";
        }
        break;
        
    // ---------------------------
    // Operaciones GET para devolver datos (para fetch)
    // ---------------------------
    case 'get_paginas':
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            header('Content-Type: application/json');
            $page = isset($_GET["page"]) ? intval($_GET["page"]) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            echo json_encode(getPaginas($conn, $limit, $offset));
            exit;
        }
        break;

    case 'get_page_keywords':
    case 'get_page_keyword':
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            header('Content-Type: application/json');
            $page_id = isset($_GET["page_id"]) ? intval($_GET["page_id"]) : 0;
            echo json_encode(getPageKeywords($conn, $page_id));
        }
        break;

    case 'get_etiquetas_no_asignadas':
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            header('Content-Type: application/json');
            $etiquetas = [];
            $result = getEtiquetasNoAsignadas($conn);
            while ($row = $result->fetch_assoc()) {
                $etiquetas[] = $row;
            }
            echo json_encode($etiquetas);
        }
        break;

    case 'get_selector_paginas':
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            header('Content-Type: application/json');
            $paginas = [];
            $result = getSelectorPaginas($conn);
            while ($row = $result->fetch_assoc()) {
                $paginas[] = $row;
            }
            echo json_encode($paginas);
        }
        break;

    case 'get_etiquetas':
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        header('Content-Type: application/json');
        $etiquetas = [];
        $paginas = [];

        if (isset($_GET['all']) && $_GET['all'] == '1') {
            // Cargar TODAS las etiquetas, estén o no asociadas
            $result_etiquetas = $conn->query("
                SELECT e.id, e.etiqueta, e.estado, p.nombre AS pagina_nombre
                FROM etiquetas_seo e
                LEFT JOIN etiquetas_paginas ep ON e.id = ep.etiqueta_id
                LEFT JOIN paginas p ON ep.pagina_id = p.id
            ");
        } else {
            // Solo etiquetas NO asignadas
            $result_etiquetas = getEtiquetasNoAsignadas($conn);
        }

        while ($row = $result_etiquetas->fetch_assoc()) {
            $etiquetas[] = $row;
        }

        $result_paginas = getSelectorPaginas($conn);
        while ($row = $result_paginas->fetch_assoc()) {
            $paginas[] = $row;
        }

        echo json_encode([
            "etiquetas" => $etiquetas,
            "paginas"   => $paginas
        ]);
    }
    break;


    // ---------------------------
    // Casos para generar HTML de modales (vistas parciales)
    // ---------------------------
    case 'modal_import_excel':
        echo '<div id="importExcelModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Importar Excel</h2>
                    <form id="importExcelForm" action="controller.php?action=import_excel" method="post" enctype="multipart/form-data">
                        <input type="file" name="file" class="custom-select">
                        <br><br>
                        <input type="submit" value="Cargar Archivo">
                    </form>
                </div>
              </div>';
        break;

    case 'modal_add_label':
        echo '<div id="addLabelModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Añadir Etiqueta</h2>
                    <form id="addLabelForm" method="post" action="javascript:void(0);">
                        <label for="labelName">Nombre de la Etiqueta:</label>
                        <input type="text" id="labelName" name="labelName" class="custom-select" required>
                        <button type="submit">Crear</button>
                    </form>
                </div>
              </div>';
        break;

    case 'modal_add_page':
        echo '<div id="addPageModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Añadir Página</h2>
                    <form id="addPageForm" method="post" action="javascript:void(0);">
                        <label for="pageName">Nombre de la Página:</label>
                        <input type="text" id="pageName" name="pageName" class="custom-select">
                        <label for="pageUrl">URL de la Página:</label>
                        <input type="text" id="pageUrl" name="pageUrl" class="custom-select">
                        <label for="parentPage">Página Principal:</label>
                        <select id="parentPage" name="parentPage" class="custom-select">
                            <option value="">Ninguna</option>
                        </select>
                        <button type="submit">Crear</button>
                    </form>
                </div>
              </div>';
        break;

    case 'modal_edit_label':
        echo '<div id="editLabelModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Editar Etiqueta</h2>
                    <form id="editLabelForm" method="post">
                        <input type="hidden" id="editLabelId" name="etiqueta_id">
                        <label for="editLabelName">Nombre de la Etiqueta:</label>
                        <input type="text" id="editLabelName" name="etiqueta" class="custom-select">
                        <label for="editLabelPages">Páginas:</label>
                        <select id="editLabelPages" name="pagina_id" class="custom-select">
                            <option value="">(Sin página)</option>
                        </select>
                        <label for="editLabelStatus" id="estadoLabel">Estado:</label>
                        <select id="editLabelStatus" name="nuevo_estado" class="custom-select">
                            <option value="naranja">Naranja</option>
                            <option value="verde">Verde</option>
                            <option value="rojo">Rojo</option>
                        </select>
                        <button type="button" id="updateLabelBtn">Actualizar</button>
                    </form>
                </div>
              </div>';
        break;

    default:
        echo "Acción no especificada o no soportada.";
}

$conn->close();
?>
