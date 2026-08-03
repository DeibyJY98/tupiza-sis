<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Dashboard - TupizaSis</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">
  <link rel="stylesheet" href="{{ asset('css/generalTable.css') }}">
  <link rel="stylesheet" href="{{ asset('css/generalModals.css') }}">
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <h2>TUPIZASIS</h2>
      <img src="{{ asset('storage/general/IcoTupiza.png')}}" alt="">
    </div>
    @php
        $usuario = Auth::guard(session('auth_guard'))->user();
        $notificaciones = \App\Models\Notificacion::latest()->take(10)->get();
        $notificacionesNoLeidas = \App\Models\Notificacion::where('leida', false)->count();
    @endphp
    <div class="user-info">
      <img src="{{asset('storage/general/user-avatar.png')}}" alt="User" class="user-avatar" />
      <div>
        <p class="user-name">{{ optional($usuario->persona)->nombre . ' ' . optional($usuario->persona)->apellido }}</p>
        <p class="user-role">{{ optional($usuario->rol)->nombre }}</p>
      </div>
    </div>

    <nav>
      <h3 class="section-title">Navegación</h3>
      <ul class="nav-list">
        <!--<li class="active">
          <i>🏠</i> Dashboard
        </li>-->
        <li><a href="{{ route('mostrar.persona') }}"><i>👥</i> Personas </a></li>
        <li><a href="{{ route('mostrar.cliente')}}"><i>🧍</i> Clientes </a></li>
        <li><a href="{{ route('mostrar.trabajador')}}"><i>🧑‍💼</i> Trabajadores </a></li>
        <li><a href="{{ route('mostrar.habitacion') }}"><i>🏨</i> Habitaciones </a></li>
        <li><a href="{{ route('mostrar.tipo_habitacion') }}"><i>🛏️</i> Tipos de Habitación </a></li>
        <li><a href="{{ route('mostrar.caracteristica') }}"><i>🏷️</i> Características </a></li>
        <li><a href="{{ route('mostrar.reserva') }}"><i>📅</i> Reservas </a></li>
        <li><a href="{{ route('mostrar.servicio_extra') }}"><i>🛎️</i> Servicios Extras </a></li>
        <li><a href="{{ route('mostrar.pago') }}"><i>💳</i> Pagos </a></li>
      </ul>

      <h3 class="section-title">Administrador</h3>
      <ul class="nav-list">
        <li><a href="{{route('mostrar.usuario')}}"><i>👤</i> Usuarios </a></li>
        <li><a href="{{route('mostrar.rol')}}"><i>⚙️</i> Roles </a></li>
        <li><a href="{{route('mostrar.permiso')}}"><i>🔐</i> Permisos </a></li>
      </ul>
    </nav>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="main-content">
    <header class="top-bar">
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú">☰</button>
      <div class="notifications" id="notificacionesToggle">
        🔔
        @if ($notificacionesNoLeidas > 0)
          <span class="badge">{{ $notificacionesNoLeidas > 9 ? '9+' : $notificacionesNoLeidas }}</span>
        @endif

        <div class="notifications-panel" id="notificacionesPanel" style="display:none">
          <div class="notifications-header">
            <span>Notificaciones</span>
            @if ($notificacionesNoLeidas > 0)
              <form action="{{ route('notificaciones.marcar-leidas') }}" method="POST">
                @csrf
                <button type="submit" class="btn-marcar-leidas">Marcar todas como leídas</button>
              </form>
            @endif
          </div>
          <ul class="notifications-list">
            @forelse ($notificaciones as $notificacion)
              <li class="{{ $notificacion->leida ? 'leida' : 'no-leida' }}">
                <p>{{ $notificacion->mensaje }}</p>
                <span class="notifications-time">{{ $notificacion->created_at->diffForHumans() }}</span>
              </li>
            @empty
              <li class="notifications-empty">Sin notificaciones por ahora.</li>
            @endforelse
          </ul>
        </div>
      </div>
      <div class="user-header">
        <a href="{{ route('logout') }}">
          <img src="{{asset('storage/general/user-avatar.png')}}" alt="User" class="user-avatar" />
          <div>
            <p>{{ optional($usuario->persona)->nombre . ' ' . optional($usuario->persona)->apellido }}</p>
            <p class="user-role">Cerrar sesión</p>
          </div>
        </a>
      </div>
    </header>

    <!--<section class="dashboard">
      <div class="chart-card">
        <h3>Categorías con habitaciones</h3>
        <canvas id="chartHabitaciones"></canvas>
      </div>

      <div class="chart-card">
        <h3>Personas registradas</h3>
        <canvas id="chartPersonas"></canvas>
      </div>
    </section>-->

    <section class="hero">
      <div class="hero-content">
          @yield('contenido')
      </div>
    </section>

  </div>

  <script src="{{ asset('js/navbar.js')}}" defer></script>
</body>
</html>
