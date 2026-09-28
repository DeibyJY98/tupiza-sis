@extends('layout.navbar')

@section('titulo', 'Roles')

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
    <h1>Gestión de Roles</h1>
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.rol') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear Rol</button>
    </div>
  </div>
  <div class="filters">
    <div class="input-group" style="pading:5%;">
      <select id="estado" class="select-filtro-estado">
        <option value="">Seleccionar Estado</option>
        <option value="1">Activo</option>
        <option value="0">Inactivo</option>
      </select>
    </div>

    <div class="input-group full">
      <input type="text" id="busqueda" placeholder="Busca un nombre de Rol">
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
        <th><span>Estado</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato->id }}" data-filtro-texto="{{ strtolower($dato->nombre) }}" data-filtro-estado="{{ $dato->estado }}">
        <td>{{ $dato->id }}</td>
        <td>{{ $dato->nombre }}</td>
        <td>
          @if ($dato['estado'] == 1)
            <span class="badge-estado activo">Activo</span>
          @else
            <span class="badge-estado inactivo">inactivo</span>
          @endif
        </td>
        @php
          $rolVerData = [
              'id' => $dato->id,
              'nombre' => $dato->nombre,
              'estado' => $dato->estado,
              'permisos' => $dato->permisos->pluck('nombre')->values(),
              'usuarios' => $dato->users->map(function ($u) {
                  $nombre = trim((optional($u->persona)->nombre ?? '') . ' ' . (optional($u->persona)->apellido ?? ''));
                  return $nombre ?: $u->username;
              })->values(),
          ];
        @endphp
        <td class="acciones">
          <button type="button" class="btn btn-ver btn-abrir-ver-rol" data-rol='@json($rolVerData)'>
            Ver
          </button>

          <button type="button" class="btn btn-edit btn-abrir-editar"
              data-id="{{ $dato->id }}"
              data-nombre="{{ $dato->nombre }}"
              data-estado="{{ $dato->estado }}"
              data-permisos="{{ $dato->permisos->pluck('id')->implode(',') }}">
              Editar
          </button>

          <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.rol') }}" data-id="{{ $dato->id }}">PDF</button>

          <button type="button" class="btn btn-cancelar btn-abrir-eliminar" data-id-eliminar="{{ $dato->id }}">
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
            <h2>Agregar Rol</h2>
            <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('crear.rol') }}" method="POST">
            @csrf

            <div class="campo-form">
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
            </div>

            <div class="campo-form">
                <label>Estado:</label>
                <input type="number" name="estado" value="1" required>
            </div>

            <div class="campo-form">
                <label>Permisos:</label>
                <div class="checkbox-group" id="permisosCrear">
                    @forelse ($permisos as $permiso)
                        <label class="checkbox-item">
                            <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}" class="permiso-check-crear">
                            {{ $permiso->nombre }}
                        </label>
                    @empty
                        <span class="checkbox-empty">No hay permisos registrados.</span>
                    @endforelse
                </div>
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
            <h2>Editar Rol</h2>
            <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('editar.rol') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="edit_id">

            <div class="campo-form">
                <label>Nombre:</label>
                <input type="text" name="nombre" id="edit_nombre" required>
            </div>

            <div class="campo-form">
                <label>Estado:</label>
                <input type="number" name="estado" id="edit_estado" required>
            </div>

            <div class="campo-form">
                <label>Permisos:</label>
                <div class="checkbox-group" id="permisosEditar">
                    @forelse ($permisos as $permiso)
                        <label class="checkbox-item">
                            <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}" class="permiso-check-editar">
                            {{ $permiso->nombre }}
                        </label>
                    @empty
                        <span class="checkbox-empty">No hay permisos registrados.</span>
                    @endforelse
                </div>
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
            <h2>Eliminar Rol</h2>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <span>¿Seguro que desea eliminar este rol?</span>

        <form action="{{ route('eliminar.rol') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="inputIdEliminar">

            <div class="modal-footer">
                <button type="button" class="btn-cancelarM" id="cancelarEliminar">Cancelar</button>
                <button type="submit" class="btn-guardar">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal ver detalle --}}
<div id="modalVerRol" class="modal-overlay" style="display:none">
  <div class="modal-contenido modal-2col">
    <div class="modal-header">
      <h1><span>Detalle del Rol</span></h1>
      <button type="button" id="cerrarModalVerRol" class="btn-cerrar">&times;</button>
    </div>

    <div class="form-columnas">
      <div class="form-columna">
        <div class="campo-form">
          <label>Nombre:</label>
          <span id="verRol_nombre"></span>
        </div>
        <div class="campo-form">
          <label>Estado:</label>
          <span id="verRol_estado"></span>
        </div>
      </div>

      <div class="form-columna">
        <div class="campo-form">
          <label>Permisos asignados:</label>
          <ul class="dashboard-list" id="verRol_permisos"></ul>
        </div>
        <div class="campo-form">
          <label>Usuarios con este rol:</label>
          <ul class="dashboard-list" id="verRol_usuarios"></ul>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-cancelarM" id="cancelarVerRol">Cerrar</button>
    </div>
  </div>
</div>

<script>
    /* === Modal Crear === */
    const modalCrear = document.getElementById('modalCrear');
    document.getElementById('abrirModalCrear').addEventListener('click', () => {
        document.querySelectorAll('.permiso-check-crear').forEach(checkbox => checkbox.checked = false);
        modalCrear.style.display = 'flex';
    });
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
    const editEstado = document.getElementById('edit_estado');

    document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
        boton.addEventListener('click', () => {
            editId.value = boton.getAttribute('data-id');
            editNombre.value = boton.getAttribute('data-nombre');
            editEstado.value = boton.getAttribute('data-estado');

            const idsPermisos = (boton.getAttribute('data-permisos') || '')
                .split(',')
                .filter(Boolean);
            document.querySelectorAll('.permiso-check-editar').forEach(checkbox => {
                checkbox.checked = idsPermisos.includes(checkbox.value);
            });

            modalEditar.style.display = 'flex';
        });
    });

    document.getElementById('cerrarModalEditar').addEventListener('click', () => modalEditar.style.display = 'none');
    document.getElementById('cancelarEditar').addEventListener('click', () => modalEditar.style.display = 'none');

    /* === Modal Ver detalle === */
    const modalVerRol = document.getElementById('modalVerRol');
    const verRolNombre = document.getElementById('verRol_nombre');
    const verRolEstado = document.getElementById('verRol_estado');
    const verRolPermisos = document.getElementById('verRol_permisos');
    const verRolUsuarios = document.getElementById('verRol_usuarios');

    document.querySelectorAll('.btn-abrir-ver-rol').forEach(boton => {
        boton.addEventListener('click', () => {
            const rol = JSON.parse(boton.getAttribute('data-rol'));

            verRolNombre.textContent = rol.nombre || '—';
            verRolEstado.textContent = rol.estado == 1 ? 'Activo' : 'Inactivo';

            verRolPermisos.innerHTML = (rol.permisos && rol.permisos.length)
                ? rol.permisos.map(nombre => `<li>${nombre}</li>`).join('')
                : '<li class="dashboard-list-empty">Sin permisos asignados.</li>';

            verRolUsuarios.innerHTML = (rol.usuarios && rol.usuarios.length)
                ? rol.usuarios.map(nombre => `<li>${nombre}</li>`).join('')
                : '<li class="dashboard-list-empty">Sin usuarios con este rol.</li>';

            modalVerRol.style.display = 'flex';
        });
    });

    document.getElementById('cerrarModalVerRol').addEventListener('click', () => modalVerRol.style.display = 'none');
    document.getElementById('cancelarVerRol').addEventListener('click', () => modalVerRol.style.display = 'none');

    /* === Cerrar modales al hacer clic fuera === */
    window.addEventListener('click', (e) => {
        if (e.target === modalEditar) modalEditar.style.display = 'none';
        if (e.target === modalCrear) modalCrear.style.display = 'none';
        if (e.target === modalEliminar) modalEliminar.style.display = 'none';
        if (e.target === modalVerRol) modalVerRol.style.display = 'none';
    });
</script>

@endsection
