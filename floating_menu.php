<?php
// floating_menu.php
?>
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<!-- Menú flotante -->
<div class="floating-menu">
    <div class="floating-menu-items" id="menuItems" style="display: none;">
        <div class="floating-menu-item">
            <a href="#" id="menuAddLabel" title="Añadir Etiqueta">
                <span class="menu-icon"><i class="fa fa-tag"></i></span>
                <span class="menu-label">Añadir Etiqueta</span>
            </a>
        </div>
        <div class="floating-menu-item">
            <a href="#" id="menuAddPage" title="Añadir Página">
                <span class="menu-icon"><i class="fa fa-file-text"></i></span>
                <span class="menu-label">Añadir Página</span>
            </a>
        </div>
        <div class="floating-menu-item">
            <a href="#" id="menuImportExcel" title="Importar Excel">
                <span class="menu-icon"><i class="fa fa-file-excel-o"></i></span>
                <span class="menu-label">Importar Excel</span>
            </a>
        </div>
       
        <div class="floating-menu-item">
            <a href="etiquetas.php" title="Ver Etiquetas">
                <span class="menu-icon"><i class="fa fa-list"></i></span>
                <span class="menu-label">Ver Etiquetas</span>
            </a>
        </div>
        <div class="floating-menu-item">
            <a href="paginas.php" title="Ver Páginas Creadas">
                <span class="menu-icon"><i class="fa fa-folder-open"></i></span>
                <span class="menu-label">Ver Páginas Creadas</span>
            </a>
        </div>
    </div>
    <button class="menu-toggle" id="menuToggle">
        <i class="fa fa-bars"></i>
    </button>
</div>

<!-- Botón atrás -->
<div class="back-button">
    <a href="javascript:history.back()" title="Volver Atrás">
        <i class="fa fa-arrow-left"></i>
    </a>
</div>

<!-- Modal Etiqueta -->
<div id="addLabelModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(this.parentElement.parentElement)">&times;</span>
        <form id="addLabelForm" method="post" action="javascript:void(0);">
            <label for="labelName">Nombre de la Etiqueta:</label>
            <input type="text" id="labelName" name="labelName" required>
            <button type="submit">Crear</button>
        </form>
    </div>
</div>

<!-- Modal Página -->
<div id="addPageModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(this.parentElement.parentElement)">&times;</span>
        <form id="addPageForm" method="post" action="javascript:void(0);">
            <label for="pageName">Nombre de la Página:</label>
            <input type="text" id="pageName" name="pageName" required>
            <label for="pageUrl">URL:</label>
            <input type="text" id="pageUrl" name="pageUrl">

            <label for="parentPage">Página Principal:</label>
            <select id="parentPage" name="parentPage">
                <option value="">Ninguna</option>
            </select>
            <button type="submit">Crear</button>
        </form>
    </div>
</div>

<!-- Modal Importar Excel -->
<div id="importExcelModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(this.parentElement.parentElement)">&times;</span>
        <form id="importExcelForm" method="post" action="controller.php?action=import_excel" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Importar</button>
        </form>
    </div>
</div>

<!-- Script -->
<script>
function closeModal(modal) {
    modal.style.display = 'none';
}

function enviarFormulario(formularioId, url, callback) {
    const formulario = document.getElementById(formularioId);
    if (!formulario || formulario.dataset.submitted === "true") return;
    formulario.addEventListener('submit', function(evento) {
        evento.preventDefault();
        const formData = new FormData(formulario);
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => callback(data))
        .catch(err => console.error('Error:', err));
    }, { once: true });
    formulario.dataset.submitted = "true";
}

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const menuItems = document.getElementById('menuItems');
    menuToggle.addEventListener('click', (e) => {
        e.preventDefault();
        menuItems.style.display = (menuItems.style.display === 'flex') ? 'none' : 'flex';
    });

    // Añadir etiqueta: Al crear la etiqueta se recarga la página actual
    const btnAddLabel = document.getElementById('menuAddLabel');
    if (btnAddLabel) {
    btnAddLabel.addEventListener('click', (e) => {
        e.preventDefault();
        const modal = document.getElementById('addLabelModal');
        if (modal) {
            modal.style.display = 'block';
            enviarFormulario('addLabelForm', 'controller.php?action=create_label', data => {
                if (data.includes('creada')) {
                    modal.style.display = 'none';
                    const form = document.getElementById('addLabelForm');
                    if (form) form.reset();

                    // Detectar si estamos en etiquetas.php y usar recarga inteligente
                    if (typeof recargarEtiquetas === "function") {
                        recargarEtiquetas();
                    } else {
                        location.reload(); // fallback si estamos en otra página
                    }
                } else {
                    console.error('Error al crear etiqueta:', data);
                }
            });
        }
    });
}


    // Añadir página
    const btnAddPage = document.getElementById('menuAddPage');
if (btnAddPage) {
    btnAddPage.addEventListener('click', async (e) => {
        e.preventDefault();
        const modal = document.getElementById('addPageModal');
        if (!modal) return;

        modal.style.display = 'block';

        // Cargar selector de páginas solo una vez
        const select = document.getElementById('parentPage');
        if (select && !select.dataset.loaded) {
            const res = await fetch('controller.php?action=get_selector_paginas');
            const paginas = await res.json();
            select.innerHTML = '<option value="">Ninguna</option>';
            paginas.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.nombre;
                select.appendChild(opt);
            });
            select.dataset.loaded = "true";
        }

        enviarFormulario('addPageForm', 'controller.php?action=create_page', data => {
            if (data.includes('creada')) {
                modal.style.display = 'none';
                document.getElementById('addPageForm')?.reset();

                // Si estamos en paginas.php
                if (typeof recargarPaginas === "function") {
                    recargarPaginas();
                } else {
                    location.reload(); // fallback
                }
            } else {
                console.error('Error al crear página:', data);
            }
        });
    });
}


    // Importar Excel
    const btnImportExcel = document.getElementById('menuImportExcel');
    if (btnImportExcel) {
        btnImportExcel.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = document.getElementById('importExcelModal');
            if (modal) {
                modal.style.display = 'block';
            }
        });
    }
});
</script>
