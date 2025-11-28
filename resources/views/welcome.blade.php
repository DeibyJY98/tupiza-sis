<!DOCTYPE html>
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
</html>
