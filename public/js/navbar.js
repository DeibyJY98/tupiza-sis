// === Filtro genérico de tablas (búsqueda + estado + rango de fechas) ===
// Cada vista con filtros marca sus <tr> con data-filtro-texto y data-filtro-estado
// (y data-filtro-fecha cuando aplica) para que estas dos funciones los filtren en el
// cliente. Los inputs de filtro son opcionales: si una vista no tiene fecha_inicio,
// fecha_fin o estado, esa parte del filtro simplemente se ignora.
// El resultado del filtro se guarda en data-filtro-oculto (en vez de tocar
// style.display directamente) porque la paginación de más abajo también decide
// qué filas se muestran; combinar ambas cosas sobre el mismo atributo evitaría
// que una pisotee a la otra.
function buscar() {
    const texto = (document.getElementById('busqueda')?.value || '').trim().toLowerCase();
    const estado = document.getElementById('estado')?.value ?? '';
    const fechaInicio = document.getElementById('fecha_inicio')?.value ?? '';
    const fechaFin = document.getElementById('fecha_fin')?.value ?? '';

    document.querySelectorAll('table tbody tr[data-filtro-texto]').forEach(fila => {
        const filtroTexto = fila.dataset.filtroTexto || '';
        const filtroEstado = fila.dataset.filtroEstado ?? '';
        const filtroFecha = fila.dataset.filtroFecha ?? '';

        let visible = true;

        if (texto && !filtroTexto.includes(texto)) {
            visible = false;
        }
        if (visible && estado !== '' && filtroEstado !== estado) {
            visible = false;
        }
        if (visible && fechaInicio && filtroFecha && filtroFecha < fechaInicio) {
            visible = false;
        }
        if (visible && fechaFin && filtroFecha && filtroFecha > fechaFin) {
            visible = false;
        }

        fila.dataset.filtroOculto = visible ? 'false' : 'true';
    });

    paginaActual = 1;
    renderizarPagina();
}

function limpiar() {
    const busqueda = document.getElementById('busqueda');
    const estado = document.getElementById('estado');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');

    if (busqueda) busqueda.value = '';
    if (estado) estado.value = '';
    if (fechaInicio) fechaInicio.value = '';
    if (fechaFin) fechaFin.value = '';

    document.querySelectorAll('table tbody tr[data-filtro-texto]').forEach(fila => {
        fila.dataset.filtroOculto = 'false';
    });

    paginaActual = 1;
    renderizarPagina();
}

// === Paginación genérica de tablas ===
// Se aplica sobre la misma marca data-filtro-texto que usan los filtros de arriba,
// así que funciona en las 12 vistas sin tocar ningún archivo .blade.php. Solo pagina
// las filas que pasaron el filtro (data-filtro-oculto="false"); cambiar de página no
// afecta al filtro, y aplicar un filtro siempre vuelve a la página 1.
const FILAS_POR_PAGINA = 10;
let paginaActual = 1;

function filasDeTabla() {
    return Array.from(document.querySelectorAll('table tbody tr[data-filtro-texto]'));
}

function filasFiltradas() {
    return filasDeTabla().filter(fila => fila.dataset.filtroOculto !== 'true');
}

function renderizarPagina() {
    const filas = filasDeTabla();
    if (filas.length === 0) {
        return;
    }

    const filtradas = filasFiltradas();
    const totalPaginas = Math.max(1, Math.ceil(filtradas.length / FILAS_POR_PAGINA));
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;

    const inicio = (paginaActual - 1) * FILAS_POR_PAGINA;
    const finPagina = inicio + FILAS_POR_PAGINA;

    filas.forEach(fila => { fila.style.display = 'none'; });
    filtradas.slice(inicio, finPagina).forEach(fila => { fila.style.display = ''; });

    renderizarControlesPaginacion(filtradas.length, totalPaginas);
}

function irAPagina(numero) {
    paginaActual = numero;
    renderizarPagina();
}

function renderizarControlesPaginacion(totalRegistros, totalPaginas) {
    const tabla = document.querySelector('table');
    if (!tabla) return;

    let contenedor = document.getElementById('paginacionControles');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'paginacionControles';
        contenedor.className = 'pagination';
        // Se ancla después del contenedor con scroll (si existe) y no de la tabla en sí,
        // para que la paginación quede a ancho completo y no se recorte junto con la tabla.
        const referencia = tabla.closest('.table-responsive') || tabla;
        referencia.insertAdjacentElement('afterend', contenedor);
    }

    if (totalRegistros === 0) {
        contenedor.innerHTML = '<span class="pagination-info">No hay registros para mostrar.</span>';
        return;
    }

    const inicio = (paginaActual - 1) * FILAS_POR_PAGINA + 1;
    const fin = Math.min(paginaActual * FILAS_POR_PAGINA, totalRegistros);

    let botonesPaginas = '';
    for (let numero = 1; numero <= totalPaginas; numero++) {
        botonesPaginas += `<button type="button" class="pagination-page${numero === paginaActual ? ' activo' : ''}" onclick="irAPagina(${numero})">${numero}</button>`;
    }

    contenedor.innerHTML = `
        <span class="pagination-info">Mostrando ${inicio}-${fin} de ${totalRegistros} registros</span>
        <div class="pagination-controles">
            <button type="button" class="pagination-nav" onclick="irAPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>&laquo; Anterior</button>
            ${botonesPaginas}
            <button type="button" class="pagination-nav" onclick="irAPagina(${paginaActual + 1})" ${paginaActual === totalPaginas ? 'disabled' : ''}>Siguiente &raquo;</button>
        </div>
    `;
}

// Envuelve la tabla principal en un contenedor con scroll horizontal propio,
// para que en mobile solo la tabla scrollee y no toda la página. Se hace por JS
// (no en cada .blade.php) para no tener que tocar las 12 vistas por separado.
function envolverTablaResponsive() {
    const tabla = document.querySelector('.container table');
    if (!tabla || tabla.parentElement.classList.contains('table-responsive')) {
        return;
    }

    const envoltorio = document.createElement('div');
    envoltorio.className = 'table-responsive';
    tabla.parentElement.insertBefore(envoltorio, tabla);
    envoltorio.appendChild(tabla);
}

document.addEventListener('DOMContentLoaded', () => {
    envolverTablaResponsive();

    document.querySelectorAll('table tbody tr[data-filtro-texto]').forEach(fila => {
        fila.dataset.filtroOculto = 'false';
    });

    renderizarPagina();
});

// === Menú lateral deslizante (hamburguesa) para mobile ===
document.addEventListener('DOMContentLoaded', () => {
    const boton = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!boton || !sidebar || !backdrop) {
        return;
    }

    const cerrarMenu = () => {
        sidebar.classList.remove('abierta');
        backdrop.classList.remove('visible');
    };

    boton.addEventListener('click', (evento) => {
        sidebar.classList.toggle('abierta');
        backdrop.classList.toggle('visible');
        evento.stopPropagation();
    });

    backdrop.addEventListener('click', cerrarMenu);
});

// === Panel de notificaciones (campanita del top-bar) ===
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('notificacionesToggle');
    const panel = document.getElementById('notificacionesPanel');

    if (!toggle || !panel) {
        return;
    }

    toggle.addEventListener('click', (evento) => {
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        evento.stopPropagation();
    });

    panel.addEventListener('click', (evento) => evento.stopPropagation());

    document.addEventListener('click', () => {
        panel.style.display = 'none';
    });
});

// === Exportar reportes en PDF ===
// El botón superior ("📄 PDF" con id="btnExportarPdf" y data-ruta-pdf) exporta los
// registros que pasan el filtro actual (data-filtro-oculto="false"): si no hay ningún
// filtro aplicado eso equivale a "todos", y si hay uno aplicado (buscar()) equivale a
// "filtrados" -no hace falta ninguna lógica extra para distinguir ambos casos. Se usa
// el estado del filtro y no style.display porque la paginación oculta filas que sí
// pasaron el filtro pero no caben en la página actual; esas deben seguir exportándose.
// Los botones de fila con clase "btn-pdf" y data-ruta-pdf/data-id exportan un único registro.
function enviarFormularioPdf(ruta, ids) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = ruta;
    form.style.display = 'none';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = token;
    form.appendChild(csrf);

    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();
}

document.addEventListener('DOMContentLoaded', () => {
    const btnExportar = document.getElementById('btnExportarPdf');

    if (btnExportar) {
        btnExportar.addEventListener('click', () => {
            const ids = filasFiltradas()
                .map(fila => fila.dataset.id)
                .filter(Boolean);

            enviarFormularioPdf(btnExportar.dataset.rutaPdf, ids);
        });
    }

    document.querySelectorAll('.btn-pdf[data-ruta-pdf]').forEach(boton => {
        boton.addEventListener('click', () => {
            enviarFormularioPdf(boton.dataset.rutaPdf, [boton.dataset.id]);
        });
    });
});
