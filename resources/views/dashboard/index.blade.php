@extends('layout.navbar')

@section('titulo', 'Dashboard')

@section('contenido')

@if (session('error'))
    <div class="alerta-error">{{ session('error') }}</div>
@endif

@if (session('success'))
    <div class="alerta-exito">{{ session('success') }}</div>
@endif

<div class="container">
  <div class="header-section">
    <h1>Dashboard</h1>
  </div>

  <div class="dashboard-stats">
    <div class="stat-card">
      <span class="stat-label">Ocupación de hoy</span>
      <span class="stat-value">{{ $habitacionesOcupadas }}/{{ $totalHabitaciones }}</span>
      <span class="stat-sub">{{ $habitacionesDisponibles }} disponibles</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Llegadas hoy</span>
      <span class="stat-value">{{ $llegadasHoy->count() }}</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Salidas hoy</span>
      <span class="stat-value">{{ $salidasHoy->count() }}</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Ingresos del mes</span>
      <span class="stat-value">Bs. {{ number_format($ingresosMes, 2) }}</span>
    </div>
  </div>

  <div class="dashboard-grid">
    <div class="dashboard-panel">
      <div class="header-section" style="margin-bottom: 10px;">
        <h3 style="margin: 0;">Ocupación de habitaciones</h3>
        <div class="right-buttons">
          <button type="button" class="btn blue" id="btnVerHabitaciones" style="padding: 4px 10px; font-size: 12px;">Ver detalle</button>
        </div>
      </div>
      <canvas id="chartOcupacion" style="cursor: pointer;"></canvas>
    </div>

    <div class="dashboard-panel">
      <h3>Llegadas de hoy</h3>
      <ul class="dashboard-list">
        @forelse ($llegadasHoy as $reserva)
          <li>
            <span>{{ trim(optional(optional($reserva->cliente)->persona)->nombre . ' ' . optional(optional($reserva->cliente)->persona)->apellido) }}</span>
            <span class="dashboard-list-detalle">Habitación {{ optional($reserva->habitaciones->first())->numero_habitacion ?? '—' }}</span>
          </li>
        @empty
          <li class="dashboard-list-empty">Sin llegadas programadas para hoy.</li>
        @endforelse
      </ul>
    </div>

    <div class="dashboard-panel">
      <h3>Salidas de hoy</h3>
      <ul class="dashboard-list">
        @forelse ($salidasHoy as $reserva)
          <li>
            <span>{{ trim(optional(optional($reserva->cliente)->persona)->nombre . ' ' . optional(optional($reserva->cliente)->persona)->apellido) }}</span>
            <span class="dashboard-list-detalle">Habitación {{ optional($reserva->habitaciones->first())->numero_habitacion ?? '—' }}</span>
          </li>
        @empty
          <li class="dashboard-list-empty">Sin salidas programadas para hoy.</li>
        @endforelse
      </ul>
    </div>
  </div>

  <div class="dashboard-panel">
    <div class="header-section" style="justify-content: space-between;">
      <h3>Calendario de ocupación del mes</h3>
      <div class="input-group" style="width: auto; margin-left: auto;">
        <select id="calendarioHabitacion">
          <option value="todas">Todas</option>
          @foreach ($habitaciones as $habitacion)
            <option value="{{ $habitacion->id }}">Habitación {{ $habitacion->numero_habitacion }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div id="calendarioMes" class="calendario-grid"></div>
    <div class="calendario-leyenda">
      <span><i class="calendario-punto disponible"></i> Disponible</span>
      <span><i class="calendario-punto ocupado"></i> Ocupado</span>
      <span><i class="calendario-punto hoy"></i> Hoy</span>
    </div>
  </div>

  <!-- Modal: detalle de habitaciones ocupadas/disponibles -->
  <div id="modalHabitaciones" class="modal-overlay" style="display:none">
    <div class="modal-contenido" style="width: 420px;">
      <div class="modal-header">
        <h1><span>Habitaciones</span></h1>
        <button type="button" id="cerrarModalHabitaciones" class="btn-cerrar">&times;</button>
      </div>
      <div class="modal-footer" style="justify-content: flex-start; margin: 15px 0;">
        <button type="button" class="btn red" id="tabHabitacionesOcupadas" data-tab="ocupadas">Ocupadas ({{ $habitacionesOcupadas }})</button>
        <button type="button" class="btn green" id="tabHabitacionesDisponibles" data-tab="disponibles">Disponibles ({{ $habitacionesDisponibles }})</button>
      </div>
      <ul class="dashboard-list" id="listaHabitacionesOcupadas">
        @forelse ($habitaciones->where('estado', 0) as $habitacion)
          <li>
            <span>Habitación {{ $habitacion->numero_habitacion }}</span>
            <span class="dashboard-list-detalle">{{ optional($habitacion->tipoHabitacion)->nombre ?? '—' }} · Planta {{ $habitacion->planta }}</span>
          </li>
        @empty
          <li class="dashboard-list-empty">No hay habitaciones ocupadas.</li>
        @endforelse
      </ul>
      <ul class="dashboard-list" id="listaHabitacionesDisponibles" style="display:none">
        @forelse ($habitaciones->where('estado', 1) as $habitacion)
          <li>
            <span>Habitación {{ $habitacion->numero_habitacion }}</span>
            <span class="dashboard-list-detalle">{{ optional($habitacion->tipoHabitacion)->nombre ?? '—' }} · Planta {{ $habitacion->planta }}</span>
          </li>
        @empty
          <li class="dashboard-list-empty">No hay habitaciones disponibles.</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>

<script>
  // === Modal de detalle de habitaciones (ocupadas/disponibles) ===
  const modalHabitaciones = document.getElementById('modalHabitaciones');
  const listaHabOcupadas = document.getElementById('listaHabitacionesOcupadas');
  const listaHabDisponibles = document.getElementById('listaHabitacionesDisponibles');
  const tabHabOcupadas = document.getElementById('tabHabitacionesOcupadas');
  const tabHabDisponibles = document.getElementById('tabHabitacionesDisponibles');

  function mostrarTabHabitaciones(tab) {
    const esOcupadas = tab === 'ocupadas';
    listaHabOcupadas.style.display = esOcupadas ? '' : 'none';
    listaHabDisponibles.style.display = esOcupadas ? 'none' : '';
    tabHabOcupadas.style.opacity = esOcupadas ? '1' : '0.5';
    tabHabDisponibles.style.opacity = esOcupadas ? '0.5' : '1';
  }

  function abrirModalHabitaciones(tab) {
    mostrarTabHabitaciones(tab || 'ocupadas');
    modalHabitaciones.style.display = 'flex';
  }

  document.getElementById('btnVerHabitaciones').addEventListener('click', () => abrirModalHabitaciones('ocupadas'));
  document.getElementById('cerrarModalHabitaciones').addEventListener('click', () => modalHabitaciones.style.display = 'none');
  tabHabOcupadas.addEventListener('click', () => mostrarTabHabitaciones('ocupadas'));
  tabHabDisponibles.addEventListener('click', () => mostrarTabHabitaciones('disponibles'));
  window.addEventListener('click', (e) => {
    if (e.target === modalHabitaciones) modalHabitaciones.style.display = 'none';
  });

  const ctxOcupacion = document.getElementById('chartOcupacion');
  if (ctxOcupacion && window.Chart) {
    new Chart(ctxOcupacion, {
      type: 'doughnut',
      data: {
        labels: ['Ocupadas', 'Disponibles'],
        datasets: [{
          data: [{{ $habitacionesOcupadas }}, {{ $habitacionesDisponibles }}],
          backgroundColor: ['#ef4444', '#22c55e'],
          borderColor: '#0f172a',
        }],
      },
      options: {
        onClick: (evento, elementos) => {
          if (elementos.length > 0) {
            abrirModalHabitaciones(elementos[0].index === 0 ? 'ocupadas' : 'disponibles');
          }
        },
        plugins: {
          legend: {
            labels: { color: '#fff' },
            onClick: (evento, item) => abrirModalHabitaciones(item.index === 0 ? 'ocupadas' : 'disponibles'),
          },
        },
      },
    });
  }

  // Calendario mensual: el servidor ya manda TODAS las reservas activas del mes
  // (reservasDelMes), así que "Todas" no necesita pedir nada por separado; el filtro
  // por habitación solo recorta ese mismo dataset en el cliente. Formato de matriz
  // (grilla de 7 columnas, como antes): cada celda es un día del mes y lista, dentro
  // de la misma celda, las habitaciones reservadas ese día (huésped + # de reserva).
  const selectHabitacionCalendario = document.getElementById('calendarioHabitacion');
  const calendarioMes = document.getElementById('calendarioMes');
  const reservasDelMes = @json($reservasDelMes);
  const MAX_RESERVAS_VISIBLES_POR_DIA = 3;

  function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  }

  function pintarCalendario(habitacionId) {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const mes = hoy.getMonth();
    const ultimoDia = new Date(anio, mes + 1, 0).getDate();
    // OJO: toISOString() convierte a UTC, lo que desfasaba el resaltado de "hoy" un
    // día cuando la hora local (Bolivia, UTC-4) ya cruzó medianoche en UTC. Se arma
    // la fecha con los mismos componentes locales que fechaStr para que coincidan.
    const hoyStr = `${anio}-${String(mes + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;

    const reservas = habitacionId === 'todas'
      ? reservasDelMes
      : reservasDelMes.filter(r => String(r.id_habitacion) === String(habitacionId));

    let html = '';
    for (let dia = 1; dia <= ultimoDia; dia++) {
      const fechaStr = `${anio}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
      const reservasDelDia = reservas.filter(r => fechaStr >= r.fecha_inicio && fechaStr <= r.fecha_fin);
      const ocupado = reservasDelDia.length > 0;
      const esHoy = fechaStr === hoyStr;

      const visibles = reservasDelDia.slice(0, MAX_RESERVAS_VISIBLES_POR_DIA);
      const restantes = reservasDelDia.length - visibles.length;

      const items = visibles.map(r => (
        `<div class="calendario-dia-reserva" title="Hab. ${escaparHtml(r.numero_habitacion)} - ${escaparHtml(r.cliente)} #${r.id_reserva}">`
        + `Hab. ${escaparHtml(r.numero_habitacion)} - ${escaparHtml(r.cliente)} #${r.id_reserva}`
        + `</div>`
      )).join('') + (restantes > 0 ? `<div class="calendario-dia-reserva">+${restantes} más</div>` : '');

      html += `<div class="calendario-dia ${ocupado ? 'ocupado' : 'disponible'}${esHoy ? ' hoy' : ''}">`
        + `<span class="calendario-dia-numero">${dia}</span>`
        + `<div class="calendario-dia-reservas">${items}</div>`
        + `</div>`;
    }

    calendarioMes.innerHTML = html;
  }

  if (selectHabitacionCalendario) {
    selectHabitacionCalendario.value = 'todas';
    pintarCalendario('todas');
    selectHabitacionCalendario.addEventListener('change', () => pintarCalendario(selectHabitacionCalendario.value));
  }
</script>

@endsection
