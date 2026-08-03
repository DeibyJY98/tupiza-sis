@extends('layout.navbar')

@section('titulo', 'Usuario')

@section('contenido')

@if (session('autorizacion'))
    <div class="alerta-error">
        {{ session('autorizacion') }}
    </div>
@endif    

<div class="container">
  <div class="header-section">
    <h1>Gestión de Usuarios</h1>    
    <div class="right-buttons">
      <button type="button" class="btn yellow" id="btnExportarPdf" data-ruta-pdf="{{ route('pdf.usuario') }}">📄 PDF</button>
      <button class="btn green" id="abrirModalCrear">Crear usuario</button>
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
      <input type="text" id="busqueda" placeholder="Busca un nombre de Usuario">
    </div>

    <button class="btn blue" onclick="buscar()">Buscar</button>
    <button class="btn red" onclick="limpiar()">Limpiar</button>
  </div>
  <!--TABLA DE REGISTROS -->
  <table>
    <thead>
      <tr class="headerTable">
        <th><span>ID</span></th>
        <th><span>Username</span></th> 
        <th><span>Email</span></th> 
        <th><span>Rol</span></th>
        <th><span>Estado</span></th>
        <th><span>Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      @foreach ($datos as $dato)
      <tr data-id="{{ $dato['id'] }}"
          data-filtro-texto="{{ strtolower($dato['username'].' '.$dato['email'].' '.($dato['rol'] ?? '')) }}"
          data-filtro-estado="{{ $dato['estado'] }}">
        <td>{{ $dato['id'] }}</td>
        <td>{{ $dato['username']}}</td>
        <td>{{ $dato['email'] }}</td>  
        <td>{{ $dato['rol'] }}</td>
        <td>
          @if ($dato['estado'] == 1)
              <span style="color: #22c55e; border: 2px solid #22c55e; background-color: transparent; padding: 5%;">
                Activo
              </span>
            @else
             <span style="color: #ef4444; border: 2px solid #ef4444; background-color: transparent; padding: 5% 8%;">
                inactivo
              </span>
            @endif 
        </td>   
        <td class="acciones">
          <form action="{{ route('index.editar.usuario') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $dato['id'] }}">  
            <button class="btn btn-edit" type="submit">Editar</button>
          </form>
          <button type="button" class="btn btn-pdf" data-ruta-pdf="{{ route('pdf.usuario') }}" data-id="{{ $dato['id'] }}">PDF</button>
          <form action="{{ route('eliminar.usuario') }}" method="POST">
            @csrf
            <input type="hidden" name="id" value="{{ $dato['id'] }}">  
            <button class="btn btn-cancelar" type="submit">Inactivar</button>
          </form>
        </td>
      </tr> 
      @endforeach
    </tbody>
  </table>
</div>

@endsection
