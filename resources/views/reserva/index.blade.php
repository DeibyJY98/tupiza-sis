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
      <select id="estado" class="select-filtro-estado">
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
        <th><span>Estadía</span></th>
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
            <span class="badge-estado activo">Completado</span>
          @else
            <span class="badge-estado inactivo">Cancelado</span>
          @endif
        </td>
        @php $estadia = $dato['estado_estadia'] ?? 'pendiente'; @endphp
        <td>
            <span class="badge-estadia {{ $estadia }}">
                @switch($estadia)
                    @case('confirmada') Confirmada @break
                    @case('check_in') Check-in @break
                    @case('check_out') Check-out @break
                    @default Pendiente
                @endswitch
            </span>
        </td>
        <td class="acciones">
            <!-- Botón ver detalle -->
            <button type="button" class="btn btn-ver btn-abrir-ver-reserva" data-reserva='@json($dato)'>
                Ver
            </button>

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

            @if ($dato['estado'] == 1 && in_array($estadia, ['pendiente', 'confirmada']) && \Carbon\Carbon::parse($dato['fecha_inicio'])->isToday())
                <form action="{{ route('reserva.check-in', $dato['id']) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-edit">Check-in</button>
                </form>
            @endif

            @if ($estadia === 'check_in')
                <form action="{{ route('reserva.check-out', $dato['id']) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-edit">Check-out</button>
                </form>
            @endif

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
  <div class="modal-contenido modal-2col">
    <div class="modal-header">
      <h1><span>Agregar Reserva</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>
    <form action="{{ route('crear.reserva') }}" method="POST" id="formReserva">
      @csrf
      <div class="form-columnas">
        <div class="form-columna">
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
            <input type="date" name="fecha_inicio" id="crear_fecha_inicio" value="{{ \Carbon\Carbon::now(new DateTimeZone('-04:00'))->format('Y-m-d') }}" required>
          </div>

          <div class="campo-form">
            <label>Fecha fin:</label>
            <input type="date" name="fecha_fin" id="crear_fecha_fin" required>
          </div>

          <div class="campo-form">
            <label>Costo total:</label>
            <input type="number" name="costo_total" id="costo_total" step="0.01" required readonly>
          </div>

          <div class="campo-form">
            <label>Estado:</label>
            <input type="number" name="estado" value="1" required>
          </div>
        </div>

        <div class="form-columna">
          <div class="campo-form">
            <label>Trabajador:</label>
            @if ($trabajadorActual)
              <input type="text" value="{{ optional($trabajadorActual->persona)->nombre }} {{ optional($trabajadorActual->persona)->apellido }} (tú)" disabled>
            @else
              <select name="id_trabajador" required>
                <option value="">-- Seleccione un trabajador --</option>
                @foreach ($trabajadores as $trab)
                  <option value="{{ $trab['id'] }}">{{ optional($trab->persona)->nombre }} {{ optional($trab->persona)->apellido }}</option>
                @endforeach
              </select>
            @endif
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
      <h1><span>Editar Reserva</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.reserva') }}" method="POST" id="formEditar" onsubmit="handleEditSubmit(event)">
      @csrf
      <div class="form-columnas">
        <div class="form-columna">
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
            <input type="number" name="costo_total" id="edit_costo_total" step="0.01" required readonly title="Se recalcula automáticamente en el servidor a partir de la habitación, las fechas y los servicios extras">
          </div>
        </div>

        <div class="form-columna">
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

{{-- ventana modal ver detalle --}}
<div id="modalVerReserva" class="modal-overlay" style="display:none">
  <div class="modal-contenido modal-2col">
    <div class="modal-header">
      <h1><span>Detalle de la Reserva</span></h1>
      <button type="button" id="cerrarModalVerReserva" class="btn-cerrar">&times;</button>
    </div>

    <div class="form-columnas">
      <div class="form-columna">
        <div class="campo-form">
          <label>Reserva:</label>
          <span id="verRes_id"></span>
        </div>
        <div class="campo-form">
          <label>Fechas:</label>
          <span id="verRes_fechas"></span>
        </div>
        <div class="campo-form">
          <label>Habitación(es):</label>
          <span id="verRes_habitaciones"></span>
        </div>
        <div class="campo-form">
          <label>Costo total:</label>
          <span id="verRes_costo"></span>
        </div>
        <div class="campo-form">
          <label>Estado:</label>
          <span id="verRes_estado"></span>
        </div>
        <div class="campo-form">
          <label>Estadía:</label>
          <span id="verRes_estadia"></span>
        </div>
        <div class="campo-form">
          <label>Trabajador:</label>
          <span id="verRes_trabajador"></span>
        </div>
        <div class="campo-form">
          <label>Cliente:</label>
          <span id="verRes_cliente"></span>
        </div>
      </div>

      <div class="form-columna">
        <div class="campo-form">
          <label>Servicios Extra:</label>
          <ul class="dashboard-list" id="verRes_servicios"></ul>
        </div>
        <div class="campo-form">
          <label>Pagos:</label>
          <ul class="dashboard-list" id="verRes_pagos"></ul>
          <span>Saldo pendiente: <strong id="verRes_saldo"></strong></span>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-cancelarM" id="cancelarVerReserva">Cerrar</button>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<style>
  .campo-form .flatpickr-wrapper { display: block; width: 100%; }
  .campo-form .flatpickr-wrapper input { width: 100%; box-sizing: border-box; }
  .flatpickr-day.flatpickr-disabled,.flatpickr-day.flatpickr-disabled:hover { color: #bbb; background: #f1f1f1; text-decoration: line-through; }
</style>
<script>
  const selectReserva =document.getElementById('selectHabitacion');
  const selectHabitacionEdit = document.getElementById('edit_habitacion');
  const inputMonto = document.getElementById('costo_total');
  const inputMontoEdit = document.getElementById('edit_costo_total');

  selectReserva.addEventListener('change', function() {
      const precio = this.options[this.selectedIndex].getAttribute('data-precio');
      inputMonto.value = precio ? precio : '';
  });


  let fechasOcupadas = [];
  const fechaInicio = document.getElementById('crear_fecha_inicio');
  const fechaFin = document.getElementById('crear_fecha_fin');
  const selectHabitacion = document.getElementById('selectHabitacion');
  const costoTotal = document.getElementById('costo_total');

  // P2.1: en vez de mostrar siempre todas las habitaciones, cuando ya hay un
  // rango de fechas completo se consulta cuáles están realmente libres para ESE
  // rango (no depende del estado "hoy" de la habitación) y se reemplazan las
  // opciones del select por esa lista.
  function actualizarHabitacionesDisponibles() {
    if (!fechaInicio.value || !fechaFin.value) return;

    fetch(`/reserva/habitaciones-disponibles?desde=${fechaInicio.value}&hasta=${fechaFin.value}`)
      .then(response => response.json())
      .then(data => {
        const valorPrevio = selectHabitacion.value;
        let opciones = '<option value="">-- Seleccione una habitación --</option>';

        (data.habitaciones || []).forEach(habitacion => {
          opciones += `<option value="${habitacion.id}" data-precio="${habitacion.precio}">${habitacion.numero_habitacion}</option>`;
        });

        selectHabitacion.innerHTML = opciones;

        // Si la habitación ya elegida sigue disponible para el nuevo rango, se mantiene.
        if (Array.from(selectHabitacion.options).some(opcion => opcion.value === valorPrevio)) {
          selectHabitacion.value = valorPrevio;
        }

        calcularCostoTotal();
      })
      .catch(() => {}); // si falla la consulta, se deja la lista de habitaciones tal cual estaba
  }

  // Calendarios (flatpickr): un <input type="date"> nativo no permite deshabilitar
  // días sueltos, así que se usa flatpickr para bloquear el pasado y los días ya
  // reservados de la habitación elegida. El valor enviado sigue siendo Y-m-d.
  const HOY = '{{ \Carbon\Carbon::now(new DateTimeZone('-04:00'))->format('Y-m-d') }}';

  const pickerInicio = flatpickr(fechaInicio, {
    locale: 'es',
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    minDate: HOY,
    static: true,
    onChange: function(selectedDates, valor) {
      actualizarLimiteFin(valor);
      actualizarHabitacionesDisponibles();
      calcularCostoTotal();
    }
  });

  const pickerFin = flatpickr(fechaFin, {
    locale: 'es',
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd/m/Y',
    minDate: HOY,
    static: true,
    onChange: function() {
      actualizarHabitacionesDisponibles();
      calcularCostoTotal();
    }
  });

  // La fecha fin no puede ser anterior al inicio ni cruzar una reserva existente:
  // se limita a la primera noche ocupada posterior al inicio (ese día es salida).
  function actualizarLimiteFin(inicio) {
    // La estadía se cobra por noches: la salida es como mínimo el día siguiente al ingreso
    let minimo = HOY;
    if (inicio) {
      const m = new Date(inicio + 'T00:00:00');
      m.setDate(m.getDate() + 1);
      minimo = flatpickr.formatDate(m, 'Y-m-d');
    }
    pickerFin.set('minDate', minimo);

    let maximo = null;
    if (inicio) {
      // La salida puede coincidir con el día en que empieza la siguiente reserva
      maximo = fechasOcupadas.filter(f => f > inicio).sort()[0] || null;
    }
    pickerFin.set('maxDate', maximo);

    if (fechaFin.value && (fechaFin.value < minimo || (maximo && fechaFin.value > maximo))) {
      pickerFin.clear();
    }
  }

  // Consulta los días ocupados de la habitación y los bloquea en ambos calendarios
  function actualizarFechasDeshabilitadas() {
    const habitacionId = selectHabitacion.value;

    if (!habitacionId) {
      fechasOcupadas = [];
      pickerInicio.set('disable', []);
      actualizarLimiteFin(fechaInicio.value);
      return;
    }

    fetch(`/reserva/fechas-ocupadas/${habitacionId}`)
      .then(response => response.json())
      .then(data => {
        fechasOcupadas = data.fechas_ocupadas || [];

        // Solo el ingreso se bloquea en las noches ocupadas; la salida se acota
        // con minDate/maxDate en actualizarLimiteFin()
        pickerInicio.set('disable', fechasOcupadas);

        // Si el ingreso ya elegido cae en una noche ocupada, se limpia
        if (fechasOcupadas.includes(fechaInicio.value)) pickerInicio.clear();

        actualizarLimiteFin(fechaInicio.value);
        calcularCostoTotal();
      })
      .catch(() => {});
  }

  // Función para calcular el costo total (precio de la habitación por noche + servicios extras elegidos)
  function calcularCostoTotal() {
    if (fechaInicio.value && fechaFin.value && selectHabitacion.value) {
      const inicio = new Date(fechaInicio.value);
      const fin = new Date(fechaFin.value);
      // Noches de hospedaje (ingreso 14:00-15:00, salida 11:00-12:00): el día de salida no se cobra
      const dias = Math.max(1, Math.round((fin - inicio) / (1000 * 60 * 60 * 24)));
      const precioBase = parseFloat(selectHabitacion.selectedOptions[0].dataset.precio);
      const precioServiciosExtra = Array.from(document.querySelectorAll('.servicio-extra-check:checked'))
        .reduce((total, checkbox) => total + parseFloat(checkbox.dataset.precio || 0), 0);

      costoTotal.value = (dias * precioBase + precioServiciosExtra).toFixed(2);
    }
  }

  // Event Listeners
  selectHabitacion.addEventListener('change', actualizarFechasDeshabilitadas);
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
    for (let d = new Date(inicio); d < fin; d.setDate(d.getDate() + 1)) {
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
  document.getElementById('cancelarModal').addEventListener('click', () => modalCrear.style.display = 'none');

  /* === Modal Ver detalle === */
  const modalVerReserva = document.getElementById('modalVerReserva');
  const verResId = document.getElementById('verRes_id');
  const verResFechas = document.getElementById('verRes_fechas');
  const verResHabitaciones = document.getElementById('verRes_habitaciones');
  const verResCosto = document.getElementById('verRes_costo');
  const verResEstado = document.getElementById('verRes_estado');
  const verResEstadia = document.getElementById('verRes_estadia');
  const verResTrabajador = document.getElementById('verRes_trabajador');
  const verResCliente = document.getElementById('verRes_cliente');
  const verResServicios = document.getElementById('verRes_servicios');
  const verResPagos = document.getElementById('verRes_pagos');
  const verResSaldo = document.getElementById('verRes_saldo');

  const ETIQUETAS_ESTADIA_RESERVA = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    check_in: 'Check-in',
    check_out: 'Check-out',
  };

  document.querySelectorAll('.btn-abrir-ver-reserva').forEach(boton => {
    boton.addEventListener('click', () => {
      const reserva = JSON.parse(boton.getAttribute('data-reserva'));

      verResId.textContent = `RES-${reserva.id}`;
      verResFechas.textContent = `${reserva.fecha_inicio} al ${reserva.fecha_fin}`;
      verResHabitaciones.textContent = (reserva.habitaciones && reserva.habitaciones.length)
        ? reserva.habitaciones.map(h => `Habitación ${h.numero_habitacion} (Bs. ${h.monto})`).join(', ')
        : '—';
      verResCosto.textContent = `Bs. ${reserva.costo_total}`;
      verResEstado.textContent = reserva.estado == 1 ? 'Completado' : 'Cancelado';
      verResEstadia.textContent = ETIQUETAS_ESTADIA_RESERVA[reserva.estado_estadia] || '—';
      verResTrabajador.textContent = reserva.trabajador
        ? `${reserva.trabajador.nombre} ${reserva.trabajador.apellido}`
        : '—';
      verResCliente.textContent = reserva.cliente
        ? `${reserva.cliente.nombre} ${reserva.cliente.apellido}`
        : '—';

      verResServicios.innerHTML = (reserva.servicios_extra && reserva.servicios_extra.length)
        ? reserva.servicios_extra.map(servicio => (
            `<li><span>${servicio.nombre}</span><span class="dashboard-list-detalle">Bs. ${servicio.precio}</span></li>`
          )).join('')
        : '<li class="dashboard-list-empty">Sin servicios extra.</li>';

      const pagos = reserva.pagos || [];
      verResPagos.innerHTML = pagos.length
        ? pagos.map(pago => (
            `<li><span>${pago.fecha ?? '—'}</span><span class="dashboard-list-detalle">Bs. ${pago.monto} · ${pago.estado == 1 ? 'Completado' : 'Cancelado'}</span></li>`
          )).join('')
        : '<li class="dashboard-list-empty">Sin pagos registrados.</li>';

      const pagado = pagos.filter(p => p.estado == 1).reduce((total, p) => total + parseFloat(p.monto), 0);
      const saldo = parseFloat(reserva.costo_total) - pagado;
      verResSaldo.textContent = `Bs. ${saldo.toFixed(2)}`;

      modalVerReserva.style.display = 'flex';
    });
  });

  document.getElementById('cerrarModalVerReserva').addEventListener('click', () => modalVerReserva.style.display = 'none');
  document.getElementById('cancelarVerReserva').addEventListener('click', () => modalVerReserva.style.display = 'none');

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
  const editCosto = document.getElementById('edit_costo_total');
  const editFechaInicio = document.getElementById('edit_fecha_inicio');
  const editFechaFin = document.getElementById('edit_fecha_fin');
  const editEstado = document.getElementById('edit_estado');
  const editTrabajador = document.getElementById('edit_trabajador');
  const editCliente = document.getElementById('edit_cliente');
  const editHabitacion = document.getElementById('edit_habitacion');

  /* Calendarios del formulario de edición: misma lógica que el de crear (sin fechas
     pasadas, sin noches ya reservadas, salida = mínimo día siguiente al ingreso),
     excluyendo la propia reserva de las noches ocupadas. */
  let editFechasOcupadas = [];
  let editInicioOriginal = '';
  let editFinOriginal = '';

  const sumarDias = (fecha, n) => {
    const d = new Date(fecha + 'T00:00:00');
    d.setDate(d.getDate() + n);
    return flatpickr.formatDate(d, 'Y-m-d');
  };

  const editPickerInicio = flatpickr(editFechaInicio, {
    locale: 'es', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', minDate: HOY, static: true,
    onChange: function(selectedDates, valor) {
      actualizarLimiteFinEdit(valor);
      calcularCostoTotalEdit();
    }
  });

  const editPickerFin = flatpickr(editFechaFin, {
    locale: 'es', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', minDate: HOY, static: true,
    onChange: calcularCostoTotalEdit
  });

  // Costo de referencia (noches x precio de la habitación + servicios extras). El valor
  // definitivo lo recalcula el servidor al guardar con la misma fórmula.
  function calcularCostoTotalEdit() {
    if (!editFechaInicio.value || !editFechaFin.value || !editHabitacion.value) return;

    const noches = Math.max(1, Math.round(
      (new Date(editFechaFin.value) - new Date(editFechaInicio.value)) / (1000 * 60 * 60 * 24)
    ));
    const precioBase = parseFloat(editHabitacion.selectedOptions[0].dataset.precio) || 0;
    const precioServicios = Array.from(document.querySelectorAll('.servicio-extra-check-editar:checked'))
      .reduce((total, checkbox) => total + parseFloat(checkbox.dataset.precio || 0), 0);

    editCosto.value = (noches * precioBase + precioServicios).toFixed(2);
  }

  document.querySelectorAll('.servicio-extra-check-editar').forEach(checkbox => {
    checkbox.addEventListener('change', calcularCostoTotalEdit);
  });

  function actualizarLimiteFinEdit(inicio) {
    let minimo = inicio ? sumarDias(inicio, 1) : HOY;
    if (minimo < HOY) minimo = HOY;
    // Una reserva ya vencida conserva su fecha de salida original aunque sea pasada
    if (editFinOriginal && editFinOriginal < minimo && inicio && editFinOriginal >= sumarDias(inicio, 1)) {
      minimo = editFinOriginal;
    }
    editPickerFin.set('minDate', minimo);

    // La salida puede coincidir con el día en que empieza la siguiente reserva
    const maximo = inicio ? (editFechasOcupadas.filter(f => f > inicio).sort()[0] || null) : null;
    editPickerFin.set('maxDate', maximo);

    if (editFechaFin.value && (editFechaFin.value < minimo || (maximo && editFechaFin.value > maximo))) {
      editPickerFin.clear();
    }
  }

  function cargarFechasOcupadasEdit(idHabitacion, idReserva) {
    if (!idHabitacion) {
      editFechasOcupadas = [];
      editPickerInicio.set('disable', []);
      actualizarLimiteFinEdit(editFechaInicio.value);
      return Promise.resolve();
    }

    return fetch(`/reserva/fechas-ocupadas/${idHabitacion}?excluir=${idReserva}`)
      .then(response => response.json())
      .then(data => {
        editFechasOcupadas = data.fechas_ocupadas || [];
        editPickerInicio.set('disable', editFechasOcupadas);

        if (editFechasOcupadas.includes(editFechaInicio.value)) editPickerInicio.clear();
        actualizarLimiteFinEdit(editFechaInicio.value);
      })
      .catch(() => {});
  }

  editHabitacion.addEventListener('change', () => {
    calcularCostoTotalEdit();
    cargarFechasOcupadasEdit(editHabitacion.value, editId.value).then(calcularCostoTotalEdit);
  });

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
      boton.addEventListener('click', () => {
          editId.value = boton.getAttribute('data-id');
          editCosto.value = boton.getAttribute('data-costo-total');
          editEstado.value = boton.getAttribute('data-estado');
          // set selects by id
          editTrabajador.value = boton.getAttribute('data-trabajador-id');
          editCliente.value = boton.getAttribute('data-cliente-id');
          editHabitacion.value = boton.getAttribute('data-habitacion-id');

          editInicioOriginal = boton.getAttribute('data-fecha-inicio').substring(0, 10);
          editFinOriginal = boton.getAttribute('data-fecha-fin').substring(0, 10);

          // Si la estadía ya empezó, el ingreso no se puede mover (queda bloqueado);
          // si no, solo se permiten fechas desde hoy.
          const yaEmpezo = editInicioOriginal < HOY;
          editPickerInicio.set('minDate', yaEmpezo ? editInicioOriginal : HOY);
          editPickerInicio.set('clickOpens', !yaEmpezo);
          editPickerInicio.setDate(editInicioOriginal, false);
          editPickerFin.setDate(editFinOriginal, false);

          cargarFechasOcupadasEdit(editHabitacion.value, editId.value).then(() => {
            editPickerFin.setDate(editFinOriginal, false);
            // Se muestra el costo con la fórmula vigente (noches), que es el que se guardará
            calcularCostoTotalEdit();
          });

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
      if (e.target === modalVerReserva) modalVerReserva.style.display = 'none';
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
