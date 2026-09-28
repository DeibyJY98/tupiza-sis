<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExportaPdf;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    use ExportaPdf;

    public function index()
    {
      $datos = Pago::orderByDesc('created_at')->get();
      $datos = $datos->map->toShow();

      // Cargar reservas y clientes para los selects en la vista. $reservas (todas,
      // sin filtrar) se usa en el modal de editar, para que la reserva ya asignada a
      // un pago existente siga apareciendo aunque ya esté totalmente pagada.
      $reservas = Reserva::get();
      $clientes = Cliente::get();

      // Para "Crear Pago": ordenadas de más reciente a más antigua, y sin las que ya
      // tienen pagos completados por el total de la reserva (no queda saldo pendiente
      // que registrar).
      $reservasDisponibles = Reserva::orderByDesc('id')
          ->withSum(['pagos as monto_pagado' => fn ($query) => $query->where('estado', 1)], 'monto')
          ->get()
          ->filter(fn (Reserva $reserva) => (float) ($reserva->monto_pagado ?? 0) < (float) $reserva->costo_total)
          ->values();

      return view("pago.index", compact('datos', 'reservas', 'clientes', 'reservasDisponibles'));
    }

    public function store(Request $request){
      try {
        $request->validate([
          'fecha' => 'required|date',
          'monto' => 'required|numeric|min:0',
          'comprobante' => 'required|image|mimes:jpg,jpeg,png|max:2048',
          'estado' => 'required|numeric',
          'id_cliente' => 'required|exists:clientes,id',
          'id_reserva' => 'required|exists:reservas,id',
        ], $this->rules);

        $estadoNuevo = (int) $request->input('estado', 1);

        // El comprobante se guarda antes de abrir la transacción: es I/O de
        // archivos, no algo que deba revertirse junto con la fila de la BD.
        $ubicacion = null;
        if ($request->file('comprobante')) {
          $nombre ="RES-" . $request->id_reserva . "-" . time() . ".jpg";
          $ubicacion = "storage/" . $request->file('comprobante')->storeAs('comprobante', $nombre, 'public');
        }

        // P1.2: bloquea la reserva hasta el commit para que dos pagos concurrentes
        // sobre la misma reserva no puedan validar el saldo pendiente al mismo
        // tiempo y, juntos, superarlo.
        DB::transaction(function () use ($request, $ubicacion, $estadoNuevo) {
          Reserva::whereKey($request->id_reserva)->lockForUpdate()->firstOrFail();

          if ($estadoNuevo === 1) {
            $this->validarSaldoPendiente($request->id_reserva, $request->monto);
          }

          $nuevo = Pago::create([
            "fecha" => $request->fecha,
            "monto" => $request->monto,
            "comprobante" => $ubicacion,
            "id_reserva" => $request->id_reserva,
            "id_cliente" => $request->id_cliente,
            "estado" => $estadoNuevo,
          ]);

          if ($estadoNuevo === 1) {
            $this->confirmarReservaSiSaldoCubierto($nuevo->id_reserva);
          }
        });
      }
      catch(ValidationException $e){
        $mensajes = collect($e->errors())->flatten()->join(' ');
        return back()->with('error', $mensajes);
      }
      catch (\Exception $e) {
        Log::error('Error al registrar el pago: ' . $e->getMessage(), ['exception' => $e]);
        return back()->with('error', 'Ocurrió un error al registrar el pago. Intenta nuevamente.');
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

            $idReserva = $request->input('id_reserva', $pago->id_reserva);
            $montoNuevo = $request->input('monto', $pago->monto);
            $estadoNuevo = (int) $request->input('estado', $pago->estado);

            // Manejar subida de comprobante si se proporciona (antes de la transacción)
            $ubicacionNueva = null;
            if ($request->file('comprobante')) {
                $nombre = "RES-" . $request->id_reserva . "-" . time() . ".jpg";
                $ubicacionNueva = "storage/" . $request->file('comprobante')->storeAs('comprobante', $nombre, 'public');
            }

            // P1.2: bloquea la reserva hasta el commit para que dos ediciones de pago
            // concurrentes sobre la misma reserva no puedan superar el saldo juntas.
            DB::transaction(function () use ($request, $pago, $idReserva, $montoNuevo, $estadoNuevo, $ubicacionNueva) {
                Reserva::whereKey($idReserva)->lockForUpdate()->firstOrFail();

                if ($estadoNuevo === 1) {
                    $this->validarSaldoPendiente($idReserva, $montoNuevo, excluirPago: $pago->id);
                }

                $modificar = [
                    'fecha' => $request->input('fecha', $pago->fecha),
                    'monto' => $montoNuevo,
                    'estado' => $estadoNuevo,
                    'id_cliente' => $request->input('id_cliente', $pago->id_cliente),
                    'id_reserva' => $idReserva,
                ];

                if ($ubicacionNueva) {
                    $modificar['comprobante'] = $ubicacionNueva;
                }

                $pago->update($modificar);

                if ($estadoNuevo === 1) {
                    $this->confirmarReservaSiSaldoCubierto($idReserva);
                }
            });
        }
        catch(ValidationException $e){
            $mensajes = collect($e->errors())->flatten()->join(' ');
            return back()->with('error', $mensajes);
        }
        catch (\Exception $e) {
            Log::error('Error al actualizar el pago: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Ocurrió un error al actualizar el pago. Intenta nuevamente.');
        }

        return redirect()->route('mostrar.pago')->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Request $request)
    {   
      //dd($request->all());
        try {
            $pago = Pago::find($request->inputIdEliminar);
            if ($pago) {
                $pago->update(['estado' => 0]);
                return redirect()->route('mostrar.pago')->with('success', 'Pago eliminado correctamente.');
            }
            return redirect()->route('mostrar.pago')->with('error', 'El pago no existe.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar el pago #' . $request->inputIdEliminar . ': ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Ocurrió un error al eliminar el pago. Intenta nuevamente.');
        }
    }

    public function exportarPdf(Request $request)
    {
        $consulta = Pago::with(['cliente.persona', 'reserva']);

        if ($request->filled('ids')) {
            $consulta->whereIn('id', $request->input('ids'));
        }

        $filas = $consulta->get()->map(fn (Pago $pago) => [
            $pago->id,
            $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('d/m/Y') : '',
            $pago->id_reserva ? 'RES-' . $pago->id_reserva : '',
            trim(optional(optional($pago->cliente)->persona)->nombre . ' ' . optional(optional($pago->cliente)->persona)->apellido),
            $pago->monto,
            $pago->estado == 1 ? 'Completado' : 'Cancelado',
        ])->all();

        return $this->generarPdf(
            'Reporte de Pagos',
            ['ID', 'Fecha', 'Reserva', 'Cliente', 'Monto', 'Estado'],
            $filas,
            'pagos.pdf'
        );
    }

    /**
     * Un pago "completado" (estado 1) no puede hacer que la suma de pagos de la
     * reserva supere su costo_total. Se excluyen los pagos "cancelado" del cálculo.
     */
    private function validarSaldoPendiente($idReserva, $monto, $excluirPago = null)
    {
        $reserva = Reserva::findOrFail($idReserva);

        $pagadoQuery = Pago::where('id_reserva', $reserva->id)->where('estado', 1);
        if ($excluirPago) {
            $pagadoQuery->where('id', '!=', $excluirPago);
        }
        $pagado = $pagadoQuery->sum('monto');

        $saldoPendiente = $reserva->costo_total - $pagado;

        if ($monto > $saldoPendiente) {
            throw ValidationException::withMessages([
                'monto' => "El monto ({$monto}) excede el saldo pendiente de la reserva ({$saldoPendiente}).",
            ]);
        }
    }

    /**
     * P1.1: si los pagos "completados" de la reserva ya cubren su costo_total, la
     * marca como 'confirmada'. Antes el estado del pago y el de la reserva no se
     * hablaban entre sí. Solo actúa si la estadía sigue en 'pendiente': no
     * retrocede una reserva que ya tiene check-in/check-out, ni una cancelada.
     */
    private function confirmarReservaSiSaldoCubierto($idReserva): void
    {
        $reserva = Reserva::find($idReserva);

        if (!$reserva || $reserva->estado_estadia !== Reserva::ESTADIA_PENDIENTE) {
            return;
        }

        $pagado = Pago::where('id_reserva', $idReserva)->where('estado', 1)->sum('monto');

        if ($pagado >= $reserva->costo_total) {
            $reserva->update(['estado_estadia' => Reserva::ESTADIA_CONFIRMADA]);
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
