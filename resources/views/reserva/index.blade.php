@extends('layout.navbar')

@section('titulo', 'Gestión de Reservas')

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
    <h1>Gestión de Reservas</h1>    
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.reserva') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear Reserva</button>
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
        <option value="1">Completado</option>
        <option value="0">Cancelado</option>
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
        <th><span>Fecha ingreso</span></th>
        <th><span>Fecha salida</span></th>
        <th><span>Costo total</span></th>
        <th><span>Trabajador</span></th>
        <th><span>Cliente</span></th>
        <th><span>Habitación</span></th>
        <th><span>Estado</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
    @foreach ($datos as $dato)
    <tr data-id="{{ $dato['id'] }}"
        data-filtro-texto="{{ strtolower(($dato['cliente']['nombre'] ?? '').' '.($dato['cliente']['apellido'] ?? '').' '.($dato['trabajador']['nombre'] ?? '').' '.($dato['trabajador']['apellido'] ?? '').' '.($dato['habitaciones'][0]['numero_habitacion'] ?? '')) }}"
        data-filtro-estado="{{ $dato['estado'] }}"
        data-filtro-fecha="{{ $dato['fecha_inicio'] }}">
        <td>{{ $dato['id'] }}</td>
        <td>{{ $dato['fecha_inicio'] }}</td>
        <td>{{ $dato['fecha_fin'] }}</td>
        <td>{{ $dato['costo_total'] }}</td>
        <td>
            {{ isset($dato['trabajador']['nombre']) ? $dato['trabajador']['nombre'].' '.$dato['trabajador']['apellido'] : '' }}
        </td>
        <td>
            {{ isset($dato['cliente']['nombre']) ? $dato['cliente']['nombre'].' '.$dato['cliente']['apellido'] : '' }}
        </td>
        <td>
            @if(isset($dato['habitaciones']) && count($dato['habitaciones'])>0)
                {{ 'Habitación ' . ($dato['habitaciones'][0]['numero_habitacion'] ?? $dato['habitaciones'][0]['id']) }}
            @else
                
            @endif
        </td>
        <td>
          @if ($dato['estado'] == 1)
            <span style="color: #22c55e; border: 2px solid #22c55e; background-color: transparent; padding: 5%;">
              Completado
            </span>
          @else
            <span style="color: #ef4444; border: 2px solid #ef4444; background-color: transparent; padding: 5% 9%;">
              Cancelado
            </span>
          @endif    
        </td>
        <td class="acciones">
            <!-- Botón editar -->
            <button class="btn btn-edit btn-abrir-editar"
                data-id="{{ $dato['id'] }}"
                data-fecha-inicio="{{ $dato['fecha_inicio'] }}"
                data-fecha-fin="{{ $dato['fecha_fin'] }}"
                data-costo-total="{{ $dato['costo_total'] }}"
                data-estado="{{ $dato['estado'] }}"
                data-trabajador-id="{{ $dato['trabajador']['id'] ?? '' }}"
                data-cliente-id="{{ $dato['cliente']['id'] ?? '' }}"
                data-habitacion-id="{{ $dato['habitaciones'][0]['id'] ?? '' }}"
                data-servicios-extra="{{ collect($dato['servicios_extra'])->pluck('id')->implode(',') }}">
                Editar
            </button>

            <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.reserva') }}" data-id="{{ $dato['id'] }}">PDF</button>

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
      <h1><span>Agregar Reserva</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>
    <form action="{{ route('crear.reserva') }}" method="POST" id="formReserva">
      @csrf
      <div class="campo-form">
        <label>Habitación:</label>
        <select name="id_habitacion" id="selectHabitacion" required>
          <option value="">-- Seleccione una habitación --</option>
          @foreach ($habitaciones as $hab)
              @php
                  $precio = 0;
                  $label = '';
                  $idVal = '';

                  if (is_array($hab)) {
                      $precio = $hab['tipo_habitacion']['precio'] ?? $hab['precio'] ?? 0;
                      $label = $hab['numero_habitacion'] ?? ($hab['id'] ?? '');
                      $idVal = $hab['id'] ?? '';
                  } else {
                      $precio = optional($hab->tipoHabitacion)->precio ?? $hab->precio ?? 0;
                      $label = $hab->numero_habitacion ?? $hab->id;
                      $idVal = $hab->id;
                  }
              @endphp
              <option value="{{ $idVal }}" data-precio="{{ $precio }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="campo-form">
        <label>Fecha inicio:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ \Carbon\Carbon::now(new DateTimeZone('-04:00'))->format('Y-m-d') }}" required>
      </div>

      <div class="campo-form">
        <label>Fecha fin:</label>
        <input type="date" name="fecha_fin" id="fecha_fin" required>
      </div>

      <div class="campo-form">
        <label>Costo total:</label>
        <input type="number" name="costo_total" id="costo_total" step="0.01" required readonly>
      </div>

      <div class="campo-form">
        <label>Estado:</label>
        <input type="number" name="estado" value="1" required>
      </div>

      <div class="campo-form">
        <label>Trabajador:</label>
        <select name="id_trabajador" required>
          <option value="">-- Seleccione un trabajador --</option>
          @foreach ($trabajadores as $trab)
            <option value="{{ $trab['id'] }}">{{ optional($trab->persona)->nombre }} {{ optional($trab->persona)->apellido }}</option>
          @endforeach
        </select>
      </div> 

      <div class="campo-form">
        <label>Cliente:</label>
        <select name="id_cliente" required>
          <option value="">-- Seleccione un cliente --</option>
          @foreach ($clientes as $cliente)
            <option value="{{ $cliente['id'] }}">{{ optional( $cliente->persona)->nombre }} {{ optional( $cliente->persona)->apellido }}</option>
          @endforeach
        </select>
      </div>

      <div class="campo-form">
        <label>Servicios Extras:</label>
        <div class="checkbox-group" id="serviciosExtraCrear">
          @forelse ($serviciosExtras as $servicio)
            <label class="checkbox-item">
              <input type="checkbox" name="servicios_extra[]" value="{{ $servicio->id }}" data-precio="{{ $servicio->precio }}" class="servicio-extra-check">
              {{ $servicio->nombre }} (+{{ $servicio->precio }})
            </label>
          @empty
            <span class="checkbox-empty">No hay servicios extras activos.</span>
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
      <h1><span>Editar Reserva</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.reserva') }}" method="POST" id="formEditar" onsubmit="handleEditSubmit(event)">
      @csrf
      <div class="campo-form">
        <label>Id:</label>
        <input type="text" name="id" id="edit_id" step="RES-00" required readonly>
      </div>

      <div class="campo-form">
        <label>Habitación:</label>
        <select name="id_habitacion" id="edit_habitacion" required>
          @foreach ($habitaciones as $hab)
              @php
                  $precio = 0;
                  $label = '';
                  $idVal = '';

                  if (is_array($hab)) {
                      $precio = $hab['tipo_habitacion']['precio'] ?? $hab['precio'] ?? 0;
                      $label = $hab['numero_habitacion'] ?? ($hab['id'] ?? '');
                      $idVal = $hab['id'] ?? '';
                  } else {
                      $precio = optional($hab->tipoHabitacion)->precio ?? $hab->precio ?? 0;
                      $label = $hab->numero_habitacion ?? $hab->id;
                      $idVal = $hab->id;
                  }
              @endphp
              <option value="{{ $idVal }}" data-precio="{{ $precio }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="campo-form">
        <label>Fecha inicio:</label>
        <input type="date" name="fecha_inicio" id="edit_fecha_inicio" required>
      </div>

      <div class="campo-form">
        <label>Fecha fin:</label>
        <input type="date" name="fecha_fin" id="edit_fecha_fin" required>
      </div>

      <div class="campo-form">
        <label>Estado:</label>
        <input type="number" name="estado" id="edit_estado" required>
      </div>

      <div class="campo-form">
        <label>Costo total:</label>
        <input type="number" name="costo_total" id="edit_costo_total" step="0.01" required>
      </div>

      <div class="campo-form">
        <label>Trabajador:</label>
        <select name="id_trabajador" id="edit_trabajador" required>
          @foreach ($trabajadores as $trab)
            <option value="{{ $trab->id }}">{{ optional($trab->persona)->nombre }} {{ optional($trab->persona)->apellido }}</option>
          @endforeach
        </select>
      </div>

      <div class="campo-form">
        <label>Cliente:</label>
        <select name="id_cliente" id="edit_cliente" required>
          @foreach ($clientes as $cliente)
            <option value="{{ $cliente->id }}">{{ optional($cliente->persona)->nombre }} {{ optional($cliente->persona)->apellido }}</option>
          @endforeach
        </select>
      </div>

      <div class="campo-form">
        <label>Servicios Extras:</label>
        <div class="checkbox-group" id="serviciosExtraEditar">
          @forelse ($serviciosExtras as $servicio)
            <label class="checkbox-item">
              <input type="checkbox" name="servicios_extra[]" value="{{ $servicio->id }}" data-precio="{{ $servicio->precio }}" class="servicio-extra-check-editar">
              {{ $servicio->nombre }} (+{{ $servicio->precio }})
            </label>
          @empty
            <span class="checkbox-empty">No hay servicios extras activos.</span>
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
            <h1><span>Eliminar Reserva</span></h1>
            <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
        </div>
        <br/>
        <span>¿Seguro que desea eliminar esta reserva?</span>

        <form action="{{ route('eliminar.reserva') }}" method="POST">
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
  const selectReserva = document.getElementById('selectHabitacion');
  const selectHabitacionEdit = document.getElementById('edit_habitacion');
  const inputMonto = document.getElementById('costo_total');
  const inputMontoEdit = document.getElementById('edit_costo_total');

  selectReserva.addEventListener('change', function() {
      const precio = this.options[this.selectedIndex].getAttribute('data-precio');
      inputMonto.value = precio ? precio : '';
  });

  selectHabitacionEdit.addEventListener('change', function() {
      const precio = this.options[this.selectedIndex].getAttribute('data-precio');
      inputMontoEdit.value = precio ? precio : '';
  });

  let fechasOcupadas = [];
  const fechaInicio = document.getElementById('fecha_inicio');
  const fechaFin = document.getElementById('fecha_fin');
  const selectHabitacion = document.getElementById('selectHabitacion');
  const costoTotal = document.getElementById('costo_total');

  // Función para calcular las fechas deshabilitadas
  function actualizarFechasDeshabilitadas() {
    const habitacionId = selectHabitacion.value;
    if (!habitacionId) return;

    fetch(`/reserva/fechas-ocupadas/${habitacionId}`)
      .then(response => response.json())
      .then(data => 
      {
        fechasOcupadas = data.fechas_ocupadas;
        const fechaMinima = data.fecha_minima;

        // Actualizar min date en los inputs
        fechaInicio.min = fechaMinima;
        fechaFin.min = fechaMinima;

        // Validar fechas actuales
        validarFecha(fechaInicio);
        validarFecha(fechaFin);
      });
  }

  // Función para validar si una fecha está disponible
  function validarFecha(input) {
    const fecha = input.value;
    if (fechasOcupadas.includes(fecha)) {
      input.setCustomValidity('Esta fecha no está disponible');
      input.reportValidity();
    } 
    else {
      input.setCustomValidity('');
    }
  }

  // Función para calcular el costo total (precio de la habitación por noche + servicios extras elegidos)
  function calcularCostoTotal() {
    if (fechaInicio.value && fechaFin.value && selectHabitacion.value) {
      const inicio = new Date(fechaInicio.value);
      const fin = new Date(fechaFin.value);
      const dias = Math.ceil((fin - inicio) / (1000 * 60 * 60 * 24)) + 1;
      const precioBase = parseFloat(selectHabitacion.selectedOptions[0].dataset.precio);
      const precioServiciosExtra = Array.from(document.querySelectorAll('.servicio-extra-check:checked'))
        .reduce((total, checkbox) => total + parseFloat(checkbox.dataset.precio || 0), 0);

      costoTotal.value = (dias * precioBase + precioServiciosExtra).toFixed(2);
    }
  }

  // Event Listeners
  selectHabitacion.addEventListener('change', actualizarFechasDeshabilitadas);
  fechaInicio.addEventListener('change', function() {
    validarFecha(this);
    fechaFin.min = this.value; // La fecha fin no puede ser anterior a la fecha inicio
    calcularCostoTotal();
  });

  fechaFin.addEventListener('change', function() {
    validarFecha(this);
    calcularCostoTotal();
  });

  document.querySelectorAll('.servicio-extra-check').forEach(checkbox => {
    checkbox.addEventListener('change', calcularCostoTotal);
  });

  // Validación del formulario antes de enviar
  document.getElementById('formReserva').addEventListener('submit', function(e) {
    const inicio = new Date(fechaInicio.value);
    const fin = new Date(fechaFin.value);
      
    if (fin < inicio) {
      e.preventDefault();
      alert('La fecha de fin no puede ser anterior a la fecha de inicio');
    }

    // Verificar si alguna fecha está ocupada
    const fechasSeleccionadas = [];
    for (let d = new Date(inicio); d <= fin; d.setDate(d.getDate() + 1)) {
      fechasSeleccionadas.push(d.toISOString().split('T')[0]);
    }

    const hayFechaOcupada = fechasSeleccionadas.some(fecha => fechasOcupadas.includes(fecha));
    if (hayFechaOcupada) {
      e.preventDefault();
      alert('Una o más fechas seleccionadas no están disponibles');
    }
  });

  /* === Modal Crear === */
  const modalCrear = document.getElementById('modalCrear');
  document.getElementById('abrirModalCrear').addEventListener('click', () => modalCrear.style.display = 'flex');
  document.getElementById('cerrarModalCrear').addEventListener('click', () => modalCrear.style.display = 'none');
  document.getElementById('cancelarModal').addEventListener('click', () => modalCrear.style.display = 'none');    /* === Modal Eliminar === */
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
  const editCosto = document.getElementById('edit_costo_total');
  const editFechaInicio = document.getElementById('edit_fecha_inicio');
  const editFechaFin = document.getElementById('edit_fecha_fin');
  const editEstado = document.getElementById('edit_estado');
  const editTrabajador = document.getElementById('edit_trabajador');
  const editCliente = document.getElementById('edit_cliente');
  const editHabitacion = document.getElementById('edit_habitacion');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
      boton.addEventListener('click', () => {
          // Debug: mostrar valores
          console.log('Fecha inicio:', boton.getAttribute('data-fecha-inicio'));
          console.log('Fecha fin:', boton.getAttribute('data-fecha-fin'));
          
          editId.value = boton.getAttribute('data-id');
          editCosto.value = boton.getAttribute('data-costo-total');
          editFechaInicio.value = boton.getAttribute('data-fecha-inicio');
          editFechaFin.value = boton.getAttribute('data-fecha-fin');
          editEstado.value = boton.getAttribute('data-estado');
          // set selects by id
          editTrabajador.value = boton.getAttribute('data-trabajador-id');
          editCliente.value = boton.getAttribute('data-cliente-id');
          editHabitacion.value = boton.getAttribute('data-habitacion-id');

          const idsServiciosExtra = (boton.getAttribute('data-servicios-extra') || '')
            .split(',')
            .filter(Boolean);
          document.querySelectorAll('.servicio-extra-check-editar').forEach(checkbox => {
            checkbox.checked = idsServiciosExtra.includes(checkbox.value);
          });

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
  /* === Funciones de mensajes === */
  function mostrarMensaje(mensaje, esError = false) {
      const div = document.createElement('div');
      div.style.position = 'fixed';
      div.style.top = '20px';
      div.style.right = '20px';
      div.style.padding = '15px 25px';
      div.style.borderRadius = '5px';
      div.style.zIndex = '1000';
      div.style.backgroundColor = esError ? '#dc3545' : '#00e078';
      div.style.color = 'white';
      div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
      div.textContent = mensaje;
      document.body.appendChild(div);
      
      setTimeout(() => {
          div.remove();
      }, 3000);
  }

  /* === Manejo del formulario de edición === */
  async function handleEditSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const formData = new FormData(form);

      try {
          const response = await fetch('{{ route("editar.reserva") }}', {
              method: 'POST',
              body: formData,
              headers: {
                  'X-Requested-With': 'XMLHttpRequest'
              }
          });

          const data = await response.json();
          
          if (data.success) {
              mostrarMensaje(data.message);
              modalEditar.style.display = 'none';
              // Recargar la página después de un breve delay
              setTimeout(() => {
                  window.location.reload();
              }, 1500);
          } else {
              mostrarMensaje(data.message, true);
          }
      } catch (error) {
          mostrarMensaje('Error al procesar la solicitud', true);
          console.error(error);
      }
  }

  // Asignar el manejador al formulario de edición
  document.getElementById('formEditar').addEventListener('submit', handleEditSubmit);
</script>

@endsection
