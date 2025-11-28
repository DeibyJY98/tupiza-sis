@extends('layout.navbar')

@section('titulo', 'permisos')

@section('contenido')
  <div class="container">
    <div class="header-section">
      <h1>Gestión de Permisos</h1>
      <div class="right-buttons">
        <button class="btn yellow">📄 PDF</button>
        <button class="btn green" id="abrirModalCrear">Crear Permiso</button>
      </div>
    </div>
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
          <option value="completado">Completado</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </div>

      <div class="input-group full">
        <input type="text" id="busqueda" placeholder="Busca un nombre de Cliente">
      </div>

      <button class="btn blue" onclick="buscar()">Buscar</button>
      <button class="btn red" onclick="limpiar()">Limpiar</button>

    </div>
    <table>
      <thead>
        <tr class="headerTable">
          <th><span>ID</span></th>
          <th><span>Nombre del Permiso</span></th>
          <th><span>Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($datos as $dato)
        <tr>
          <td>{{ $dato->id }}</td>
          <td>{{ $dato->nombre }}</td>
          <td class="acciones">
            <form action="{{ route('editar.permiso') }}" method="POST">
              @csrf
              <input type="hidden" name="id" value="{{ $dato->id }}">
              <button class="btn btn-edit" type="submit">Editar</button>
            </form>

            <form action="{{ route('mostrar.pago') }}" method="GET">
              @csrf
              <input type="hidden" name="id" value="{{ $dato['id'] ?? '' }}">  
              <button class="btn btn-pdf" type="submit">PDF</button>
            </form>

            <form action="{{ route('eliminar.permiso') }}" method="POST">
              @csrf
              <input type="hidden" name="id" value="{{ $dato->id }}">
              <button class="btn btn-cancelar" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
