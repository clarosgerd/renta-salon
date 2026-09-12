<?php

namespace App\Models;

use App\Traits\BelongsToNegocio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservacion extends Model
{
    use BelongsToNegocio, HasFactory;

    // Bug real (10/09/2026): sin esto, Eloquent adivina 'reservacions'
    // (pluralización en inglés de 'reservacion') — la tabla real de la
    // migración es 'reservaciones'. Sin este $table, CUALQUIER query a
    // este modelo fallaba con "Base table or view not found" — el módulo
    // de Reservaciones nunca llegó a funcionar contra la BD real.
    protected $table = 'reservaciones';

    protected $fillable = [
        'negocio_id', 'salon_id', 'paquete_id', 'folio',
        'cliente_nombre', 'cliente_telefono', 'cliente_email', 'num_invitados',
        'fecha_evento', 'hora_inicio', 'hora_fin',
        'precio_total', 'estado', 'origen', 'creado_por', 'notas',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
        'precio_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservacion $reservacion) {
            $reservacion->folio ??= 'RSV-' . strtoupper(Str::random(6));
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function pagosAbonos(): HasMany
    {
        return $this->hasMany(PagoAbono::class);
    }

    public function ventasPos(): HasMany
    {
        return $this->hasMany(VentaPos::class);
    }

    /**
     * Suma solo los abonos con estado_pago = confirmado.
     * Un pago QR "generado" (aún no pagado) NO cuenta para el saldo.
     */
    public function getTotalAbonadoAttribute(): float
    {
        return (float) $this->pagosAbonos()
            ->where('estado_pago', 'confirmado')
            ->sum('monto');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return round($this->precio_total - $this->total_abonado, 2);
    }

    public function getEstaLiquidadaAttribute(): bool
    {
        return $this->saldo_pendiente <= 0;
    }

    /**
     * Regla anti doble-booking. Se usa tanto en el alta manual del admin
     * como en la solicitud del portal público, dentro de una transacción
     * con lockForUpdate para evitar condiciones de carrera.
     *
     * Bug real encontrado en Fase 6 (11/09/2026): `where('fecha_evento', $fecha)`
     * comparaba texto exacto contra una columna casteada 'date' — Eloquent
     * SIEMPRE serializa ese cast como datetime completo ('Y-m-d 00:00:00')
     * al guardar, sin importar si se le asignó una fecha pura. Contra MySQL
     * (columna DATE real) esto "funcionaba" porque el motor trunca la hora
     * en el storage sin que nadie lo pida — contra SQLite (toda la suite de
     * tests) NUNCA matcheaba, así que esta regla anti doble-booking corría
     * en un no-op silencioso bajo tests desde que se escribió. `whereDate()`
     * es agnóstico de motor: extrae la parte de fecha en ambos casos.
     */
    public static function existeConflictoDeHorario(
        int $salonId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $ignorarReservacionId = null
    ): bool {
        return static::query()
            ->where('salon_id', $salonId)
            ->whereDate('fecha_evento', $fecha)
            ->where('estado', '!=', 'cancelada')
            ->when($ignorarReservacionId, fn ($q) => $q->where('id', '!=', $ignorarReservacionId))
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora_inicio', [$horaInicio, $horaFin])
                    ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
                    ->orWhere(function ($q2) use ($horaInicio, $horaFin) {
                        $q2->where('hora_inicio', '<=', $horaInicio)
                            ->where('hora_fin', '>=', $horaFin);
                    });
            })
            ->lockForUpdate()
            ->exists();
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'confirmada');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeDelSalon($query, int $salonId)
    {
        return $query->where('salon_id', $salonId);
    }
}
