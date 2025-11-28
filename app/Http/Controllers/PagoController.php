<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PagoController extends Controller
{
    public function index()
    {
      $datos = Pago::get();
      $datos = $datos->map->toShow();

      // Cargar reservas y clientes para los selects en la vista
      $reservas = Reserva::get();
      $clientes = Cliente::get();

      return view("pago.index", compact('datos','reservas','clientes'));
    }

    public function store(Request $request){
      try {
        $request->validate([
          'fecha' => 'required|date',
          'monto' => 'required|numeric|min:0',
          'comprobante' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
          'estado' => 'required|numeric',
          'id_cliente' => 'required|exists:clientes,id',
          'id_reserva' => 'required|exists:reservas,id',
        ], $this->rules);
  
        $ubicacion = null;
        if ($request->file('comprobante')) {
          $nombre ="RES-" . $request->id_reserva . "-" . time() . ".jpg";
          $ubicacion = "storage/" . $request->file('comprobante')->storeAs('comprobante', $nombre, 'public');
        }

        $nuevo = [
          "fecha" => $request->fecha,
          "monto" => $request->monto,
          "comprobante" => $ubicacion,
          "id_reserva" => $request->id_reserva,
          "id_cliente" => $request->id_cliente,
          "estado" => $request->input('estado', 1),
        ];

        $nuevo = Pago::create($nuevo);
      } 
      catch(ValidationException $e){
        $mensajes = collect($e->errors())->flatten()->join(' ');
        return back()->with('error', $mensajes);
      }
      catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
      }

      return redirect()->route('mostrar.pago');
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:pagos,id',
                'fecha' => 'sometimes|date',
                'monto' => 'sometimes|numeric|min:0',
                'comprobante' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
                'estado' => 'sometimes|numeric',
                'id_cliente' => 'sometimes|exists:clientes,id',
                'id_reserva' => 'sometimes|exists:reservas,id',
            ], $this->rules);

            $pago = Pago::find($request->id);
            if (!$pago) {
                return back()->with('error', 'El pago no existe.');
            }

            $modificar = [
                'fecha' => $request->input('fecha', $pago->fecha),
                'monto' => $request->input('monto', $pago->monto),
                'estado' => $request->input('estado', $pago->estado),
                'id_cliente' => $request->input('id_cliente', $pago->id_cliente),
                'id_reserva' => $request->input('id_reserva', $pago->id_reserva),
            ];

            // Manejar subida de comprobante si se proporciona
            if ($request->file('comprobante')) {
                $nombre = "RES-" . $request->id_reserva . "-" . time() . ".jpg";
                $ubicacion = "storage/" . $request->file('comprobante')->storeAs('comprobante', $nombre, 'public');
                $modificar['comprobante'] = $ubicacion;
            }

            $pago->update($modificar);
        } 
        catch(ValidationException $e){
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('mostrar.pago')->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Request $request)
    {   
      //dd($request->all());
        try {
            $pago = Pago::find($request->inputIdEliminar);
            if ($pago) {
                $pago->delete();
                return redirect()->route('mostrar.pago')->with('success', 'Pago eliminado correctamente.');
            }
            return redirect()->route('mostrar.pago')->with('error', 'El pago no existe.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el pago: ' . $e->getMessage());
        }
    }

    private $rules = [
      // FECHA
      'fecha.required'    => 'La fecha es obligatoria.',
      'fecha.date'        => 'Debe ingresar una fecha válida.',

      // MONTO
      'monto.required'    => 'El monto es obligatorio.',
      'monto.numeric'     => 'El monto debe ser numérico.',
      'monto.min'         => 'El monto no puede ser negativo.',

      // COMPROBANTE
      'comprobante.image' => 'El comprobante debe ser una imagen.',
      'comprobante.mimes' => 'El comprobante debe ser un archivo JPG o PNG.',
      'comprobante.max'   => 'El tamaño máximo permitido del comprobante es 2MB.',

      // ESTADO
      'estado.required'   => 'El estado es obligatorio.',
      'estado.numeric'    => 'El estado debe ser un número.',

      // CLIENTE
      'id_cliente.required' => 'Debe seleccionar un cliente.',
      'id_cliente.exists'   => 'El cliente seleccionado no existe.',

      // RESERVA
      'id_reserva.required' => 'Debe seleccionar una reserva.',
      'id_reserva.exists'   => 'La reserva seleccionada no existe.',
    ];

}
