// ======================================
// Funciones de inicialización y utilidades
// ======================================

/**
 * Muestra un modal.
 * @param {HTMLElement} modal - El elemento modal.
 */
function openModal(modal) {
    modal.style.display = 'block';
}

/**
 * Oculta un modal.
 * @param {HTMLElement} modal - El elemento modal.
 */
function closeModal(modal) {
    modal.style.display = 'none';
}

/**
 * Envía un formulario de forma asíncrona usando fetch.
 * @param {string} formularioId - El ID del formulario.
 * @param {string} url - La URL destino.
 * @param {Function} callback - Función de callback con la respuesta.
 */
function enviarFormulario(formularioId, url, callback) {
    const formulario = document.getElementById(formularioId);
    if (!formulario) return;
    formulario.addEventListener('submit', function(evento) {
        evento.preventDefault();
        const formData = new FormData(formulario);
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
    }, { once: true });
}

/**
 * Actualiza la página completa (para vistas sin paginación).
 */
function actualizarPagina() {
    fetch('index.php')
        .then(response => response.text())
        .then(data => {
            document.body.innerHTML = data;
            inicializarApp();
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Carga datos (etiquetas y páginas) desde el controlador y ejecuta un callback.
 * @param {Function} callback - Función que recibe el objeto de datos.
 */
function loadData(callback) {
    fetch('controller.php?action=get_etiquetas')
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error al cargar datos:', error));
}

// ======================================
// Función para inicializar la vista de Index
// ======================================

function initIndex() {
    const btnAddLabel = document.getElementById('addLabelBtn');
    const btnAddPage = document.getElementById('addPageBtn');
    const btnImportExcel = document.getElementById('importExcelBtn');

    if (btnAddLabel) {
        btnAddLabel.addEventListener('click', () => {
            const modal = document.getElementById('addLabelModal');
            if (modal) openModal(modal);
        });
    }
    if (btnAddPage) {
        btnAddPage.addEventListener('click', () => {
            const modal = document.getElementById('addPageModal');
            if (modal) openModal(modal);
        });
    }
    if (btnImportExcel) {
        btnImportExcel.addEventListener('click', () => {
            const modal = document.getElementById('importExcelModal');
            if (modal) openModal(modal);
        });
    }

    // Cargar el selector de páginas para el modal "Añadir Página"
    fetch('controller.php?action=get_selector_paginas')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('parentPage');
            if (select) {
                select.innerHTML = '<option value="">Ninguna</option>';
                data.forEach(page => {
                    const option = document.createElement('option');
                    option.value = page.id;
                    option.textContent = page.nombre;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error fetching pages:', error));
}

// ======================================
// Funciones específicas para la vista de Páginas
// ======================================

// Variable global para la página actual en la paginación
let currentPage = 1;

function initPaginas() {
    // Obtenemos el <tbody> para no modificar el <thead>
    const tableBody = document.querySelector('#paginasTable tbody');
    const keywordList = document.getElementById('keywordList');
    const paginaIdInput = document.getElementById('paginaId');

    // Cargar datos sin paginación
    loadData(data => {
        if (keywordList) {
            cargarEtiquetas(data.etiquetas, keywordList);
        }
        cargarSelectorPaginas(data.paginas);
    });

    // Cargar la tabla de páginas con paginación (20 registros por tanda)
    loadPaginasTable(currentPage);

    // Botones de paginación
    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentPage > 1) { loadPaginasTable(currentPage - 1); }
    });
    document.getElementById('nextBtn').addEventListener('click', () => {
        loadPaginasTable(currentPage + 1);
    });

    // Función global para recargar la vista de páginas actual
    window.recargarPaginas = function() {
        loadPaginasTable(currentPage);
    };

    function loadPaginasTable(page) {
        fetch('controller.php?action=get_paginas&page=' + page)
            .then(response => response.json())
            .then(data => {
                cargarPaginas(data.paginas, tableBody);
                currentPage = page;
                document.getElementById('prevBtn').disabled = (currentPage <= 1);
                document.getElementById('nextBtn').disabled = (currentPage >= data.totalPages);
            })
            .catch(error => console.error('Error al cargar páginas:', error));
    }
    // Buscador de páginas
const buscadorPaginas = document.getElementById('buscadorPaginas');
if (buscadorPaginas) {
    buscadorPaginas.addEventListener('input', function () {
        const termino = this.value.trim().toLowerCase();
        if (termino.length === 0) {
            // Si está vacío, vuelve a la vista paginada normal
            loadPaginasTable(currentPage);
            return;
        }

        fetch('controller.php?action=buscar_paginas&query=' + encodeURIComponent(termino))
            .then(res => res.json())
            .then(data => {
                cargarPaginas(data, tableBody);
                // Desactivamos los botones de paginación para no romper la UX
                document.getElementById('prevBtn').disabled = true;
                document.getElementById('nextBtn').disabled = true;
            })
            .catch(err => console.error('Error al buscar páginas:', err));
    });
}


    function cargarPaginas(paginas, tbody) {
        tbody.innerHTML = '';
        if (paginas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">No hay páginas creadas.</td></tr>';
            return;
        }
        paginas.forEach(p => {
            const tr = document.createElement('tr');
            tr.id = 'pagina-' + p.id;
            tr.innerHTML = `
                <td>${p.nombre}</td>
                <td>${p.url}</td>
                <td>${p.padre_nombre || ''}</td>
                <td>
                    <div class="action-buttons">
                        <button class="add-keywords-btn btn-action" data-id="${p.id}">
                            <i class="fa fa-tag" style="color:white;"></i>
                        </button>
                        <button class="delete-page-btn btn-action" data-id="${p.id}">
                            <i class="fa fa-trash" style="color:white;"></i>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
        document.querySelectorAll('.add-keywords-btn').forEach(btn =>
            btn.addEventListener('click', () => openKeywordModal(btn.dataset.id))
        );
        document.querySelectorAll('.delete-page-btn').forEach(btn =>
            btn.addEventListener('click', () => deletePage(btn.dataset.id))
        );
    }

    function cargarEtiquetas(etiquetas, listElement) {
        listElement.innerHTML = '';
        etiquetas.forEach(etiqueta => {
            const li = document.createElement('li');
            li.textContent = etiqueta.etiqueta;
            li.dataset.id = etiqueta.id;
            listElement.appendChild(li);
        });
    }

    function cargarSelectorPaginas(paginas) {
        const select = document.getElementById('parentPage');
        if (!select) return;
        select.innerHTML = '<option value="">Ninguna</option>';
        paginas.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = p.nombre;
            select.appendChild(option);
        });
    }

    function openKeywordModal(paginaId) {
        paginaIdInput.value = paginaId;
        const modal = document.getElementById('addKeywordsModal');
        openModal(modal);
        fetch('controller.php?action=get_page_keywords&page_id=' + paginaId)
            .then(res => res.json())
            .then(tags => {
                const modalList = document.getElementById('modalKeywordList');
                modalList.innerHTML = '';
                if (tags.length > 0) {
                    tags.forEach(tag => {
                        const li = document.createElement('li');
                        li.textContent = tag.etiqueta;
                        modalList.appendChild(li);
                    });
                } else {
                    modalList.innerHTML = '<li>No hay etiquetas asignadas.</li>';
                }
            })
            .catch(err => console.error('Error al cargar etiquetas de la página:', err));
    }

    function deletePage(id) {
        fetch('controller.php?action=delete_page', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'pagina_id=' + id
        })
        .then(res => res.text())
        .then(msg => {
            if (msg.includes('eliminada')) {
                const row = document.getElementById('pagina-' + id);
                if (row) row.remove();
            } else {
                console.error(msg);
            }
        });
    }

    // Enviar formulario de creación de página y recargar la vista actual sin cambiar la URL
    const addPageForm = document.getElementById('addPageForm');
    if (addPageForm) {
        addPageForm.addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('controller.php?action=create_page', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                if (msg.includes('creada')) {
                    if (typeof recargarPaginas === "function") {
                        recargarPaginas();
                    } else {
                        actualizarPagina();
                    }
                } else {
                    console.error(msg);
                }
            });
        });
    }
}

// ======================================
// Funciones específicas para la vista de Etiquetas
// ======================================

function initEtiquetas() {
   let currentEtiquetaPage = 1;

function loadEtiquetasData(page = 1) {
    currentEtiquetaPage = page;
    const allCheckbox = document.getElementById('toggleAllEtiquetas');
    const mostrarTodas = allCheckbox && allCheckbox.checked ? '&all=1' : '';
    fetch(`controller.php?action=get_etiquetas_paginadas&page=${page}${mostrarTodas}`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('etiquetasTable');
            tableBody.innerHTML = '';

            if (data.etiquetas.length > 0) {
                data.etiquetas.forEach(etiqueta => {
                    let color_estado = (etiqueta.estado === 'verde') ? 'green' :
                        (etiqueta.estado === 'rojo') ? 'red' : 'orange';
                    const tr = document.createElement('tr');
                    tr.id = 'etiqueta-' + etiqueta.id;
                    tr.innerHTML = `
                        <td><span style="color:${color_estado};">&#x25C8;</span> ${etiqueta.etiqueta}</td>
                        <td>${etiqueta.pagina_nombre || ''}</td>
                        <td>
                            <button class="edit-label-btn" data-id="${etiqueta.id}" data-etiqueta="${etiqueta.etiqueta}" data-estado="${etiqueta.estado}">Editar</button>
                            <button class="delete-label-btn" data-id="${etiqueta.id}"><i class="fa fa-trash" style="color:white;"></i></button>
                        </td>`;
                    tableBody.appendChild(tr);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="3">No hay etiquetas.</td></tr>';
            }

            // Actualizar la paginación
            renderEtiquetaPagination(data.totalPages);
         })
        .catch(error => console.error('Error al cargar etiquetas:', error));
window.recargarEtiquetas = function () {
    loadEtiquetasData(currentEtiquetaPage);
};

}

function renderEtiquetaPagination(totalPages) {
    const container = document.getElementById('etiquetaPagination');
    if (!container) return;

    container.innerHTML = `
        <button id="prevEtiquetaPage" ${currentEtiquetaPage === 1 ? 'disabled' : ''}>Anterior</button>
        <span>Página ${currentEtiquetaPage} de ${totalPages}</span>
        <button id="nextEtiquetaPage" ${currentEtiquetaPage === totalPages ? 'disabled' : ''}>Siguiente</button>
    `;

    document.getElementById('prevEtiquetaPage').addEventListener('click', () => {
        if (currentEtiquetaPage > 1) loadEtiquetasData(currentEtiquetaPage - 1);
    });
    document.getElementById('nextEtiquetaPage').addEventListener('click', () => {
        if (currentEtiquetaPage < totalPages) loadEtiquetasData(currentEtiquetaPage + 1);
    });
}

    loadEtiquetasData();

    // Listener para el checkbox con persistencia (usando localStorage)
    const toggleAll = document.getElementById('toggleAllEtiquetas');
    if (toggleAll) {
        // Al cargar la página, si hay un estado almacenado, se aplica
        const stored = localStorage.getItem('toggleAllEtiquetas');
        if (stored !== null) {
    toggleAll.checked = stored === 'true';
}
loadEtiquetasData(); // Asegura que se carguen las etiquetas según el valor restaurado

        toggleAll.addEventListener('change', function() {
            localStorage.setItem('toggleAllEtiquetas', this.checked);
            loadEtiquetasData();
        });
    }

    // Buscador de etiquetas (filtra la tabla de etiquetas)
    const searchBox = document.getElementById('searchBox');
    if (searchBox) {
       searchBox.addEventListener('input', function () {
    const filtro = this.value.trim().toLowerCase();
    const tableBody = document.getElementById('etiquetasTable');

    if (filtro === '') {
        loadEtiquetasData(currentEtiquetaPage);
        return;
    }

    const mostrarTodas = (document.getElementById('toggleAllEtiquetas')?.checked) ? '&all=1' : '';

    fetch('controller.php?action=buscar_etiquetas&query=' + encodeURIComponent(filtro) + mostrarTodas)
        .then(response => response.json())
        .then(data => {
            tableBody.innerHTML = '';
            if (data.etiquetas.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3">No se encontraron coincidencias.</td></tr>';
                return;
            }

            data.etiquetas.forEach(etiqueta => {
                let color_estado = (etiqueta.estado === 'verde') ? 'green' :
                    (etiqueta.estado === 'rojo') ? 'red' : 'orange';

                const tr = document.createElement('tr');
                tr.id = 'etiqueta-' + etiqueta.id;
                tr.innerHTML = `
                    <td><span style="color:${color_estado};">&#x25C8;</span> ${etiqueta.etiqueta}</td>
                    <td>${etiqueta.pagina_nombre || ''}</td>
                    <td>
                        <button class="edit-label-btn" data-id="${etiqueta.id}" data-etiqueta="${etiqueta.etiqueta}" data-estado="${etiqueta.estado}">Editar</button>
                        <button class="delete-label-btn" data-id="${etiqueta.id}"><i class="fa fa-trash" style="color:white;"></i></button>
                    </td>`;
                tableBody.appendChild(tr);
            });

            // Quitar paginación
            const paginacion = document.getElementById('etiquetaPagination');
            if (paginacion) paginacion.innerHTML = '';
        })
        .catch(error => console.error('Error al buscar etiquetas:', error));
});


    }

    // Delegación de eventos para editar y eliminar etiquetas
    const etiquetasTable = document.getElementById('etiquetasTable');
    if (etiquetasTable) {
        etiquetasTable.addEventListener('click', function(e) {
            if (e.target.closest('.edit-label-btn')) {
                const btn = e.target.closest('.edit-label-btn');
                const id = btn.dataset.id;
                const texto = btn.dataset.etiqueta;
                const estado = btn.dataset.estado;
                document.getElementById('editLabelId').value = id;
                document.getElementById('editLabelName').value = texto;
                document.getElementById('editLabelStatus').value = estado;
                document.getElementById('editLabelPages').value = "";
                document.getElementById('editLabelStatus').style.display = 'block';
                document.getElementById('estadoLabel').style.display = 'block';
                openModal(document.getElementById('editLabelModal'));
            }
            if (e.target.closest('.delete-label-btn')) {
                const btn = e.target.closest('.delete-label-btn');
                const id = btn.dataset.id;
                fetch('controller.php?action=delete_label', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'etiqueta_id=' + id,
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('eliminada correctamente')) {
                        document.getElementById('etiqueta-' + id).remove();
                    } else {
                        console.error('Error al eliminar la etiqueta:', data);
                    }
                })
                .catch(err => console.error('Error:', err));
            }
        });
    }

    // Listener para el select del modal de edición
    const editLabelPages = document.getElementById('editLabelPages');
    const editLabelStatus = document.getElementById('editLabelStatus');
    const estadoLabel = document.getElementById('estadoLabel');
    if (editLabelPages) {
        editLabelPages.addEventListener('change', function() {
            if (this.value !== "") {
                editLabelStatus.value = 'verde';
                editLabelStatus.style.display = 'none';
                estadoLabel.style.display = 'none';
            } else {
                editLabelStatus.style.display = 'block';
                estadoLabel.style.display = 'block';
            }
        });
    }

    // Actualizar etiqueta mediante fetch
    const updateLabelBtn = document.getElementById('updateLabelBtn');
if (updateLabelBtn) {
    updateLabelBtn.addEventListener('click', function() {
        const etiquetaId = document.getElementById('editLabelId').value;
        const etiquetaTexto = document.getElementById('editLabelName').value;
        const etiquetaEstado = document.getElementById('editLabelStatus').value;
        const paginaId = document.getElementById('editLabelPages').value;
        // Obtenemos el nombre de la página desde el input de búsqueda
        const pageSearch = document.getElementById('editLabelPageSearch');
        let paginaTexto = "";
        if (pageSearch && pageSearch.value.trim() !== "") {
            paginaTexto = pageSearch.value;
        }
        fetch('controller.php?action=update_label', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `etiqueta_id=${etiquetaId}&etiqueta=${encodeURIComponent(etiquetaTexto)}&nuevo_estado=${etiquetaEstado}&pagina_id=${paginaId}`
        })
        .then(response => response.text())
        .then(data => {
    if (data.includes('actualizada correctamente')) {
        const colorEstado = (etiquetaEstado === 'verde') ? 'green'
                               : ((etiquetaEstado === 'rojo') ? 'red' : 'orange');
        const fila = document.getElementById('etiqueta-' + etiquetaId);
        fila.innerHTML = `
            <td><span style="color:${colorEstado};">&#x25C8;</span> ${etiquetaTexto}</td>
            <td>${paginaTexto}</td>
            <td>
                <button class="edit-label-btn" data-id="${etiquetaId}" data-etiqueta="${etiquetaTexto}" data-estado="${etiquetaEstado}">Editar</button>
                <button class="delete-label-btn" data-id="${etiquetaId}"><i class="fa fa-trash" style="color:white;"></i></button>
            </td>
        `;
        closeModal(document.getElementById('editLabelModal'));
        location.reload(); // ← recarga toda la página
    } else {
        console.error('Error al actualizar etiqueta:', data);
    }
})

        .catch(err => console.error('Error:', err));
    });
}

}

// ======================================
// Función para inicializar el Menú Flotante
// ======================================

function initFloatingMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const menuItems = document.getElementById('menuItems');
    if (menuToggle && menuItems) {
        menuToggle.addEventListener('click', () => {
            menuItems.classList.toggle('active');
        });
    }
}

// ======================================
// Inicialización de modales en etiquetas.php
// ======================================

function initEtiquetasModales() {
    const addLabelModalBtn = document.getElementById('addLabelModalBtn');
    if (addLabelModalBtn) {
        addLabelModalBtn.addEventListener('click', () => {
            const modal = document.getElementById('addLabelModal');
            if (modal) openModal(modal);
        });
    }
    const importExcelModalBtn = document.getElementById('importExcelModalBtn');
    if (importExcelModalBtn) {
        importExcelModalBtn.addEventListener('click', () => {
            const modal = document.getElementById('importExcelModal');
            if (modal) openModal(modal);
        });
    }
    // Evitar que el modal de editar etiqueta se cierre con la cruz
    const editLabelClose = document.querySelector('#editLabelModal .close');
    if (editLabelClose) {
      editLabelClose.addEventListener('click', function() {
    closeModal(document.getElementById('editLabelModal'));
});
const pageSearch = document.getElementById('editLabelPageSearch');
const pageSuggestions = document.getElementById('editLabelPageSuggestions');
const hiddenPageInput = document.getElementById('editLabelPages');

let paginasDisponibles = [];

fetch('controller.php?action=get_selector_paginas')
    .then(res => res.json())
    .then(data => {
        paginasDisponibles = data;
    });

pageSearch.addEventListener('input', function () {
    const term = this.value.toLowerCase();
    pageSuggestions.innerHTML = '';
    if (!term || !paginasDisponibles) return;

    const filtradas = paginasDisponibles.filter(p =>
        p.nombre.toLowerCase().includes(term)
    );

    filtradas.forEach(p => {
        const li = document.createElement('li');
        li.textContent = p.nombre;
        li.dataset.id = p.id;
        pageSuggestions.appendChild(li);
    });
});

pageSuggestions.addEventListener('click', function (e) {
    if (e.target.tagName.toLowerCase() === 'li') {
        const selectedText = e.target.textContent;
        const selectedId = e.target.dataset.id;
        pageSearch.value = selectedText;
        hiddenPageInput.value = selectedId;
        pageSuggestions.innerHTML = '';
    }
});


    }
    // Configurar buscador sencillo en el modal de añadir etiqueta:
    // Se espera que en el HTML del modal exista un input con id "newLabelSearch"
    // y un <ul> con id "newLabelSuggestions" para mostrar sugerencias.
    const newLabelSearch = document.getElementById('newLabelSearch');
    const newLabelSuggestions = document.getElementById('newLabelSuggestions');
    if (newLabelSearch && newLabelSuggestions) {
        // Cargar la lista completa de etiquetas disponibles (usando all=1)
        fetch('controller.php?action=get_etiquetas&all=1')
            .then(response => response.json())
            .then(data => {
                window.listaEtiquetas = data.etiquetas || [];
                mostrarSugerencias(newLabelSearch.value);
            });
        newLabelSearch.addEventListener('input', function() {
            mostrarSugerencias(this.value);
        });
        // Al hacer clic en una sugerencia, se completa el input "newLabelName"
        newLabelSuggestions.addEventListener('click', function(e) {
            if (e.target.tagName.toLowerCase() === 'li') {
                const selected = e.target.textContent;
                document.getElementById('newLabelName').value = selected;
                newLabelSuggestions.innerHTML = '';
            }
        });
    }
    function mostrarSugerencias(query) {
        newLabelSuggestions.innerHTML = '';
        if (!window.listaEtiquetas) return;
        const filtradas = window.listaEtiquetas.filter(et => et.etiqueta.toLowerCase().includes(query.toLowerCase()));
        filtradas.forEach(et => {
            const li = document.createElement('li');
            li.textContent = et.etiqueta;
            newLabelSuggestions.appendChild(li);
        });
    }
}

// ======================================
// Función para inicializar toda la aplicación
// ======================================

function inicializarApp() {
    // Inicializar formularios asíncronos
    if (document.getElementById('addLabelForm')) {
    enviarFormulario('addLabelForm', 'controller.php?action=create_label', data => {
        if (data.includes('creada correctamente')) {
            closeModal(document.getElementById('addLabelModal'));
            if (typeof recargarEtiquetas === "function") {
                recargarEtiquetas();
            } else {
                actualizarPagina();
            }
        } else {
            console.error('Error al crear etiqueta:', data);
        }
    });
}

    if (document.getElementById('addPageForm')) {
        enviarFormulario('addPageForm', 'controller.php?action=create_page', data => {
            if (data.includes('creada')) {
                if (typeof recargarPaginas === "function") {
                    recargarPaginas();
                } else {
                    actualizarPagina();
                }
            } else {
                console.error('Error al crear página:', data);
            }
        });
    }

    // Inicializar vistas específicas según existan
    if (document.getElementById('paginasTable')) {
        initPaginas();
    }
    if (document.getElementById('etiquetasTable')) {
        initEtiquetas();
    }
    // Inicializar modales específicos de etiquetas.php
    initEtiquetasModales();

    // Inicializar el menú flotante
    initFloatingMenu();

    // Inicializar eventos de la vista index (botones de acción superiores)
    if (document.getElementById('addLabelBtn') || document.getElementById('addPageBtn') || document.getElementById('importExcelBtn')) {
        initIndex();
    }
}

// ======================================
// Inicialización general
// ======================================

document.addEventListener('DOMContentLoaded', inicializarApp);
