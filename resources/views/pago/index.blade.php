@extends('layout.navbar')

@section('titulo','Gestión de Pagos')

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
    <h1>Gestión de Pagos</h1>
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.pago') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear Pago</button>
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
        <th><span>Fecha</span></th>   
        <th><span>Reserva</span></th>         
        <th><span>Cliente</span></th>         
        <th><span>Cédula</span></th>         
        <th><span>Monto</span></th>   
        <th><span>Estado</span></th>         
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody id="tablaPagos">
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato['id'] }}"
          data-filtro-texto="{{ strtolower(($dato['cliente']['nombre'] ?? '').' '.($dato['cliente']['apellido'] ?? '').' '.($dato['cliente']['cedula'] ?? '').' RES-'.($dato['reserva']['id'] ?? '')) }}"
          data-filtro-estado="{{ $dato['estado'] }}"
          data-filtro-fecha="{{ $dato['fecha'] ?? '' }}">
        <td>{{ $dato['fecha'] ?? '' }}</td>
        <td>RES-{{ $dato['reserva']['id'] ?? '' }}</td>
        <td>{{ isset($dato['cliente']['nombre']) ? $dato['cliente']['nombre'].' '.$dato['cliente']['apellido'] : '' }}</td>
        <td>{{ isset($dato['cliente']['cedula']) ? $dato['cliente']['cedula'] : '' }}</td>
        <td>{{ $dato['reserva']['costo_total'] ?? '' }}</td>
        <td>
          @if ($dato['estado'] == 1)
            <span class="badge-estado activo">Completado</span>
          @else
            <span class="badge-estado inactivo">Cancelado</span>
          @endif    
        </td>
        <td class="acciones">
          <button type="button" class="btn btn-ver btn-abrir-ver"
            data-reserva-id="{{ $dato['reserva']['id'] ?? '' }}"
            data-reserva-fecha-inicio="{{ $dato['reserva']['fecha_inicio'] ?? '' }}"
            data-reserva-fecha-fin="{{ $dato['reserva']['fecha_fin'] ?? '' }}"
            data-reserva-habitaciones="{{ $dato['reserva']['habitaciones'] ?? '' }}"
            data-reserva-costo="{{ $dato['reserva']['costo_total'] ?? '' }}"
            data-reserva-estadia="{{ $dato['reserva']['estado_estadia'] ?? '' }}"
            data-cliente-nombre="{{ isset($dato['cliente']['nombre']) ? $dato['cliente']['nombre'].' '.$dato['cliente']['apellido'] : '' }}"
            data-cliente-cedula="{{ $dato['cliente']['cedula'] ?? '' }}"
            data-fecha="{{ $dato['fecha'] ?? '' }}"
            data-monto="{{ $dato['monto'] ?? '' }}"
            data-estado="{{ $dato['estado'] ?? '' }}"
            data-comprobante="{{ $dato['comprobante'] ? asset($dato['comprobante']) : '' }}">
            Ver
          </button>

          <button class="btn btn-edit btn-abrir-editar"
            data-id="{{ $dato['id'] ?? '' }}"
            data-fecha="{{ $dato['fecha'] ?? '' }}"
            data-monto="{{ $dato['monto'] ?? '' }}"
            data-estado="{{ $dato['estado'] ?? '' }}"
            data-reserva-id="{{ $dato['reserva']['id'] ?? '' }}"
            data-cliente-id="{{ isset($dato['cliente']['id']) ? $dato['cliente']['id'] : '' }}"
            data-comprobante="{{ $dato['comprobante'] ? asset($dato['comprobante']) : '' }}">
            Editar
          </button>

          <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.pago') }}" data-id="{{ $dato['id'] }}">PDF</button>

          <button class="btn btn-cancelar btn-abrir-eliminar"
            data-id-eliminar="{{ $dato['id'] ?? '' }}">
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
  <div class="modal-contenido modal-pago-2col">
    <div class="modal-header">
      <h1><span>Agregar Pago</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('crear.pago') }}" method="POST" id="formPago" enctype="multipart/form-data">
      @csrf
      <div class="pago-columnas">
        <div class="pago-detalles">
          <div class="campo-form">
            <label>Reserva:</label>
            <select name="id_reserva" id="selectReserva" required>
              <option value="">-- Seleccione una Reserva --</option>
              @foreach ($reservasDisponibles as $res)
                <option value="{{ $res->id }}" data-precio="{{ $res->costo_total ?? 0 }}" data-cliente="{{ $res->id_cliente }}">RES-{{ $res->id }}</option>
              @endforeach
            </select>
          </div>

          <div class="campo-form">
            <label>Fecha Pago</label>
            <input type="date" name="fecha" id="fecha" value="{{ \Carbon\Carbon::now(new DateTimeZone('-04:00'))->format('Y-m-d') }}" required>
          </div>

          <div class="campo-form">
            <label>Monto:</label>
            <input type="number" name="monto" id="monto" readonly required>
          </div>

          <div class="form-group">
            <label for="comprobante">Comprobante:</label>
            <input type="file" id="comprobante" name="comprobante" accept="image/*" required>
          </div>

          <div class="campo-form">
            <label>Estado:</label>
            <input type="number" name="estado" value="1" required>
          </div>

          <div class="campo-form">
            <label>Cliente:</label>
            <select name="id_cliente" id="selectCliente" required>
              <option value="">-- Seleccione un cliente --</option>
              @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}">{{ optional($cliente->persona)->nombre }} {{ optional($cliente->persona)->apellido }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="pago-comprobante">
          <label>Vista previa del comprobante:</label>
          <img id="previewComprobanteCrear" alt="Vista previa del comprobante" style="display:none;">
          <span id="previewComprobanteCrearVacio" class="dashboard-list-empty">Selecciona un archivo para ver la vista previa.</span>
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
  <div class="modal-contenido modal-pago-2col">
    <div class="modal-header">
      <h1><span>Editar Pago</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.pago') }}" method="POST" id="formEditar" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="id" id="edit_id">

      <div class="pago-columnas">
        <div class="pago-detalles">
          <div class="campo-form">
            <label>Reserva:</label>
            <select name="id_reserva" id="edit_reserva" required>
              <option value="">-- Seleccione una Reserva --</option>
                @foreach ($reservas as $res)
                  <option value="{{ $res->id }}" data-precio="{{ $res->costo_total ?? 0 }}">RES-{{ $res->id }}</option>
                @endforeach
            </select>
          </div>

          <div class="campo-form">
            <label>Fecha Pago:</label>
            <input type="date" name="fecha" id="edit_fecha" required>
          </div>

          <div class="campo-form">
            <label>Monto:</label>
            <input type="number" name="monto" id="edit_monto" required>
          </div>

          <div class="form-group">
            <label for="edit_comprobante">Comprobante:</label>
            <input type="file" id="edit_comprobante" name="comprobante" accept="image/*">
            <small style="color: #666; margin-top: 5px; display: block;">Dejar en blanco para mantener el comprobante actual</small>
          </div>

          <div class="campo-form">
            <label>Estado:</label>
            <input type="number" name="estado" id="edit_estado" required>
          </div>

          <div class="campo-form">
            <label>Cliente:</label>
            <select name="id_cliente" id="edit_cliente" required>
              @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}">{{ optional($cliente->persona)->nombre }} {{ optional($cliente->persona)->apellido }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="pago-comprobante">
          <label>Comprobante:</label>
          <img id="previewComprobanteEditar" alt="Comprobante" style="display:none;">
          <span id="previewComprobanteEditarVacio" class="dashboard-list-empty">Sin comprobante adjunto.</span>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancelarM" id="cancelarEditar">Cancelar</button>
        <button type="submit" class="btn-guardar">Guardar Cambios</button>
      </div>
    </form>
  </div>
</div>

{{-- ventana modal ver detalle --}}
<div id="modalVer" class="modal-overlay" style="display:none">
  <div class="modal-contenido modal-pago-2col">
    <div class="modal-header">
      <h1><span>Detalle del Pago</span></h1>
      <button type="button" id="cerrarModalVer" class="btn-cerrar">&times;</button>
    </div>

    <div class="pago-columnas">
      <div class="pago-detalles">
        <div class="campo-form">
          <label>Reserva:</label>
          <span id="ver_reserva"></span>
        </div>
        <div class="campo-form">
          <label>Fechas de la reserva:</label>
          <span id="ver_reserva_fechas"></span>
        </div>
        <div class="campo-form">
          <label>Habitación(es):</label>
          <span id="ver_reserva_habitaciones"></span>
        </div>
        <div class="campo-form">
          <label>Costo total de la reserva:</label>
          <span id="ver_reserva_costo"></span>
        </div>
        <div class="campo-form">
          <label>Estadía:</label>
          <span id="ver_reserva_estadia"></span>
        </div>
        <div class="campo-form">
          <label>Cliente:</label>
          <span id="ver_cliente"></span>
        </div>
        <div class="campo-form">
          <label>Fecha de pago:</label>
          <span id="ver_fecha"></span>
        </div>
        <div class="campo-form">
          <label>Monto pagado:</label>
          <span id="ver_monto"></span>
        </div>
        <div class="campo-form">
          <label>Estado del pago:</label>
          <span id="ver_estado"></span>
        </div>
      </div>

      <div class="pago-comprobante">
        <label>Comprobante:</label>
        <a id="ver_comprobante_link" href="#" target="_blank">
          <img id="ver_comprobante_img" src="" alt="Comprobante de pago">
        </a>
        <span id="ver_comprobante_vacio" class="dashboard-list-empty" style="display:none;">Sin comprobante adjunto.</span>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-cancelarM" id="cancelarVer">Cerrar</button>
    </div>
  </div>
</div>

{{-- ventana modal eliminar --}}
<div id="modalEliminar" class="modal-overlay" style="display:none">
  <div class="modal-contenido">
    <div class="modal-header">
      <h1><span>Eliminar Pago</span></h1>
      <button id="cerrarModalEliminar" class="btn-cerrar">&times;</button>
    </div>
    <br/>
    <span>¿Seguro que desea eliminar este pago?</span>

    <form action="{{ route('eliminar.pago') }}" method="POST" id="formEliminar">
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
  /* === Vista previa de comprobante (compartida entre Crear y Editar) ===
     Muestra el archivo que el usuario acaba de seleccionar, antes de subirlo. */
  function previsualizarArchivoSeleccionado(input, img, vacio) {
    const archivo = input.files && input.files[0];
    if (!archivo) return;

    const lector = new FileReader();
    lector.onload = (evento) => {
      img.src = evento.target.result;
      img.style.display = '';
      vacio.style.display = 'none';
    };
    lector.readAsDataURL(archivo);
  }

  /* === Modal Crear === */
  const selectReserva = document.getElementById('selectReserva');
  const inputMonto = document.getElementById('monto');
  const selectCliente = document.getElementById('selectCliente');
  const inputComprobanteCrear = document.getElementById('comprobante');
  const previewComprobanteCrear = document.getElementById('previewComprobanteCrear');
  const previewComprobanteCrearVacio = document.getElementById('previewComprobanteCrearVacio');

  selectReserva.addEventListener('change', function() {
      const opcion = this.options[this.selectedIndex];
      const precio = opcion.getAttribute('data-precio');
      inputMonto.value = precio ? precio : '';

      const idCliente = opcion.getAttribute('data-cliente');
      selectCliente.value = idCliente || '';
  });

  inputComprobanteCrear.addEventListener('change', function() {
    previsualizarArchivoSeleccionado(this, previewComprobanteCrear, previewComprobanteCrearVacio);
  });

  const modalCrear = document.getElementById('modalCrear');
  const formPago = document.getElementById('formPago');

  document.getElementById('abrirModalCrear').addEventListener('click', () => {
    formPago.reset();
    previewComprobanteCrear.style.display = 'none';
    previewComprobanteCrear.src = '';
    previewComprobanteCrearVacio.style.display = '';
    modalCrear.style.display = 'flex';
  });
  document.getElementById('cerrarModalCrear').addEventListener('click', () => modalCrear.style.display = 'none');
  document.getElementById('cancelarModal').addEventListener('click', () => modalCrear.style.display = 'none');

  /* === Modal Ver detalle === */
  const modalVer = document.getElementById('modalVer');
  const verReserva = document.getElementById('ver_reserva');
  const verReservaFechas = document.getElementById('ver_reserva_fechas');
  const verReservaHabitaciones = document.getElementById('ver_reserva_habitaciones');
  const verReservaCosto = document.getElementById('ver_reserva_costo');
  const verReservaEstadia = document.getElementById('ver_reserva_estadia');
  const verCliente = document.getElementById('ver_cliente');
  const verFecha = document.getElementById('ver_fecha');
  const verMonto = document.getElementById('ver_monto');
  const verEstado = document.getElementById('ver_estado');
  const verComprobanteLink = document.getElementById('ver_comprobante_link');
  const verComprobanteImg = document.getElementById('ver_comprobante_img');
  const verComprobanteVacio = document.getElementById('ver_comprobante_vacio');

  const ETIQUETAS_ESTADIA = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    check_in: 'Check-in',
    check_out: 'Check-out',
  };

  document.querySelectorAll('.btn-abrir-ver').forEach(boton => {
    boton.addEventListener('click', () => {
      const idReserva = boton.getAttribute('data-reserva-id');
      verReserva.textContent = idReserva ? `RES-${idReserva}` : '—';
      verReservaFechas.textContent = (boton.getAttribute('data-reserva-fecha-inicio') || '—')
        + ' al ' + (boton.getAttribute('data-reserva-fecha-fin') || '—');
      verReservaHabitaciones.textContent = boton.getAttribute('data-reserva-habitaciones') || '—';
      verReservaCosto.textContent = boton.getAttribute('data-reserva-costo') || '—';
      verReservaEstadia.textContent = ETIQUETAS_ESTADIA[boton.getAttribute('data-reserva-estadia')] || '—';
      verCliente.textContent = boton.getAttribute('data-cliente-nombre')
        ? `${boton.getAttribute('data-cliente-nombre')} (${boton.getAttribute('data-cliente-cedula') || 's/n'})`
        : '—';
      verFecha.textContent = boton.getAttribute('data-fecha') || '—';
      verMonto.textContent = boton.getAttribute('data-monto') || '—';
      verEstado.textContent = boton.getAttribute('data-estado') === '1' ? 'Completado' : 'Cancelado';

      const comprobante = boton.getAttribute('data-comprobante');
      if (comprobante) {
        verComprobanteImg.src = comprobante;
        verComprobanteLink.href = comprobante;
        verComprobanteLink.style.display = '';
        verComprobanteVacio.style.display = 'none';
      } else {
        verComprobanteLink.style.display = 'none';
        verComprobanteVacio.style.display = '';
      }

      modalVer.style.display = 'flex';
    });
  });

  document.getElementById('cerrarModalVer').addEventListener('click', () => modalVer.style.display = 'none');
  document.getElementById('cancelarVer').addEventListener('click', () => modalVer.style.display = 'none');

  /* === Modal Editar === */
  const modalEditar = document.getElementById('modalEditar');
  const editId = document.getElementById('edit_id');
  const editFecha = document.getElementById('edit_fecha');
  const editMonto = document.getElementById('edit_monto');
  const editEstado = document.getElementById('edit_estado');
  const editReserva = document.getElementById('edit_reserva');
  const editCliente = document.getElementById('edit_cliente');
  const editComprobante = document.getElementById('edit_comprobante');
  const previewComprobanteEditar = document.getElementById('previewComprobanteEditar');
  const previewComprobanteEditarVacio = document.getElementById('previewComprobanteEditarVacio');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
    boton.addEventListener('click', () => {
      editId.value = boton.getAttribute('data-id');
      editFecha.value = boton.getAttribute('data-fecha');
      editMonto.value = boton.getAttribute('data-monto');
      editEstado.value = boton.getAttribute('data-estado');
      editReserva.value = boton.getAttribute('data-reserva-id');
      editCliente.value = boton.getAttribute('data-cliente-id');

      // Recupera la imagen ya asociada al pago; si se elige un archivo nuevo
      // (listener de abajo), la vista previa se reemplaza por la del nuevo.
      editComprobante.value = '';
      const comprobanteActual = boton.getAttribute('data-comprobante');
      if (comprobanteActual) {
        previewComprobanteEditar.src = comprobanteActual;
        previewComprobanteEditar.style.display = '';
        previewComprobanteEditarVacio.style.display = 'none';
      } else {
        previewComprobanteEditar.style.display = 'none';
        previewComprobanteEditarVacio.style.display = '';
      }

      modalEditar.style.display = 'flex';
    });
  });

  editComprobante.addEventListener('change', function() {
    previsualizarArchivoSeleccionado(this, previewComprobanteEditar, previewComprobanteEditarVacio);
  });

  document.getElementById('cerrarModalEditar').addEventListener('click', () => modalEditar.style.display = 'none');
  document.getElementById('cancelarEditar').addEventListener('click', () => modalEditar.style.display = 'none');

  /* === Modal Eliminar === */
  const modalEliminar = document.getElementById('modalEliminar');
  const inputIdEliminar = document.getElementById('inputIdEliminar');

  document.querySelectorAll('.btn-abrir-eliminar').forEach(boton => {
    boton.addEventListener('click', () => {
      const id = boton.getAttribute('data-id-eliminar') || boton.dataset.idEliminar || null;
      console.log('Abrir modal Eliminar - data-id-eliminar:', id);
      inputIdEliminar.value = id;
      // También actualizar el atributo value para asegurar que el DOM refleja el cambio
      inputIdEliminar.setAttribute('value', id);
      modalEliminar.style.display = 'flex';
    });
  });

  // Listener para ver el valor justo antes de enviar el formulario
  const formEliminar = document.getElementById('formEliminar');
  if (formEliminar) {
    formEliminar.addEventListener('submit', (e) => {
      console.log('Enviando formulario eliminar - inputIdEliminar.value =', inputIdEliminar.value);
    });
  }

  document.getElementById('cerrarModalEliminar').addEventListener('click', () => modalEliminar.style.display = 'none');
  document.getElementById('cancelarEliminar').addEventListener('click', () => modalEliminar.style.display = 'none');

</script>
@endsection
