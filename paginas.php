<?php
// etiquetas.php
// Se incluye el menú flotante si es necesario
include 'floating_menu.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Páginas Creadas</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome sin integridad para evitar el error de 'digest' -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Páginas Creadas</h1>
        <input type="text" id="buscadorPaginas" placeholder="Buscar páginas..." />

        <table id="paginasTable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>URL</th>
                    <th>Página Padre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Aquí se cargarán las páginas vía fetch -->
            </tbody>
        </table>

        <!-- Controles de paginación -->
        <div class="pagination">
            <button id="prevBtn">Anterior</button>
            <button id="nextBtn">Siguiente</button>
        </div>

        <!-- Elementos adicionales requeridos por script.js -->
        <ul id="keywordList" style="display: none;"></ul>
        <input type="hidden" id="paginaId" value="">
    </div>

    <!-- Modal para ver/gestionar etiquetas de la página -->
    <div id="addKeywordsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal(document.getElementById('addKeywordsModal'))">&times;</span>
            <h2>Etiquetas de la Página</h2>
            <!-- Aquí se cargará la lista de etiquetas asignadas a la página -->
            <ul id="modalKeywordList"></ul>
            <button onclick="closeModal(document.getElementById('addKeywordsModal'))">Cerrar</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
