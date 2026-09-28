@extends('layout.navbar')

@section('titulo', 'Usuario')

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
    <h1>Gestión de Usuarios</h1>
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.usuario') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear usuario</button>
    </div>
  </div>
  <!--SECCION DE FILTROS -->
  <div class="filters">
    <div class="input-group" style="pading:5%;">
      <select id="estado" class="select-filtro-estado">
        <option value="">Seleccionar Estado</option>
        <option value="1">Activo</option>
        <option value="0">Inactivo</option>
      </select>
    </div>

    <div class="input-group full">
      <input type="text" id="busqueda" placeholder="Busca un nombre de Usuario">
    </div>

    <button class="btn blue" onclick="buscar()">Buscar</button>
    <button class="btn red" onclick="limpiar()">Limpiar</button>
  </div>
  <!--TABLA DE REGISTROS -->
  <table>
    <thead>
      <tr class="headerTable">
        <th><span>ID</span></th>
        <th><span>Username</span></th>
        <th><span>Email</span></th>
        <th><span>Rol</span></th>
        <th><span>Estado</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato['id'] }}"
          data-filtro-texto="{{ strtolower($dato['username'].' '.$dato['email'].' '.($dato['rol'] ?? '')) }}"
          data-filtro-estado="{{ $dato['estado'] }}">
        <td>{{ $dato['id'] }}</td>
        <td>{{ $dato['username']}}</td>
        <td>{{ $dato['email'] }}</td>
        <td>{{ $dato['rol'] }}</td>
        <td>
          @if ($dato['estado'] == 1)
              <span class="badge-estado activo">Activo</span>
            @else
             <span class="badge-estado inactivo">inactivo</span>
            @endif
        </td>
        <td class="acciones">
          <button type="button" class="btn btn-ver btn-abrir-ver-usuario"
            data-usuario='@json(array_merge($dato, ["foto_url" => $dato["foto"] ? asset($dato["foto"]) : null]))'>
            Ver
          </button>

          <button type="button" class="btn btn-edit btn-abrir-editar"
            data-id="{{ $dato['id'] }}"
            data-username="{{ $dato['username'] }}"
            data-email="{{ $dato['email'] }}"
            data-estado="{{ $dato['estado'] }}"
            data-rol="{{ $dato['id_rol'] ?? '' }}">
            Editar
          </button>

          <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.usuario') }}" data-id="{{ $dato['id'] }}">PDF</button>
          <button type="button" class="btn btn-cancelar btn-abrir-eliminar" data-id-eliminar="{{ $dato['id'] }}">
            Inactivar
          </button>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- ventana modal crear --}}
<div id="modalCrear" class="modal-overlay" style="display:none">
  <div class="modal-contenido modal-2col">
    <div class="modal-header">
      <h1><span>Agregar Usuario</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('crear.usuario') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="form-columnas">
        <div class="form-columna">
          <div class="campo-form">
            <label>Persona:</label>
            <select name="id_persona" id="selectPersona" required>
              <option value="">-- Seleccione una persona --</option>
              @foreach ($personas as $persona)
                <option value="{{ $persona->id }}" data-correo="{{ $persona->correo }}">{{ $persona->nombre . ' ' . $persona->apellido }}</option>
              @endforeach
            </select>
          </div>

          <div class="campo-form">
            <label>Username:</label>
            <input type="text" name="username" required>
          </div>

          <div class="campo-form">
            <label>Correo:</label>
            <input type="email" name="email" id="crearEmail" required>
          </div>

          <div class="campo-form">
            <label>Contraseña:</label>
            <input type="password" name="password" required>
          </div>
        </div>

        <div class="form-columna">
          <div class="form-group">
            <label for="foto">Foto:</label>
            <input type="file" id="foto" name="foto" accept="image/*">
          </div>

          <div class="campo-form">
            <label>Estado:</label>
            <input type="number" name="estado" value="1" required>
          </div>

          <div class="campo-form">
            <label>Rol:</label>
            <select name="id_rol" required>
              <option value="">-- Seleccione un rol --</option>
              @foreach ($roles as $rol)
                <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
              @endforeach
            </select>
          </div>
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
  <div class="modal-contenido modal-2col">
    <div class="modal-header">
      <h1><span>Editar Usuario</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.usuario') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="id" id="edit_id">

      <div class="form-columnas">
        <div class="form-columna">
          <div class="campo-form">
            <label>Username:</label>
            <input type="text" name="username" id="edit_username" required>
          </div>

          <div class="campo-form">
            <label>Correo:</label>
            <input type="email" name="email" id="edit_email" required>
          </div>

          <div class="campo-form">
            <label>Contraseña:</label>
            <input type="password" name="password">
            <small style="color: #9ca3af; margin-top: 5px; display: block;">Dejar en blanco para mantener la actual</small>
          </div>
        </div>

        <div class="form-columna">
          <div class="form-group">
            <label for="edit_foto">Foto:</label>
            <input type="file" id="edit_foto" name="foto" accept="image/*">
            <small style="color: #9ca3af; margin-top: 5px; display: block;">Dejar en blanco para mantener la actual</small>
          </div>

          <div class="campo-form">
            <label>Estado:</label>
            <input type="number" name="estado" id="edit_estado" required>
          </div>

          <div class="campo-form">
            <label>Rol:</label>
            <select name="id_rol" id="edit_rol" required>
              @foreach ($roles as $rol)
                <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
              @endforeach
            </select>
          </div>
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
            <h2>Inactivar Usuario</h2>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <span>¿Seguro que desea inactivar este usuario?</span>

        <form action="{{ route('eliminar.usuario') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="inputIdEliminar">

            <div class="modal-footer">
                <button type="button" class="btn-cancelarM" id="cancelarEliminar">Cancelar</button>
                <button type="submit" class="btn-guardar">Inactivar</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal ver detalle --}}
<div id="modalVerUsuario" class="modal-overlay" style="display:none">
  <div class="modal-contenido modal-2col">
    <div class="modal-header">
      <h1><span>Detalle del Usuario</span></h1>
      <button type="button" id="cerrarModalVerUsuario" class="btn-cerrar">&times;</button>
    </div>

    <div class="form-columnas">
      <div class="form-columna">
        <div class="campo-form">
          <label>Username:</label>
          <span id="verUsu_username"></span>
        </div>
        <div class="campo-form">
          <label>Email:</label>
          <span id="verUsu_email"></span>
        </div>
        <div class="campo-form">
          <label>Nombre completo:</label>
          <span id="verUsu_nombre"></span>
        </div>
        <div class="campo-form">
          <label>Cédula:</label>
          <span id="verUsu_cedula"></span>
        </div>
        <div class="campo-form">
          <label>Celular:</label>
          <span id="verUsu_celular"></span>
        </div>
        <div class="campo-form">
          <label>Rol:</label>
          <span id="verUsu_rol"></span>
        </div>
        <div class="campo-form">
          <label>Estado:</label>
          <span id="verUsu_estado"></span>
        </div>
      </div>

      <div class="form-columna-imagen">
        <label>Foto:</label>
        <img id="verUsu_foto" alt="Foto del usuario" style="display:none;">
        <span id="verUsu_foto_vacio" class="dashboard-list-empty">Sin foto registrada.</span>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-cancelarM" id="cancelarVerUsuario">Cerrar</button>
    </div>
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

  // Autocompletar correo al elegir una persona
  const selectPersona = document.getElementById('selectPersona');
  const crearEmail = document.getElementById('crearEmail');
  selectPersona.addEventListener('change', function() {
    const correo = this.selectedOptions[0]?.getAttribute('data-correo') || '';
    crearEmail.value = correo;
  });

  /* === Modal Editar === */
  const modalEditar = document.getElementById('modalEditar');
  const editId = document.getElementById('edit_id');
  const editUsername = document.getElementById('edit_username');
  const editEmail = document.getElementById('edit_email');
  const editEstado = document.getElementById('edit_estado');
  const editRol = document.getElementById('edit_rol');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
    boton.addEventListener('click', () => {
      editId.value = boton.getAttribute('data-id');
      editUsername.value = boton.getAttribute('data-username');
      editEmail.value = boton.getAttribute('data-email');
      editEstado.value = boton.getAttribute('data-estado');
      editRol.value = boton.getAttribute('data-rol');
      modalEditar.style.display = 'flex';
    });
  });

  document.getElementById('cerrarModalEditar').addEventListener('click', () => modalEditar.style.display = 'none');
  document.getElementById('cancelarEditar').addEventListener('click', () => modalEditar.style.display = 'none');

  /* === Modal Ver detalle === */
  const modalVerUsuario = document.getElementById('modalVerUsuario');
  const verUsuUsername = document.getElementById('verUsu_username');
  const verUsuEmail = document.getElementById('verUsu_email');
  const verUsuNombre = document.getElementById('verUsu_nombre');
  const verUsuCedula = document.getElementById('verUsu_cedula');
  const verUsuCelular = document.getElementById('verUsu_celular');
  const verUsuRol = document.getElementById('verUsu_rol');
  const verUsuEstado = document.getElementById('verUsu_estado');
  const verUsuFoto = document.getElementById('verUsu_foto');
  const verUsuFotoVacio = document.getElementById('verUsu_foto_vacio');

  document.querySelectorAll('.btn-abrir-ver-usuario').forEach(boton => {
    boton.addEventListener('click', () => {
      const usuario = JSON.parse(boton.getAttribute('data-usuario'));

      verUsuUsername.textContent = usuario.username || '—';
      verUsuEmail.textContent = usuario.email || '—';
      verUsuNombre.textContent = (usuario.nombre || usuario.apellido)
        ? `${usuario.nombre ?? ''} ${usuario.apellido ?? ''}`.trim()
        : '—';
      verUsuCedula.textContent = usuario.cedula || '—';
      verUsuCelular.textContent = usuario.celular || '—';
      verUsuRol.textContent = usuario.rol || '—';
      verUsuEstado.textContent = usuario.estado == 1 ? 'Activo' : 'Inactivo';

      if (usuario.foto_url) {
        verUsuFoto.src = usuario.foto_url;
        verUsuFoto.style.display = '';
        verUsuFotoVacio.style.display = 'none';
      } else {
        verUsuFoto.style.display = 'none';
        verUsuFotoVacio.style.display = '';
      }

      modalVerUsuario.style.display = 'flex';
    });
  });

  document.getElementById('cerrarModalVerUsuario').addEventListener('click', () => modalVerUsuario.style.display = 'none');
  document.getElementById('cancelarVerUsuario').addEventListener('click', () => modalVerUsuario.style.display = 'none');

  /* === Cerrar modales al hacer clic fuera === */
  window.addEventListener('click', (e) => {
    if (e.target === modalCrear) modalCrear.style.display = 'none';
    if (e.target === modalEditar) modalEditar.style.display = 'none';
    if (e.target === modalEliminar) modalEliminar.style.display = 'none';
    if (e.target === modalVerUsuario) modalVerUsuario.style.display = 'none';
  });
</script>

@endsection
