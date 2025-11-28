@extends('layout.navbar')

@section('titulo', 'roles')

@section('contenido') 
<link rel="stylesheet" href="{{ asset('css/crear.css') }}">


<div class="form-container">
    <h2>Agregar Usuario</h2>

    @if (session('error'))
        <div class="alerta-error">
            <span class="block">{{ session('error') }}</span>
        </div>
    @endif   

    <form action="{{ route('crear.usuario') }}" method="POST"  enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="id_persona">Persona:</label>
            <select name="id_persona" id="selectPersona" required>
                <option value="">-- Seleccione una persona --</option>
                @foreach ($personas as $persona)
                    <option value="{{ $persona->id }}" data-correo="{{ $persona->correo }}">{{ $persona->nombre . ' ' . $persona->apellido }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="email">Correo:</label>
            <input type="email" id="email" name="email" value="{{ optional($personas->first())->correo ?? '' }}" required>
        </div>

        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="foto">Foto:</label>
            <input type="file" id="foto" name="foto" required>
        </div>

        <div class="form-group">
            <label for="estado">Estado:</label>
            <input type="text" id="estado" name="estado" value="1" required>
        </div>

        <div class="form-group">
            <label for="id_rol">Id Rol:</label>
            <select name="id_rol" id="id_rol">
                @foreach ($roles as  $rol)
                    <option value={{ $rol->id }}>{{$rol->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-guardar">Guardar</button>
            <a href="{{ route('mostrar.usuario') }}" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</div>

<script>
    // Autocompletar correo al seleccionar una persona
    (function(){
        const select = document.getElementById('selectPersona');
        const emailInput = document.getElementById('email');

        if (!select || !emailInput) return;

        select.addEventListener('change', function(){
            const opt = select.selectedOptions[0];
            const correo = opt ? opt.dataset.correo || '' : '';
            emailInput.value = correo;
        });
    })();
</script>

<style>
    body {
        background-color: #f7f9fc;
    }

    .form-container {        
        max-width: 450px;
        margin: 3rem auto;
        background-color: #fff;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.4s ease-in-out;
    }
 
    .form-container h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #333;
        font-size: 1.6rem;
        font-weight: 600;
    }

    .alerta-error {
        background-color: #ffeaea;
        border-left: 4px solid #e74c3c;
        color: #c0392b;
        padding: 0.75rem 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        text-align: center;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.4rem;
        font-weight: 500;
        color: #555;
    }

    .form-group input {
        width: 100%;
        padding: 0.6rem;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-group input:focus {
        border-color: #2e86de;
        box-shadow: 0 0 5px rgba(46, 134, 222, 0.3);
        outline: none;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
    }

    .btn-guardar, .btn-cancelar {
        display: inline-block;
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: transform 0.2s, background-color 0.3s;
    }

    .btn-guardar {
        background-color: #27ae60;
        color: white;
        border: none;
        cursor: pointer;
    }

    .btn-guardar:hover {
        background-color: #1e874b;
        transform: scale(1.05);
    }

    .btn-cancelar {
        background-color: #e74c3c;
        color: white;
    }

    .btn-cancelar:hover {
        background-color: #c0392b;
        transform: scale(1.05);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection
