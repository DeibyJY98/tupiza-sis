@extends('layout.navbar')

@section('titulo','permisos')

@section('contenido')        
    <main class="container">
    <div class="header-section">
      <h1>Gestión de Servicios Extras</h1>
      <div class="right-buttons">
        <button class="btn yellow">📄 PDF</button>
        <button class="btn green" id="abrirModalCrear">Crear Servicio</button>
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
          <option value="completado">Completado</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </div>
      <div class="input-group full">
        <input type="text" id="busqueda" placeholder="Busca un nombre de servicio">
      </div>

      <button class="btn blue" onclick="buscar()">Buscar</button>
      <button class="btn red" onclick="limpiar()">Limpiar</button>
    </div>
    <!--TABLA DE REGISTROS -->
    <table>
      <thead>
        <tr class="headerTable">
          <th><span>ID</span></th>         
          <th><span>Nombre</span></th>         
          <th><span>Descripcion</span></th>         
          <th><span>Precio</span></th>         
          <th><span>Estado</span></th>         
          <th><span>Acciones</span></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($datos as $dato)
        <tr>
            <td>{{ $dato['id'] }}</td>
            <td>{{ $dato['nombre'] }}</td>
            <td>{{ $dato['descripcion'] }}</td>                   
            <td>{{ $dato['precio'] }}</td>                   
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
                <form action="{{ route('editar.permiso') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ $dato['id'] }}">  
                    <button class="btn btn-edit" type="submit">Editar</button>
                </form>

                <button class="btn btn btn-pdf" type="submit">PDF</button>

                <form action="{{ route('eliminar.permiso') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ $dato['id'] }}">  
                    <button class="btn btn-cancelar" type="submit">Eliminar</button>
                </form>
            </td>
        </tr> 
        @endforeach
      </tbody>
    </table>
  </main>
@endsection
