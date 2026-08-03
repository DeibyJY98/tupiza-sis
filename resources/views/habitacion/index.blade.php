@extends('layout.navbar')

@section('titulo', 'Gestión de Habitaciones')

@section('contenido')

@if (session('error'))
    <div class="alerta-error">{{ session('error') }}</div>
@endif

@if (session('success'))
    <div class="alerta-exito">{{ session('success') }}</div>
@endif

<div class="container">
  <div class="header-section">
    <h1>Gestión de Habitaciones</h1>   
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.habitacion') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear Habitación</button>
    </div> 
  </div>
  <div class="filters">
    <div class="input-group">
      <input type="date" id="filtroFechaInicio" class="input-field" placeholder=" " required>
      <label for="fecha_inicio" class="floating-label">Fecha Inicio</label>
    </div>

    <div class="input-group">
      <input type="date" id="filtroFechaFin" class="input-field" placeholder=" " required>
      <label for="fecha_fin" class="floating-label">Fecha Final</label>
    </div>

    <div class="input-group" style="pading:5%;">
      <select id="estado" style="color: #4f37d2; border: 2px solid #4f37d2; background-color: transparent;">
        <option value="">Seleccionar Estado</option>
        <option value="1">Disponible</option>
        <option value="0">No disponible</option>
      </select>
    </div>

    <div class="input-group full">
      <input type="text" id="busqueda" placeholder="Busca un número de habitación o tipo">
    </div>

    <button id="btnFiltrar" class="btn blue" onclick="buscar()">Buscar</button>
    <button class="btn red" onclick="limpiar()">Limpiar</button>
  </div>
  <!--TABLA DE REGISTROS -->
  <table>
    <thead>
      <tr class="headerTable">
        <th><span>ID</span></th>
        <th><span>Número</span></th>
        <th><span>Tipo</span></th>
        <th><span>Planta</span></th>
        <th><span>Estado</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato['id'] }}"
          data-filtro-texto="{{ strtolower($dato['numero_habitacion'].' '.($dato['tipo_habitacion']['nombre'] ?? '').' '.$dato['planta']) }}"
          data-filtro-estado="{{ $dato['estado'] }}">
          <td>{{ $dato['id'] }}</td>
          <td>{{ $dato['numero_habitacion'] }}</td>
          <td>{{ $dato['tipo_habitacion']['nombre'] ?? 'Sin tipo' }}</td>
          <td>Piso {{ $dato['planta'] }}</td>   
          <td>
              @if ($dato['estado'] == 1)
                  <span style="color: #22c55e; border: 2px solid #22c55e; background-color: transparent; padding: 5% 10%;">
                      Disponible
                  </span>
              @else
                  <span style="color: #ef4444; border: 2px solid #ef4444; background-color: transparent; padding: 5%;">
                      No disponible
                  </span>
              @endif    
          </td>   
          <td class="acciones">
              <!-- Botón editar con data-atributos -->
              <button class="btn btn-edit btn-abrir-editar"
                  data-id="{{ $dato['id'] }}"
                  data-numero="{{ $dato['numero_habitacion'] }}"
                  data-planta="{{ $dato['planta'] }}"
                  data-estado="{{ $dato['estado'] }}"
                  data-tipo="{{ $dato['tipo_habitacion']['id'] ?? '' }}">
                  Editar
              </button>

              <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.habitacion') }}" data-id="{{ $dato['id'] }}">PDF</button>

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
<div id="modalCrear" class="modal-overlay" style="display:none" >
    <div class="modal-contenido">
        <div class="modal-header">
            <h1><span>Agregar Habitación</span></h1>
            <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
        </div>

        <form action="{{ route('crear.habitacion') }}" method="POST">
            @csrf

            <div class="campo-form">
                <label>Número de Habitación:</label>
                <input type="text" name="numero_habitacion" required>
            </div>

            <div class="campo-form">
                <label>Planta:</label>
                <input type="number" name="planta" required min="1">
            </div>

            <div class="campo-form">
                <label>Estado:</label>
                <select name="estado" required>
                    <option value="1">Disponible</option>
                    <option value="0">No Disponible</option>
                </select>
            </div>

            <div class="campo-form">
                <label>Tipo de Habitación:</label>
                <select name="id_tipo_habitacion" required>
                    @foreach($tipos ?? [] as $tipo)
                        <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }} - {{ $tipo['precio'] }} Bs.</option>
                    @endforeach
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelarM" id="cancelarModal">Cancelar</button>
                <button type="submit" class="btn-guardar">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- ventana modal editar --}}
<div id="modalEditar" class="modal-overlay" style="display:none" >
  <div class="modal-contenido">
    <div class="modal-header">
      <h1><span>Editar Habitación</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.habitacion') }}" method="POST">
      @csrf
      <input type="hidden" name="id" id="edit_id">

      <div class="campo-form">
        <label>Número de Habitación:</label>
        <input type="text" name="numero_habitacion" id="edit_numero" required>
      </div>

      <div class="campo-form">
        <label>Planta:</label>
        <input type="number" name="planta" id="edit_planta" required min="1">
      </div>

      <div class="campo-form">
        <label>Estado:</label>
        <select name="estado" id="edit_estado" required>
          <option value="1">Disponible</option>
          <option value="0">No Disponible</option>
        </select>
      </div>

      <div class="campo-form">
        <label>Tipo de Habitación:</label>
        <select name="id_tipo_habitacion" id="edit_tipo" required>
          @foreach($tipos ?? [] as $tipo)
            <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }} - {{ $tipo['precio'] }} Bs.</option>
          @endforeach
        </select>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancelarM" id="cancelarEditar">Cancelar</button>
        <button type="submit" class="btn-guardar">Guardar</button>
      </div>
    </form>
  </div>
</div>

{{-- ventana modal eliminar --}}
<div id="modalEliminar" class="modal-overlay" style="display:none" >
  <div class="modal-contenido">
    <div class="modal-header">
      <h1><span>Eliminar Habitación</span></h1>
      <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
  </div>
  <br/>
  <span>¿Seguro que desea eliminar la habitación?</span>
    <form action="{{ route('eliminar.habitacion') }}" method="POST">
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
  /* === Filtro de Fechas === */
  const filtroFechaInicio = document.getElementById('filtroFechaInicio');
  const filtroFechaFin = document.getElementById('filtroFechaFin');
  const btnFiltrar = document.getElementById('btnFiltrar');
  
  // Establecer fecha mínima como hoy
  const hoy = new Date().toISOString().split('T')[0];
  filtroFechaInicio.min = hoy;
  filtroFechaFin.min = hoy;

  // Actualizar fecha mínima de fin cuando cambia inicio
  filtroFechaInicio.addEventListener('change', function() {
      filtroFechaFin.min = this.value;
      if (filtroFechaFin.value && filtroFechaFin.value < this.value) {
          filtroFechaFin.value = this.value;
      }
  });

  // Función para verificar disponibilidad
  async function verificarDisponibilidad() {
      if (!filtroFechaInicio.value || !filtroFechaFin.value) {
          alert('Por favor seleccione ambas fechas');
          return;
      }

      const filas = document.querySelectorAll('tbody tr');
      for (const fila of filas) {
          const habitacionId = fila.querySelector('td:first-child').textContent;
          const estadoCell = fila.querySelector('td:nth-child(5)');
          
          try {
              const response = await fetch(`/reserva/fechas-ocupadas/${habitacionId}`);
              const data = await response.json();
              
              let estaDisponible = true;
              const inicio = new Date(filtroFechaInicio.value);
              const fin = new Date(filtroFechaFin.value);

              // Generar array de fechas seleccionadas
              const fechasSeleccionadas = [];
              for (let d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 1)) {
                  fechasSeleccionadas.push(d.toISOString().split('T')[0]);
              }

              // Verificar si alguna fecha está ocupada
              const hayFechaOcupada = fechasSeleccionadas.some(fecha => 
                  data.fechas_ocupadas.includes(fecha)
              );

              if (hayFechaOcupada) {
                  estadoCell.innerHTML = `
                      <span style="color: #ef4444; border: 2px solid #ef4444; background-color: transparent; padding: 5%;">
                          No disponible
                      </span>`;
              } else {
                  estadoCell.innerHTML = `
                      <span style="color: #22c55e; border: 2px solid #22c55e; background-color: transparent; padding: 5% 10%;">
                          Disponible
                      </span>`;
              }
          } catch (error) {
              console.error('Error al verificar disponibilidad:', error);
          }
      }
  }

  btnFiltrar.addEventListener('click', verificarDisponibilidad);

  /* === Modal Crear === */
  const modal = document.getElementById('modalCrear');
  const cerrarmodal = document.getElementById('cerrarModalCrear');
  const cancelarModal = document.getElementById('cancelarModal');
  const abrirModalCrear = document.getElementById('abrirModalCrear');

  cerrarmodal.addEventListener('click',()=>modal.style.display= 'none');
  cancelarModal.addEventListener('click',()=>modal.style.display= 'none');
  abrirModalCrear.addEventListener('click',()=>modal.style.display= 'flex');

  /* === Modal Eliminar === */
  const modalEliminar = document.getElementById('modalEliminar');
  const cerrarEliminar = document.getElementById('cerrarModalEliminar');
  const cancelarEliminar = document.getElementById('cancelarEliminar');   
  const botonesEliminar = document.querySelectorAll('.btn-abrir-eliminar');

  botonesEliminar.forEach(boton => {
      boton.addEventListener('click', () => {
          // obtener los datos desde los atributos del botón
          const id = boton.getAttribute('data-id-Eliminar');            

          // llenar los campos del formulario
          inputIdEliminar.value= id ;

          // mostrar el modal
          modalEliminar.style.display = 'flex';
      });
  });
  cerrarEliminar.addEventListener('click',()=>modalEliminar.style.display= 'none');
  cancelarEliminar.addEventListener('click',()=>modalEliminar.style.display= 'none');
  
  /* === Modal Editar === */
  const modalEditar = document.getElementById('modalEditar');
  const cerrarEditar = document.getElementById('cerrarModalEditar');
  const cancelarEditar = document.getElementById('cancelarEditar');
  const editId = document.getElementById('edit_id');
  const editNumero = document.getElementById('edit_numero');
  const editPlanta = document.getElementById('edit_planta');
  const editEstado = document.getElementById('edit_estado');
  const editTipo = document.getElementById('edit_tipo');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
      boton.addEventListener('click', () => {
          editId.value = boton.getAttribute('data-id');
          editNumero.value = boton.getAttribute('data-numero');
          editPlanta.value = boton.getAttribute('data-planta');
          editEstado.value = boton.getAttribute('data-estado');
          editTipo.value = boton.getAttribute('data-tipo');
          modalEditar.style.display = 'flex';
      });
  });

  // botones para cerrar/cancelar
  cerrarEditar.addEventListener('click', () => modalEditar.style.display = 'none');
  cancelarEditar.addEventListener('click', () => modalEditar.style.display = 'none');

  // cerrar si hace clic fuera del modal
  window.addEventListener('click', (e) => {
      if (e.target === modalEditar) modalEditar.style.display = 'none';
      if (e.target === modalCrear) modalCrear.style.display = 'none';
      if (e.target === modalEliminar) modalEliminar.style.display = 'none';
  });
</script>

@endsection


