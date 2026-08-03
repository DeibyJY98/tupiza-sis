<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RolController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = Rol::get();       
        
        return view("rol.index",compact('datos'));
    }

    public function indexStore(){
        return view('rol.crear');
    } 

    public function indexUpdate(Request $request){

        $dato = Rol::find($request->id);
        
        return view('rol.editar',compact('dato'));
    }

    public function store(Request $request)
    {
       try {
            $request->validate([
                'nombre' => 'required|string|max:15',
            ], [
                'nombre.required' => 'El campo nombre es obligatorio.',
                'nombre.string'   => 'El nombre debe ser una cadena de texto válida.',
                'nombre.max'      => 'El nombre no puede tener más de 15 caracteres.',
            ]);    
            // Asegurar que se establece un estado por defecto si no viene en la request
            $nuevo = [
                'nombre' => $request->nombre,
                'estado' => $request->input('estado', 1),
            ];

            $nuevo = Rol::create($nuevo);
       } 
       catch(ValidationException $e){
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
       }
       catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
       }

        return redirect()->route('mostrar.rol');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                    'nombre' => 'required|string|max:15',
                    'estado' => 'sometimes|integer',
                ], [
                    'nombre.required' => 'El campo nombre es obligatorio.',
                    'nombre.string'   => 'El nombre debe ser una cadena de texto válida.',
                    'nombre.max'      => 'El nombre no puede tener más de 15 caracteres.',
                ]);  

            $dato = Rol::find($request->id);
            $dato->update($modificar);
                } 
       catch(ValidationException $e){
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
       }
       catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
       }
        return redirect()->route('mostrar.rol');
    }

    public function destroy(Request $request)
    {
        $datos= Rol::find($request->id);
        $datos->update(['estado' => 0]);
        return redirect()->route('mostrar.rol');
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Rol::query();

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Rol $rol) => [
            $rol->id,
            $rol->nombre,
            $rol->estado == 1 ? 'Activo' : 'Inactivo',
        ])->all();

        return $this->generarPdf(
            'Reporte de Roles',
            ['ID', 'Nombre', 'Estado'],
            $filas,
            'roles.pdf'
        );
    }
}
