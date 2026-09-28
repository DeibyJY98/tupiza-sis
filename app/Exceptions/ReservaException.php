<?php

namespace App\Exceptions;

use Exception;

/**
 * Errores de negocio esperables al registrar/editar una reserva (fechas
 * inválidas, habitación no disponible, etc.). Su mensaje es seguro para
 * mostrar directamente al usuario, a diferencia de una excepción genérica
 * que podría filtrar detalles internos (SQL, stack trace).
 */
class ReservaException extends Exception
{
}
