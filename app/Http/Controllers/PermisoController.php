<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function index()
    {
        $datos = Permiso::get();       
        return view("permiso.index",compact('datos'));
    }

    public function indexUpdate(Request $request){

        $dato = Permiso::find($request->id);
        
        return view('permiso.editar',compact('dato'));
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Permiso $permiso)
    {
        //
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                    'nombre' => 'required|string|max:15'                
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
        return redirect()->route('mostrar.permiso');
    }

    public function destroy(Permiso $permiso)
    {        
        $datos= Rol::find($request->id);
        $datos->delete();
        return redirect()->route('mostrar.permiso');
    }
}
