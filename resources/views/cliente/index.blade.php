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
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.cliente') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear Cliente</button>
    </div>
  </div>
  <!--SECCION DE FILTROS -->
  <div class="filters">
    <div class="input-group" style="pading:5%;">
      <select id="estado" style="color: #4f37d2; border: 2px solid #4f37d2; background-color: transparent;">
        <option value="">Seleccionar Estado</option>
        <option value="1">Activo</option>
        <option value="0">Inactivo</option>
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
        <th><span>Razon Social</span></th>
        <th><span>Estado</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato['id'] }}"
          data-filtro-texto="{{ strtolower(($dato['persona']['nombre'] ?? '').' '.($dato['persona']['apellido'] ?? '').' '.$dato['nit'].' '.$dato['razon_social']) }}"
          data-filtro-estado="{{ $dato['estado'] }}">
          <td>{{ $dato['id'] }}</td>
          <td>
            {{ isset($dato['persona']['nombre']) ? $dato['persona']['nombre'].' '.$dato['persona']['apellido'] : '' }}
          </td>
          <td>{{ $dato['nit'] }}</td>
          <td>{{ $dato['razon_social'] }}</td>
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
                  data-nit="{{ $dato['nit'] }}"
                  data-razon-social="{{ $dato['razon_social'] }}"
                  data-estado="{{ $dato['estado'] }}"
                  data-id-persona="{{ $dato['persona']['id'] ?? '' }}">
                  Editar
              </button>
              <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.cliente') }}" data-id="{{ $dato['id'] }}">PDF</button>
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
            <h2>Agregar Cliente</h2>
            <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('crear.cliente') }}" method="POST">
            @csrf

            <div class="campo-form">
                <label>Persona:</label>
                <select name="id_persona" required>
                    <option value="">-- Seleccione una persona --</option>
                    @foreach ($personas as $persona)
                        <option value="{{ $persona->id }}">{{ $persona->nombre }} {{ $persona->apellido }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo-form">
                <label>Nit:</label>
                <input type="number" name="nit" required>
            </div>

            <div class="campo-form">
                <label>Razón Social:</label>
                <input type="text" name="razon_social" required>
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
            <h2>Editar Cliente</h2>
            <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('editar.cliente') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="edit_id">

            <div class="campo-form">
                <label>Persona:</label>
                <select name="id_persona" id="edit_id_persona" required>
                    @foreach ($personas as $persona)
                        <option value="{{ $persona->id }}">{{ $persona->nombre }} {{ $persona->apellido }}</option>
                    @endforeach
                </select>
            </div>

            <div class="campo-form">
                <label>Nit:</label>
                <input type="number" name="nit" id="edit_nit" required>
            </div>

            <div class="campo-form">
                <label>Razón Social:</label>
                <input type="text" name="razon_social" id="edit_razon_social" required>
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
            <h2>Eliminar Cliente</h2>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
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
    const editIdPersona = document.getElementById('edit_id_persona');
    const editNit = document.getElementById('edit_nit');
    const editRazonSocial = document.getElementById('edit_razon_social');
    const editEstado = document.getElementById('edit_estado');

    document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
        boton.addEventListener('click', () => {
            editId.value = boton.getAttribute('data-id');
            editIdPersona.value = boton.getAttribute('data-id-persona');
            editNit.value = boton.getAttribute('data-nit');
            editRazonSocial.value = boton.getAttribute('data-razon-social');
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
