@extends('layout.navbar')

@section('titulo', 'Gestión de Trabajadores')

@section('contenido')

{{-- Mensajes de error o éxito --}}
@if (session('error'))
    <div class="alerta-error">
        {{ session('error') }}
    </div>
@endif

@if (session('success'))
    <div class="alerta-exito">
        {{ session('success') }}
    </div>
@endif

<main class="container">
    <div class="header-section">
        <h1>Gestión de Trabajadores</h1>
        <div class="right-buttons">
            <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.trabajador') }}">📄 PDF</button>
            <button class="btn green" id="abrirModalCrear">Crear Trabajador</button>
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
        <input type="text" id="busqueda" placeholder="Busca un nombre de Trabajador">
      </div>

      <button class="btn blue" onclick="buscar()">Buscar</button>
      <button class="btn red" onclick="limpiar()">Limpiar</button>
    </div>
    <!--TABLA DE REGISTROS -->
    <div>
        <table>
            <thead>
                <tr class="headerTable">
                    <th><span>ID</span></th>
                    <th><span>Nombre</span></th>
                    <th><span>Cargo</span></th>
                    <th><span>Salario</span></th>
                    <th><span>Estado</span></th>
                    <th><span>Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($datos as $dato)
            <tr data-id="{{ $dato['id'] }}"
                data-filtro-texto="{{ strtolower(($dato['persona']['nombre'] ?? '').' '.($dato['persona']['apellido'] ?? '').' '.$dato['cargo']) }}"
                data-filtro-estado="{{ $dato['estado'] }}">
                <td>{{ $dato['id'] }}</td>
                <td>
                    {{ isset($dato['persona']['nombre']) ? $dato['persona']['nombre'].' '.$dato['persona']['apellido'] : '' }}
                </td>
                <td>{{ $dato['cargo'] }}</td>
                <td>{{ $dato['salario'] }}</td>
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
                        data-cargo="{{ $dato['cargo'] }}"
                        data-salario="{{ $dato['salario'] }}"
                        data-estado="{{ $dato['estado'] }}"
                        data-id-persona="{{ $dato['persona']['id'] ?? '' }}">
                        Editar
                    </button>

                    <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.trabajador') }}" data-id="{{ $dato['id'] }}">PDF</button>

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
</main>

{{-- ventana modal crear --}}
<div id="modalCrear" class="modal-overlay" style="display:none">
    <div class="modal-contenido">
        <div class="modal-header">
            <h2 style="color: black">Agregar Trabajador</h2>
            <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('crear.trabajador') }}" method="POST">
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
                <label>Cargo:</label>
                <input type="text" name="cargo" required>
            </div>

            <div class="campo-form">
                <label>Salario:</label>
                <input type="number" name="salario" step="0.01" min="0" required>
            </div>

            <div class="campo-form">
                <label>Estado:</label>
                <input type="number" name="estado" value="1" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="cancelarModal">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal editar --}}
<div id="modalEditar" class="modal-overlay" style="display:none">
    <div class="modal-contenido">
        <div class="modal-header">
            <h2 style="color: black">Editar Trabajador</h2>
            <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('editar.trabajador') }}" method="POST">
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
                <label>Cargo:</label>
                <input type="text" name="cargo" id="edit_cargo" required>
            </div>

            <div class="campo-form">
                <label>Salario:</label>
                <input type="number" name="salario" id="edit_salario" step="0.01" min="0" required>
            </div>

            <div class="campo-form">
                <label>Estado:</label>
                <input type="number" name="estado" id="edit_estado" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="cancelarEditar">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal eliminar --}}
<div id="modalEliminar" class="modal-overlay" style="display:none">
    <div class="modal-contenido">
        <div class="modal-header">
            <h2 style="color: black">Eliminar Trabajador</h2>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <span>¿Seguro que desea eliminar este trabajador?</span>

        <form action="{{ route('eliminar.trabajador') }}" method="POST">
            @csrf
            <input type="hidden" name="inputIdEliminar" id="inputIdEliminar">

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="cancelarEliminar">Cancelar</button>
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
    const editCargo = document.getElementById('edit_cargo');
    const editSalario = document.getElementById('edit_salario');
    const editEstado = document.getElementById('edit_estado');

    document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
        boton.addEventListener('click', () => {
            editId.value = boton.getAttribute('data-id');
            editIdPersona.value = boton.getAttribute('data-id-persona');
            editCargo.value = boton.getAttribute('data-cargo');
            editSalario.value = boton.getAttribute('data-salario');
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
