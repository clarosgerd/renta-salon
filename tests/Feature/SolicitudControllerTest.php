<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Paquete;
use App\Models\Reservacion;
use App\Models\Salon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 6 (11/09/2026) — ver CL-3/§3.14. Reusa ReservacionService::crear()
 * (ya probado en ReservacionControllerTest para el lado admin) — acá se
 * prueba específicamente el camino público: origen/estado correctos,
 * precio server-side, y que no expone datos de otros clientes.
 */
class SolicitudControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private Salon $salon;
    private Paquete $paquete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $this->paquete = Paquete::factory()->create(['negocio_id' => $this->negocio->id, 'precio_base' => 5000, 'duracion_horas' => 6]);
    }

    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'salon_id' => $this->salon->id,
            'paquete_id' => $this->paquete->id,
            'cliente_nombre' => 'Juana Pérez',
            'cliente_telefono' => '70011122',
            'cliente_email' => 'juana@example.com',
            'num_invitados' => 80,
            'fecha_evento' => now()->addDays(30)->toDateString(),
            'hora_inicio' => '14:00',
            'notas' => 'Sin gluten para 3 invitados.',
        ], $overrides);
    }

    public function test_crea_reservacion_pendiente_de_origen_portal(): void
    {
        $response = $this->post(route('portal.solicitud.store'), $this->datosValidos());

        $response->assertOk();
        $response->assertSee('Tu solicitud fue recibida');

        $reservacion = Reservacion::first();
        $this->assertNotNull($reservacion);
        $this->assertSame('pendiente', $reservacion->estado);
        $this->assertSame('portal_publico', $reservacion->origen);
        $this->assertNull($reservacion->creado_por);
        $this->assertSame('14:00', $reservacion->hora_inicio);
        $this->assertSame('20:00', $reservacion->hora_fin); // 14:00 + 6h de duracion_horas del paquete
    }

    public function test_el_precio_lo_decide_el_servidor_no_el_request(): void
    {
        $this->post(route('portal.solicitud.store'), $this->datosValidos(['precio_total' => 1])); // ignorado, ni siquiera está en las rules

        $reservacion = Reservacion::first();
        $this->assertEquals(5000, $reservacion->precio_total); // precio_base del paquete, no el "1" enviado
    }

    public function test_fecha_ya_ocupada_rechaza_sin_crear_nada(): void
    {
        Reservacion::factory()->create([
            'negocio_id' => $this->negocio->id, 'salon_id' => $this->salon->id,
            // ->toDateString() a propósito: fecha_evento castea a 'date'
            // pero Eloquent no trunca la hora al GUARDAR — un Carbon crudo
            // (now()->addDays(...), con hora del momento) queda persistido
            // con tiempo de sobra bajo SQLite (MySQL sí lo trunca solo, por
            // ser columna DATE real) y el WHERE fecha_evento=? de
            // existeConflictoDeHorario() deja de matchear. Mismo criterio
            // que ya usa ReservacionFactory::definition().
            'estado' => 'confirmada', 'fecha_evento' => now()->addDays(30)->toDateString(),
            'hora_inicio' => '17:00', 'hora_fin' => '23:00',
        ]);

        // 15:00 + 6h (duracion_horas del paquete) = 21:00, se solapa con la
        // reservación existente (17:00-23:00) sin cruzar medianoche.
        $response = $this->post(route('portal.solicitud.store'), $this->datosValidos(['hora_inicio' => '15:00']));

        $response->assertSessionHasErrors('fecha_evento');
        $this->assertDatabaseCount('reservaciones', 1); // solo la que ya existía
    }

    public function test_paquete_que_no_aplica_al_salon_elegido_rechaza(): void
    {
        $otroSalon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
        $paqueteExclusivo = Paquete::factory()->create(['negocio_id' => $this->negocio->id]);
        $paqueteExclusivo->salones()->sync([$otroSalon->id]); // NO incluye $this->salon

        $response = $this->post(route('portal.solicitud.store'), $this->datosValidos(['paquete_id' => $paqueteExclusivo->id]));

        $response->assertSessionHasErrors('paquete_id');
        $this->assertDatabaseCount('reservaciones', 0);
    }

    public function test_aislamiento_multi_tenant_no_acepta_salon_de_otro_negocio(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $salonAjeno = Salon::factory()->create(['negocio_id' => $otroNegocio->id]);

        $response = $this->post(route('portal.solicitud.store'), $this->datosValidos(['salon_id' => $salonAjeno->id]));

        $response->assertSessionHasErrors('salon_id');
        $this->assertDatabaseCount('reservaciones', 0);
    }

    public function test_confirmacion_no_expone_otra_reservacion_por_url(): void
    {
        // La confirmación se renderiza directo desde la respuesta del
        // POST — no existe ninguna ruta GET pública que reciba un id y
        // devuelva datos de una reservación ajena.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('portal.solicitud.confirmacion'));
    }
}
