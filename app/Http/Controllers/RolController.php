<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\DetalleRol;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RolController extends Controller
{
    use ExportaPdf;

    public function index()
    {
        $datos = Rol::with(['permisos', 'users.persona'])->get();
        $permisos = Permiso::get();

        return view("rol.index",compact('datos', 'permisos'));
    }

    public function store(Request $request)
    {
       try {
            $request->validate([
                'nombre' => 'required|string|max:15',
                'permisos' => 'sometimes|array',
                'permisos.*' => 'exists:permisos,id',
            ], [
                'nombre.required' => 'El campo nombre es obligatorio.',
                'nombre.string'   => 'El nombre debe ser una cadena de texto válida.',
                'nombre.max'      => 'El nombre no puede tener más de 15 caracteres.',
                'permisos.*.exists' => 'Uno de los permisos seleccionados no existe.',
            ]);
            // Asegurar que se establece un estado por defecto si no viene en la request
            $nuevo = [
                'nombre' => $request->nombre,
                'estado' => $request->input('estado', 1),
            ];

            $nuevo = Rol::create($nuevo);

            foreach ($request->input('permisos', []) as $idPermiso) {
                DetalleRol::create(['id_rol' => $nuevo->id, 'id_permiso' => $idPermiso]);
            }
       }
       catch(ValidationException $e){
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
       }
       catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
       }

        return redirect()->route('mostrar.rol')->with('success', 'Rol registrado correctamente.');
    }

    public function update(Request $request)
    {
        try {
            $modificar = $request->validate([
                    'nombre' => 'required|string|max:15',
                    'estado' => 'sometimes|integer',
                    'permisos' => 'sometimes|array',
                    'permisos.*' => 'exists:permisos,id',
                ], [
                    'nombre.required' => 'El campo nombre es obligatorio.',
                    'nombre.string'   => 'El nombre debe ser una cadena de texto válida.',
                    'nombre.max'      => 'El nombre no puede tener más de 15 caracteres.',
                    'permisos.*.exists' => 'Uno de los permisos seleccionados no existe.',
                ]);

            $dato = Rol::find($request->id);
            $dato->update(collect($modificar)->except('permisos')->all());

            // El selector de permisos siempre se envía completo (aunque esté vacío), así
            // que se sincroniza igual que servicios_extra en reservas: se revocan (soft
            // delete) los que ya no están marcados y se crean los que son nuevos.
            if ($request->has('permisos')) {
                $idsNuevos = collect($request->input('permisos', []))->map(fn ($id) => (int) $id);
                $idsActuales = DetalleRol::where('id_rol', $dato->id)->pluck('id_permiso');

                DetalleRol::where('id_rol', $dato->id)
                    ->whereNotIn('id_permiso', $idsNuevos)
                    ->delete();

                foreach ($idsNuevos->diff($idsActuales) as $idPermiso) {
                    DetalleRol::create(['id_rol' => $dato->id, 'id_permiso' => $idPermiso]);
                }
            }
                }
       catch(ValidationException $e){
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
       }
       catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
       }
        return redirect()->route('mostrar.rol')->with('success', 'Rol actualizado correctamente.');
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
