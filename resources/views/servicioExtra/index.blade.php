@extends('layout.navbar')

@section('titulo','Servicios Extras')

@section('contenido')

{{-- Mensajes de error o éxito --}}
@if (session('error'))
    <div class="alerta-error">{{ session('error') }}</div>
@endif

@if (session('success'))
    <div class="alerta-exito">{{ session('success') }}</div>
@endif

    <main class="container">
    <div class="header-section">
      <h1>Gestión de Servicios Extras</h1>
      <div class="right-buttons">
        <button class="btn yellow">📄 PDF</button>
        <button class="btn green" id="abrirModalCrear">Crear Servicio Extra</button>
      </div>
    </div>
    <!--SECCION DE FILTROS -->
    <div class="filters">
      <div class="input-group">
        <input type="date" id="fecha_inicio" class="input-field" placeholder=" " required>
        <label for="fecha_inicio" class="floating-label">Fecha Inicio</label>
      </div>
      <div class="input-group">
        <input type="date" id="fecha_fin" class="input-field" placeholder=" " required>
        <label for="fecha_fin" class="floating-label">Fecha Final</label>
      </div>
      <div class="input-group" style="pading:5%;">
        <select id="estado" style="color: #4f37d2; border: 2px solid #4f37d2; background-color: transparent;">
          <option value="">Seleccionar Estado</option>
          <option value="completado">Completado</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </div>
      <div class="input-group full">
        <input type="text" id="busqueda" placeholder="Busca un nombre de servicio">
      </div>

      <button class="btn blue" onclick="buscar()">Buscar</button>
      <button class="btn red" onclick="limpiar()">Limpiar</button>
    </div>
    <!--TABLA DE REGISTROS -->
    <table>
      <thead>
        <tr class="headerTable">
          <th><span>ID</span></th>
          <th><span>Nombre</span></th>
          <th><span>Descripcion</span></th>
          <th><span>Precio</span></th>
          <th><span>Estado</span></th>
          <th><span>Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($datos as $dato)
        <tr>
            <td>{{ $dato['id'] }}</td>
            <td>{{ $dato['nombre'] }}</td>
            <td>{{ $dato['descripcion'] }}</td>
            <td>{{ $dato['precio'] }}</td>
            <td>
              @if ($dato['estado'] == 1)
                <span style="color: #22c55e; border: 2px solid #22c55e; background-color: transparent; padding: 5%;">
                  Activo
                </span>
              @else
                <span style="color: #ef4444; border: 2px solid #ef4444; background-color: transparent; padding: 5% 8%;">
                  Inactivo
                </span>
              @endif
            </td>
            <td class="acciones">
                <button class="btn btn-edit btn-abrir-editar"
                    data-id="{{ $dato['id'] }}"
                    data-nombre="{{ $dato['nombre'] }}"
                    data-descripcion="{{ $dato['descripcion'] }}"
                    data-precio="{{ $dato['precio'] }}"
                    data-estado="{{ $dato['estado'] }}">
                    Editar
                </button>

                <button class="btn btn-cancelar btn-abrir-eliminar"
                    data-id-eliminar="{{ $dato['id'] }}">
                    Eliminar
                </button>
            </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </main>

{{-- ventana modal crear --}}
<div id="modalCrear" class="modal-overlay" style="display:none">
  <div class="modal-contenido">
    <div class="modal-header">
      <h1><span>Agregar Servicio Extra</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('crear.servicio_extra') }}" method="POST">
      @csrf
      <div class="campo-form">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
      </div>

      <div class="campo-form">
        <label>Descripción:</label>
        <input type="text" name="descripcion" required>
      </div>

      <div class="campo-form">
        <label>Precio:</label>
        <input type="number" name="precio" step="0.01" required>
      </div>

      <div class="campo-form">
        <label>Estado:</label>
        <input type="number" name="estado" value="1" required>
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
      <h1><span>Editar Servicio Extra</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.servicio_extra') }}" method="POST">
      @csrf
      <input type="hidden" name="id" id="edit_id">

      <div class="campo-form">
        <label>Nombre:</label>
        <input type="text" name="nombre" id="edit_nombre" required>
      </div>

      <div class="campo-form">
        <label>Descripción:</label>
        <input type="text" name="descripcion" id="edit_descripcion" required>
      </div>

      <div class="campo-form">
        <label>Precio:</label>
        <input type="number" name="precio" id="edit_precio" step="0.01" required>
      </div>

      <div class="campo-form">
        <label>Estado:</label>
        <input type="number" name="estado" id="edit_estado" required>
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
            <h1><span>Eliminar Servicio Extra</span></h1>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <br/>
        <span>¿Seguro que desea eliminar este servicio extra?</span>

        <form action="{{ route('eliminar.servicio_extra') }}" method="POST">
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
  const editDescripcion = document.getElementById('edit_descripcion');
  const editPrecio = document.getElementById('edit_precio');
  const editEstado = document.getElementById('edit_estado');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
    boton.addEventListener('click', () => {
      editId.value = boton.getAttribute('data-id');
      editNombre.value = boton.getAttribute('data-nombre');
      editDescripcion.value = boton.getAttribute('data-descripcion');
      editPrecio.value = boton.getAttribute('data-precio');
      editEstado.value = boton.getAttribute('data-estado');
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
