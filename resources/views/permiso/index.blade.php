@extends('layout.navbar')

@section('titulo', 'Gestión de Permisos')

@section('contenido')

{{-- Mensajes de error o éxito --}}
@if (session('error'))
    <div class="alerta-error">{{ session('error') }}</div>
@endif

@if (session('success'))
    <div class="alerta-exito">{{ session('success') }}</div>
@endif

<div class="container">
  <div class="header-section">
    <h1>Gestión de Permisos</h1>
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.permiso') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear Permiso</button>
    </div>
  </div>
  <!--SECCION DE FILTROS -->
  <div class="filters">
    <div class="input-group full">
      <input type="text" id="busqueda" placeholder="Busca un nombre de permiso">
    </div>

    <button class="btn blue" onclick="buscar()">Buscar</button>
    <button class="btn red" onclick="limpiar()">Limpiar</button>
  </div>
  <!--TABLA DE REGISTROS -->
  <table>
    <thead>
      <tr class="headerTable">
        <th><span>ID</span></th>
        <th><span>Nombre del Permiso</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato['id'] }}" data-filtro-texto="{{ strtolower($dato['nombre']) }}">
        <td>{{ $dato['id'] }}</td>
        <td>{{ $dato['nombre'] }}</td>
        <td class="acciones">
          <button class="btn btn-edit btn-abrir-editar"
              data-id="{{ $dato['id'] }}"
              data-nombre="{{ $dato['nombre'] }}">
              Editar
          </button>
          <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.permiso') }}" data-id="{{ $dato['id'] }}">PDF</button>
          <button class="btn btn-cancelar btn-abrir-eliminar"
              data-id-eliminar="{{ $dato['id'] }}">
              Eliminar
          </button>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- ventana modal crear --}}
<div id="modalCrear" class="modal-overlay" style="display:none">
    <div class="modal-contenido">
        <div class="modal-header">
            <h2>Agregar Permiso</h2>
            <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('crear.permiso') }}" method="POST">
            @csrf

            <div class="campo-form">
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelarM" id="cancelarModal">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal editar --}}
<div id="modalEditar" class="modal-overlay" style="display:none">
    <div class="modal-contenido">
        <div class="modal-header">
            <h2>Editar Permiso</h2>
            <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('editar.permiso') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="edit_id">

            <div class="campo-form">
                <label>Nombre:</label>
                <input type="text" name="nombre" id="edit_nombre" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelarM" id="cancelarEditar">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal eliminar --}}
<div id="modalEliminar" class="modal-overlay" style="display:none">
    <div class="modal-contenido">
        <div class="modal-header">
            <h2>Eliminar Permiso</h2>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <span>¿Seguro que desea eliminar este permiso?</span>

        <form action="{{ route('eliminar.permiso') }}" method="POST">
            @csrf
            <input type="hidden" name="inputIdEliminar" id="inputIdEliminar">

            <div class="modal-footer">
                <button type="button" class="btn-cancelarM" id="cancelarEliminar">Cancelar</button>
                <button type="submit" class="btn-guardar">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
    /* === Modal Crear === */
    const modalCrear = document.getElementById('modalCrear');
    document.getElementById('abrirModalCrear').addEventListener('click', () => modalCrear.style.display = 'flex');
    document.getElementById('cerrarModalCrear').addEventListener('click', () => modalCrear.style.display = 'none');
    document.getElementById('cancelarModal').addEventListener('click', () => modalCrear.style.display = 'none');

    /* === Modal Eliminar === */
    const modalEliminar = document.getElementById('modalEliminar');
    const inputIdEliminar = document.getElementById('inputIdEliminar');

    document.querySelectorAll('.btn-abrir-eliminar').forEach(boton => {
        boton.addEventListener('click', () => {
            inputIdEliminar.value = boton.getAttribute('data-id-eliminar');
            modalEliminar.style.display = 'flex';
        });
    });

    document.getElementById('cerrarModalEliminar').addEventListener('click', () => modalEliminar.style.display = 'none');
    document.getElementById('cancelarEliminar').addEventListener('click', () => modalEliminar.style.display = 'none');

    /* === Modal Editar === */
    const modalEditar = document.getElementById('modalEditar');
    const editId = document.getElementById('edit_id');
    const editNombre = document.getElementById('edit_nombre');

    document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
        boton.addEventListener('click', () => {
            editId.value = boton.getAttribute('data-id');
            editNombre.value = boton.getAttribute('data-nombre');

            modalEditar.style.display = 'flex';
        });
    });

    document.getElementById('cerrarModalEditar').addEventListener('click', () => modalEditar.style.display = 'none');
    document.getElementById('cancelarEditar').addEventListener('click', () => modalEditar.style.display = 'none');

    /* === Cerrar modales al hacer clic fuera === */
    window.addEventListener('click', (e) => {
        if (e.target === modalEditar) modalEditar.style.display = 'none';
        if (e.target === modalCrear) modalCrear.style.display = 'none';
        if (e.target === modalEliminar) modalEliminar.style.display = 'none';
    });
</script>

@endsection
