@extends('layout.navbar')

@section('titulo','roles')

@section('contenido') 
<link rel="stylesheet" href="{{asset('css/crear.css')  }}">

    <div class="form-container">
        <h2>Agregar Rol</h2>

        @if (session('error'))
            <div class="alerta-error">
                <span class="block"> {{ session('error') }}</span>
               
            </div>
        @endif   

        <form action="{{ route('crear.rol') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="nombre">Nombre del Rol:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Nombre del rol" required>
                <label for="nombre">Estado:</label>
                <input type="number" id="estado" name="estado" placeholder="Estado" value="1" required>
            </div>
   
            <div class="form-actions">
                <button type="submit" class="btn-guardar">Guardar</button>
                <a href="{{ route('mostrar.rol') }}" class="btn-cancelar">Cancelar</a>
            </div>
        </form>
    </div>

@endsection
