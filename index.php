<?php include 'floating_menu.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>SeoNetwork</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Importar FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="container">
        <h1>SeoNetwork</h1>
        <!-- Botones de acción superiores -->
        <div class="actions">
            <button id="addLabelBtn">Añadir Etiqueta</button>
            <button id="addPageBtn">Añadir Página</button>
            <button id="importExcelBtn">Importar Excel</button>
        </div>
        <!-- Botones neumórficos principales -->
        <div class="neumorphism-container">
            <a href="paginas.php" class="depth-button">
                <i class="fa fa-folder-open"></i>
                <span>Ver Páginas Creadas</span>
            </a>
            
            <a href="etiquetas.php" class="depth-button">
                <i class="fa fa-list"></i>
                <span>Ver Etiquetas</span>
            </a>
        </div>
        <!-- Modal Añadir Etiqueta -->
        <div id="addLabelModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Añadir Etiqueta</h2>
                <form id="addLabelForm">
                    <label for="labelName">Nombre de la Etiqueta:</label>
                    <input type="text" id="labelName" name="labelName">
                    <button type="submit">Crear</button>
                </form>
            </div>
        </div>
        <!-- Modal Añadir Página -->
        <div id="addPageModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Añadir Página</h2>
                <form id="addPageForm" method="post" action="javascript:void(0);">

                    <label for="pageName">Nombre de la Página:</label>
                    <input type="text" id="pageName" name="pageName">
                    <label for="pageUrl">URL de la Página:</label>
                    <input type="text" id="pageUrl" name="pageUrl">
                    <label for="pageTitle">Título de la Página:</label>
                    <input type="text" id="pageTitle" name="pageTitle">
                    <label for="parentPage">Página Principal:</label>
                    <select id="parentPage" name="parentPage">
                        <option value="">Ninguna</option>
                        <!-- Las opciones se cargarán vía fetch desde controller.php -->
                    </select>
                    <button type="submit">Crear</button>
                </form>
            </div>
        </div>
        <!-- Modal Importar Excel -->
        <div id="importExcelModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Importar Excel</h2>
                <form id="importExcelForm" action="controller.php?action=import_excel" method="post" enctype="multipart/form-data">
                    <input type="file" name="file">
                    <br><br>
                    <input type="submit" value="Cargar Archivo">
                </form>
            </div>
        </div>
    </div>

    <script>
    // Al cargar el DOM, se solicita el selector de páginas para el modal "Añadir Página"
    document.addEventListener('DOMContentLoaded', function() {
        fetch('controller.php?action=get_selector_paginas')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('parentPage');
                data.forEach(page => {
                    const option = document.createElement('option');
                    option.value = page.id;
                    option.textContent = page.nombre;
                    select.appendChild(option);
                });
            })
            .catch(error => console.error('Error fetching pages:', error));
    });
    </script>
    <script src="script.js"></script>
</body>
</html>
