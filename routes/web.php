<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\HabitacionController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ServicioExtraController;
use App\Http\Controllers\CaracteristicaController;
use App\Http\Controllers\TipoHabitacionController;
use App\Http\Controllers\NotificacionController;

//** PAGINA INICIAL **//
Route::get('/', [AuthController::class, 'welcome'])->name('welcome');
//** PAGINA LOGIN **//
Route::get("/login", [AuthController::class,'loginView'])->name('login');
Route::post("/login",[AuthController::class,'login'])->name('sendLogin');
//** PAGINA CERRAR **//
Route::get("/logout",[AuthController::class,'logout'])->name('logout');
//** NOTIFICACIONES **//
Route::post("/notificaciones/marcar-leidas", [NotificacionController::class, 'marcarLeidas'])->name('notificaciones.marcar-leidas');
//** PAGINA ROL **//
Route::prefix('/rol')->middleware('rolMiddleware')->group(function(){
    Route::get("/",[RolController::class,'index'])->name('mostrar.rol');
    //crear nuevo rol
    Route::get("/crear",[RolController::class,'indexStore'])->name('index.crear.rol');
    Route::post("/crear",[RolController::class,'store'])->name('crear.rol');

    //modificar rol
    Route::post("/modificar",[RolController::class,'indexUpdate'])->name('index.editar.rol');
    Route::post("/modificarPost",[RolController::class,'update'])->name('editar.rol');

    Route::post("/eliminar",[RolController::class,'destroy'])->name('eliminar.rol');
    Route::post("/pdf",[RolController::class,'exportarPdf'])->name('pdf.rol');
});
//** PAGINA USUARIO **//
Route::prefix('/usuario')->middleware('userMiddleware')->group(function(){
    Route::get("/",[UserController::class,'index'])->name('mostrar.usuario');
    //crear nuevo usuario
    Route::get("/crear",[UserController::class,'indexStore'])->name('index.crear.usuario');
    Route::post("/crear",[UserController::class,'store'])->name('crear.usuario');
    //modificar usuario
    Route::post("/modificar",[UserController::class,'indexUpdate'])->name('index.editar.usuario');
    Route::post("/modificarPost",[UserController::class,'update'])->name('editar.usuario');

    Route::post("/eliminar",[UserController::class,'destroy'])->name('eliminar.usuario');
    Route::post("/pdf",[UserController::class,'exportarPdf'])->name('pdf.usuario');
});
//** PAGINA PERMISOS **//
Route::prefix('/permiso')->middleware('permisoMiddleware')->group(function(){
    Route::get("/",[PermisoController::class,'index'])->name('mostrar.permiso');
    Route::post("/",[PermisoController::class,'store'])->name('crear.permiso');
    Route::post("/editar",[PermisoController::class,'update'])->name('editar.permiso');
    Route::post("/eliminar",[PermisoController::class,'destroy'])->name('eliminar.permiso');
    Route::post("/pdf",[PermisoController::class,'exportarPdf'])->name('pdf.permiso');
});
//** PAGINA HABITACION **//
Route::prefix('/habitacion')->middleware('permiso:habitacion')->group(function(){
    Route::get("/",[HabitacionController::class,'index'])->name('mostrar.habitacion');
    Route::post("/",[HabitacionController::class,'store'])->name('crear.habitacion');
    Route::post("/editar",[HabitacionController::class,'update'])->name('editar.habitacion');
    Route::post("/eliminar",[HabitacionController::class,'destroy'])->name('eliminar.habitacion');
    Route::post("/pdf",[HabitacionController::class,'exportarPdf'])->name('pdf.habitacion');
});
//** PAGINA SERVICIO EXTRA **//
Route::prefix('/servicio_extra')->middleware('permiso:servicio_extra')->group(function(){
    Route::get("/",[ServicioExtraController::class,'index'])->name('mostrar.servicio_extra');
    Route::post("/",[ServicioExtraController::class,'store'])->name('crear.servicio_extra');
    Route::post("/editar",[ServicioExtraController::class,'update'])->name('editar.servicio_extra');
    Route::post("/eliminar",[ServicioExtraController::class,'destroy'])->name('eliminar.servicio_extra');
    Route::post("/pdf",[ServicioExtraController::class,'exportarPdf'])->name('pdf.servicio_extra');
});
//** PAGINA RESERVA **//
Route::prefix('/reserva')->middleware('permiso:reserva')->group(function(){
    Route::get("/",[ReservaController::class,'index'])->name('mostrar.reserva');
    Route::post("/",[ReservaController::class,'store'])->name('crear.reserva');
    Route::post("/editar",[ReservaController::class,'update'])->name('editar.reserva');
    Route::post("/eliminar",[ReservaController::class,'destroy'])->name('eliminar.reserva');
    Route::post("/pdf",[ReservaController::class,'exportarPdf'])->name('pdf.reserva');
    Route::get('/fechas-ocupadas/{habitacion_id}', [ReservaController::class, 'getFechasOcupadas'])->name('reserva.fechas-ocupadas');
});
//** PAGINA PERSONA **//
Route::prefix('/persona')->middleware('permiso:persona')->group(function(){
    Route::get("/",[PersonaController::class,'index'])->name('mostrar.persona');
    Route::post("/",[PersonaController::class,'store'])->name('crear.persona');
    Route::post("/editar",[PersonaController::class,'update'])->name('editar.persona');
    Route::post("/eliminar",[PersonaController::class,'destroy'])->name('eliminar.persona');
    Route::post("/pdf",[PersonaController::class,'exportarPdf'])->name('pdf.persona');
});
//** PAGINA CLIENTE **//
Route::prefix('/cliente')->middleware('permiso:cliente')->group(function(){
    Route::get("/",[ClienteController::class,'index'])->name('mostrar.cliente');
    Route::post("/",[ClienteController::class,'store'])->name('crear.cliente');
    Route::post("/editar",[ClienteController::class,'update'])->name('editar.cliente');
    Route::post("/eliminar",[ClienteController::class,'destroy'])->name('eliminar.cliente');
    Route::post("/pdf",[ClienteController::class,'exportarPdf'])->name('pdf.cliente');
});
//** PAGINA TRABAJADOR **//
Route::prefix('/trabajador')->middleware('permiso:trabajador')->group(function(){
    Route::get("/",[TrabajadorController::class,'index'])->name('mostrar.trabajador');
    Route::post("/",[TrabajadorController::class,'store'])->name('crear.trabajador');
    Route::post("/editar",[TrabajadorController::class,'update'])->name('editar.trabajador');
    Route::post("/eliminar",[TrabajadorController::class,'destroy'])->name('eliminar.trabajador');
    Route::post("/pdf",[TrabajadorController::class,'exportarPdf'])->name('pdf.trabajador');
});
//** PAGINA CARACTERISTICA **//
Route::prefix('/caracteristica')->middleware('permiso:caracteristica')->group(function(){
    Route::get("/",[CaracteristicaController::class,'index'])->name('mostrar.caracteristica');
    Route::post("/",[CaracteristicaController::class,'store'])->name('crear.caracteristica');
    Route::post("/editar",[CaracteristicaController::class,'update'])->name('editar.caracteristica');
    Route::post("/eliminar",[CaracteristicaController::class,'destroy'])->name('eliminar.caracteristica');
    Route::post("/pdf",[CaracteristicaController::class,'exportarPdf'])->name('pdf.caracteristica');
});
//** PAGINA TIPO HABITACION **//
Route::prefix('/tipo_habitacion')->middleware('permiso:tipo_habitacion')->group(function(){
    Route::get("/",[TipoHabitacionController::class,'index'])->name('mostrar.tipo_habitacion');
    Route::post("/",[TipoHabitacionController::class,'store'])->name('crear.tipo_habitacion');
    Route::post("/editar",[TipoHabitacionController::class,'update'])->name('editar.tipo_habitacion');
    Route::post("/eliminar",[TipoHabitacionController::class,'destroy'])->name('eliminar.tipo_habitacion');
    Route::post("/pdf",[TipoHabitacionController::class,'exportarPdf'])->name('pdf.tipo_habitacion');
});
//** PAGINA PAGO **//
Route::prefix('/pago')->middleware('permiso:pago')->group(function(){
    Route::get("/",[PagoController::class,'index'])->name('mostrar.pago');
    Route::post("/",[PagoController::class,'store'])->name('crear.pago');
    Route::post("/editar",[PagoController::class,'update'])->name('editar.pago');
    Route::post("/eliminar",[PagoController::class,'destroy'])->name('eliminar.pago');
    Route::post("/pdf",[PagoController::class,'exportarPdf'])->name('pdf.pago');
});
