<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Rol;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
  use ExportaPdf;

  public function index(){
    $datos = User::get();       
    $datos = $datos->map->toShow();
      
    return view("user.index",compact('datos'));
  }

  public function store(Request $request){          
    try {
      $request->validate([
        'username' => 'required|string|max:30|unique:users,username',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:5|max:50',
        'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'id_rol' => 'required|exists:rols,id',
        'id_persona' => 'required|exists:personas,id',
      ], $this->rules);

      $ubicacion = null;
      if ($request->file('foto')) {
        $nombre = $request->username . ".jpg";
        $ubicacion = "storage/" . $request->file('foto')->storeAs('fotoUsurio', $nombre, 'public');
      }

      $nuevo = [
        "username" => $request->username,
        "email" => $request->email,
        "password" => bcrypt($request->password),
        "foto" => $ubicacion,
        "id_rol" => $request->id_rol,
        "id_persona" => $request->id_persona,
        // Asignar estado por defecto (1 = activo) si no se proporciona
        "estado" => $request->input('estado', 1),
      ];
        //dd( $nuevo);
        $nuevo = User::create($nuevo);
    } 
    catch(ValidationException $e){
      $mensajes = collect($e->errors())->flatten()->join(' ');
      return back()->with('error', $mensajes);
    }
    catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }

    return redirect()->route('mostrar.usuario');
  }

  public function update(Request $request){
    try {
      $modificar = $request->validate([
        'username' => 'sometimes|string|max:30|unique:users,username',
        'email' => 'sometimes|email|unique:users,email',
        'password' => 'sometimes|string|min:5|max:50',
        'foto' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        'id_rol' => 'sometimes|exists:rols,id',               
        'id_persona' => 'sometimes|exists:personas,id',               
      ], $this->rules);  

      $dato = User::find($request->id);

      // Si se envía password en la actualización, hashearla
      if (isset($modificar['password'])) {
        $modificar['password'] = bcrypt($modificar['password']);
      }

      // Manejar subida de foto en edición si se proporciona
      if ($request->file('foto')) {
        $nombre = ($modificar['username'] ?? $dato->username) . ".jpg";
        $ubicacion = "storage/" . $request->file('foto')->storeAs('fotoUsurio', $nombre, 'public');
        $modificar['foto'] = $ubicacion;
      }

      $dato->update($modificar);
    } 
    catch(ValidationException $e){
      $mensajes = collect($e->errors())->flatten()->join(' ');
      return back()->with('error', $mensajes);
    }
    catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }
    return redirect()->route('mostrar.usuario');
  }

  public function destroy(Request $request){
    $datos= User::find($request->id);
    $datos->update(['estado' => 0]);
    return redirect()->route('mostrar.usuario');
  }

  public function exportarPdf(Request $request)
  {
    $consulta = User::with('rol');

    if ($request->filled('ids')) {
      $consulta->whereIn('id', $request->input('ids'));
    }

    $filas = $consulta->get()->map(fn (User $user) => [
      $user->id,
      $user->username,
      $user->email,
      optional($user->rol)->nombre,
      $user->estado == 1 ? 'Activo' : 'Inactivo',
    ])->all();

    return $this->generarPdf(
      'Reporte de Usuarios',
      ['ID', 'Username', 'Email', 'Rol', 'Estado'],
      $filas,
      'usuarios.pdf'
    );
  }

  public function indexStore(){
    $roles = Rol::get();
    $personas = Persona::get();

    return view('user.crear',compact('roles','personas'));
  }

  public function indexUpdate(Request $request){
    $dato = User::find($request->id);
    $roles = Rol::get();

    return view('user.editar',compact('dato','roles'));
  }

  private $rules = [
    'username.required' => 'El nombre de usuario es obligatorio.',
    'username.string'   => 'El nombre de usuario debe ser texto válido.',
    'username.max'      => 'El nombre de usuario no puede superar los 30 caracteres.',

    'email.required'    => 'El correo electrónico es obligatorio.',
    'email.email'       => 'Debe ingresar un correo electrónico válido.',
    'email.unique'      => 'Este correo ya está registrado en el sistema.',

    'password.required' => 'La contraseña es obligatoria.',
    'password.string'   => 'La contraseña debe ser una cadena de texto.',
    'password.min'      => 'La contraseña debe tener al menos 5 caracteres.',
    'password.max'      => 'La contraseña no puede tener más de 50 caracteres.',

    'foto.image'        => 'El archivo debe ser una imagen.',
    'foto.mimes'        => 'Solo se permiten imágenes en formato JPG o PNG.',
    'foto.max'          => 'La imagen no puede pesar más de 2 MB.',

    'id_rol.required'   => 'Debe seleccionar un rol.',
    'id_rol.exists'     => 'El rol seleccionado no existe en la base de datos.',

    'id_persona.required'   => 'Debe seleccionar una persona.',
    'id_persona.exists'     => 'La persona seleccionada no existe en la base de datos.',
  ];
}
