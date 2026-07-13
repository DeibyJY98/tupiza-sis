@extends('layout.navbar')

@section('titulo','Gestión de Clientes')

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
      <h1>Gestión de Clientes</h1>
      <div class="right-buttons">
        <button class="btn yellow">📄 PDF</button>
        <button class="btn green" id="abrirModalCrear">Crear Cliente</button>
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
        <input type="text" id="busqueda" placeholder="Busca un nombre de Cliente">
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
          <th><span>Nit</span></th>
          <th><span>Estado</span></th>
          <th><span>Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($datos as $dato)
        <tr>
            <td>{{ $dato['id'] }}</td>
            <td>
              {{ isset($dato['persona']['nombre']) ? $dato['persona']['nombre'].' '.$dato['persona']['apellido'] : '' }}
            </td>
            <td>{{ $dato['nit'] }}</td>
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
                <!-- Botón editar -->
                <button class="btn btn-edit btn-abrir-editar"
                    data-id="{{ $dato['id'] }}"
                    data-nit="{{ $dato['nit'] }}"
                    data-estado="{{ $dato['estado'] }}">
                    Editar
                </button>

                <!-- Botón eliminar -->
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
      <h1><span>Agregar Cliente</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('crear.cliente') }}" method="POST">
      @csrf
      <div class="campo-form">
        <label>Persona:</label>
        <select name="id_persona" required>
          <option value="">-- Seleccione una persona --</option>
          @foreach ($personas as $persona)
            <option value="{{ $persona->id }}">{{ $persona->nombre }} {{ $persona->apellido }} ({{ $persona->cedula }})</option>
          @endforeach
        </select>
      </div>

      <div class="campo-form">
        <label>Nit:</label>
        <input type="number" name="nit" required>
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
      <h1><span>Editar Cliente</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.cliente') }}" method="POST">
      @csrf
      <input type="hidden" name="id" id="edit_id">

      <div class="campo-form">
        <label>Nit:</label>
        <input type="number" name="nit" id="edit_nit" required>
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
            <h1><span>Eliminar Cliente</span></h1>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <br/>
        <span>¿Seguro que desea eliminar este cliente?</span>

        <form action="{{ route('eliminar.cliente') }}" method="POST">
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
  const editNit = document.getElementById('edit_nit');
  const editEstado = document.getElementById('edit_estado');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
    boton.addEventListener('click', () => {
      editId.value = boton.getAttribute('data-id');
      editNit.value = boton.getAttribute('data-nit');
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
