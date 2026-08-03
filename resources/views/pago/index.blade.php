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
            <span style="color: #22c55e; border: 2px solid #22c55e; background-color: transparent; padding: 5%;">
              Completado
            </span>
          @else
            <span style="color: #ef4444; border: 2px solid #ef4444; background-color: transparent; padding: 5% 8%;">
              Cancelado
            </span>
          @endif    
        </td>
        <td class="acciones">
          <button class="btn btn-edit btn-abrir-editar"
            data-id="{{ $dato['id'] ?? '' }}"
            data-fecha="{{ $dato['fecha'] ?? '' }}"
            data-monto="{{ $dato['monto'] ?? '' }}"
            data-estado="{{ $dato['estado'] ?? '' }}"
            data-reserva-id="{{ $dato['reserva']['id'] ?? '' }}"
            data-cliente-id="{{ isset($dato['cliente']['id']) ? $dato['cliente']['id'] : '' }}">
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
  <div class="modal-contenido">
    <div class="modal-header">
      <h1><span>Agregar Pago</span></h1>
      <button id="cerrarModalCrear" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('crear.pago') }}" method="POST" id="formPago" enctype="multipart/form-data">
      @csrf
      <div class="campo-form">
        <label>Reserva:</label>
        <select name="id_reserva" id="selectReserva" required>
          <option value="">-- Seleccione una Reserva --</option>
          @foreach ($reservas as $res)
            <option value="{{ $res->id }}" data-precio="{{ $res->costo_total ?? 0 }}">RES-{{ $res->id }}</option>
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
          <select name="id_cliente" required> 
            <option value="">-- Seleccione un cliente --</option>
            @foreach ($clientes as $cliente)
              <option value="{{ $cliente->id }}">{{ optional($cliente->persona)->nombre }} {{ optional($cliente->persona)->apellido }}</option>
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
<div id="modalEditar" class="modal-overlay" style="display:none">
  <div class="modal-contenido">
    <div class="modal-header">
      <h1><span>Editar Pago</span></h1>
      <button id="cerrarModalEditar" class="btn-cerrar">&times;</button>
    </div>

    <form action="{{ route('editar.pago') }}" method="POST" id="formEditar" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="id" id="edit_id">
        
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
  /* === Modal Crear === */
  const selectReserva = document.getElementById('selectReserva');
  const inputMonto = document.getElementById('monto');

  selectReserva.addEventListener('change', function() {
      const precio = this.options[this.selectedIndex].getAttribute('data-precio');
      inputMonto.value = precio ? precio : '';
  });
  
  const modalCrear = document.getElementById('modalCrear');
  document.getElementById('abrirModalCrear').addEventListener('click', () => modalCrear.style.display = 'flex');
  document.getElementById('cerrarModalCrear').addEventListener('click', () => modalCrear.style.display = 'none');
  document.getElementById('cancelarModal').addEventListener('click', () => modalCrear.style.display = 'none');

  /* === Modal Editar === */
  const modalEditar = document.getElementById('modalEditar');
  const editId = document.getElementById('edit_id');
  const editFecha = document.getElementById('edit_fecha');
  const editMonto = document.getElementById('edit_monto');
  const editEstado = document.getElementById('edit_estado');
  const editReserva = document.getElementById('edit_reserva');
  const editCliente = document.getElementById('edit_cliente');

  document.querySelectorAll('.btn-abrir-editar').forEach(boton => {
    boton.addEventListener('click', () => {
      editId.value = boton.getAttribute('data-id');
      editFecha.value = boton.getAttribute('data-fecha');
      editMonto.value = boton.getAttribute('data-monto');
      editEstado.value = boton.getAttribute('data-estado');
      editReserva.value = boton.getAttribute('data-reserva-id');
      editCliente.value = boton.getAttribute('data-cliente-id');
      modalEditar.style.display = 'flex';
    });
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
