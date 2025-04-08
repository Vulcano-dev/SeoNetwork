<?php
// etiquetas.php
// Se incluye el menú flotante si es necesario
include 'floating_menu.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Etiquetas</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Importar FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="container">
        <h1>Etiquetas</h1>
        <!-- Botones superiores para abrir modales -->
        <div class="actions">
            <button id="addLabelModalBtn">Añadir Etiqueta</button>
            <button id="importExcelModalBtn">Importar Etiquetas</button>
        </div>
        <!-- Checkbox para alternar entre mostrar sólo etiquetas sin asignar o todas -->
        <div class="filter-options">
            <label>
                <input type="checkbox" id="toggleAllEtiquetas">
                Mostrar todas las etiquetas
            </label>
        </div>
        <!-- Buscador para filtrar etiquetas -->
        <input type="text" id="searchBox" placeholder="Buscar etiquetas...">
        <table>
            <thead>
                <tr>
                    <th>Etiqueta</th>
                    <th>Página</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="etiquetasTable">
                <!-- Las filas se cargarán dinámicamente mediante fetch -->
            </tbody>
        </table>
    </div>

    <!-- Modal para añadir etiqueta -->
    <div id="addLabelModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal(this.parentElement.parentElement)">&times;</span>
            <h2>Añadir Etiqueta</h2>
            <!-- Formulario con action javascript:void(0); para evitar redirección -->
            <form id="addLabelForm" method="post" action="javascript:void(0);">
                <label for="newLabelName">Nombre de la Etiqueta:</label>
                <input type="text" id="newLabelName" name="labelName" class="custom-select" required>
                <button type="submit">Crear</button>
            </form>
        </div>
    </div>

    <!-- Modal para importar etiquetas (desde Excel) -->
    <div id="importExcelModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal(this.parentElement.parentElement)">&times;</span>
            <h2>Importar Etiquetas</h2>
            <!-- Formulario con action javascript:void(0); -->
            <form id="importExcelForm" method="post" action="javascript:void(0);" enctype="multipart/form-data">
                <input type="file" name="file" class="custom-select" required>
                <button type="submit">Importar</button>
            </form>
        </div>
    </div>

    <!-- Modal para editar etiqueta -->
    <div id="editLabelModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal(document.getElementById('editLabelModal'))">&times;</span>
            <h2>Editar Etiqueta</h2>
            <form id="editLabelForm" method="post" action="javascript:void(0);">
                <input type="hidden" id="editLabelId" name="etiqueta_id">
                <label for="editLabelName">Nombre de la Etiqueta:</label>
                <input type="text" id="editLabelName" name="etiqueta" class="custom-select">
                <label for="editLabelPageSearch">Buscar Página:</label>
                <!-- Buscador de páginas para editar etiqueta -->
                <input type="text" id="editLabelPageSearch" class="custom-select" placeholder="Escribe para buscar...">
                <ul id="editLabelPageSuggestions" class="suggestions-list"></ul>
                <!-- Input oculto para almacenar el id de la página seleccionada -->
                <input type="hidden" id="editLabelPages" name="pagina_id">
                <!-- Select de estado; se ocultará si se asigna una página -->
                <label for="editLabelStatus" id="estadoLabel">Estado:</label>
                <select id="editLabelStatus" name="nuevo_estado" class="custom-select">
                    <option value="naranja">Naranja</option>
                    <option value="verde">Verde</option>
                    <option value="rojo">Rojo</option>
                </select>
                <button type="button" id="updateLabelBtn">Actualizar</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <!-- Script extra para interceptar envíos y recargar la página actual -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Interceptar el envío del formulario de Añadir Etiqueta
        const addLabelForm = document.getElementById('addLabelForm');
        if (addLabelForm) {
            addLabelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(addLabelForm);
                fetch('controller.php?action=create_label', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('creada')) {
                        location.reload();
                    } else {
                        console.error('Error al crear etiqueta:', data);
                    }
                })
                .catch(err => console.error(err));
            });
        }
        // Interceptar el envío del formulario de Importar Etiquetas
        const importExcelForm = document.getElementById('importExcelForm');
        if (importExcelForm) {
            importExcelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(importExcelForm);
                fetch('controller.php?action=import_excel', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('procesado')) {
                        location.reload();
                    } else {
                        console.error('Error al importar etiquetas:', data);
                    }
                })
                .catch(err => console.error(err));
            });
        }
    });
    </script>
</body>
</html>
