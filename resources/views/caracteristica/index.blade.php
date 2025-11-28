@extends('layout.navbar')

@section('titulo','permisos')

@section('contenido')        
    <main class="content">
    <div class="header-section">
      <h1>Gestión de Permisos</h1>
      <button class="btn-add">+ Agregar Permiso</button>
    </div>

    <table class="roles-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre del Permiso</th>         
          <th>Acciones</th>
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
                    <button class="btn-edit" type="submit">Editar</button>
                </form>

                <form action="{{ route('eliminar.permiso') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" value="{{ $dato->id }}">  
                    <button class="btn-delete" type="submit">Eliminar</button>
                </form>
            </td>
        </tr> 
        @endforeach
      </tbody>
    </table>
  </main>
@endsection
