<!--<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TupizaSis</title>
  <link rel="shortcut icon" href="/favicon.ico" />
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="{{ asset('css/welcome.css') }}"/>
</head>
<body>
  <header class="header">
    <div class="logo">
      <img src="{{ asset('storage/general/LogoTupiza.png') }}" alt="Logo del sistema" />
    </div>
    <nav>
      <ul>
        <li><a href="#">Inicio</a></li>
        <li><a href="#">Servicios</a></li>
        <li><a href="#">Acerca</a></li>
        <li><a href="#">Contacto</a></li>
      </ul>
    </nav>
    <div class="login-btn">
      <a href="{{ route('login') }}" class="btn">Iniciar Sesión</a>
    </div>
  </header>
    <div class="hero-image"></div>
  <footer>
    <p>© 2025 Sistema de Gestión | Todos los derechos reservados.</p>
  </footer>
  <style>
    .hero-image {
      background-image: url('{{ asset('storage/general/Home.png') }}');
      height: 630px;
      background-size: cover;
      background-position: center;
    }
    /*.header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background-color: black;
      color: #fff;
    }*/
  </style>
</body>
</html>-->


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión - Sistema de Gestión</title>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}" />
</head>
<body>
  <div class="login-container">
    <!-- Sección izquierda (Bienvenida) -->
    <div class="login-welcome">
      <div class="welcome-content">
        <h1>Bienvenido a TupizaSis</h1>
        <p>Administra tus roles, usuarios y reservas desde un solo lugar.</p>
        <img src="{{ asset('storage/general/LogoTupiza.png') }}" alt="Hotel" />
      </div>
    </div>

    <!-- Sección derecha (Formulario) -->
    <div class="login-form">
      <h2>Iniciar Sesión</h2>
      <form action="{{ route('sendLogin') }}" method="POST">
        @csrf

        <div class="form-group">
          <input type="text" name="username" placeholder="Usuario" required />
        </div>

        <div class="form-group">
          <input type="password" name="password" placeholder="Contraseña" required />
        </div>

        @if(session('errorUser'))
            {{ session('errorUser') }}
        @endif

        @if(session('password'))
            {{ session('password') }}
        @endif
        

        <div class="form-options">  
        </div>
        <button type="submit" class="btn">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
