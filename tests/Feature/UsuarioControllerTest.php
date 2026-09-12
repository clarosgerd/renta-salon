<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 8 (11/09/2026) — ver AN-6/§3.11. Mismo patrón que SalonControllerTest.
 */
class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;
    private User $adminNegocio;
    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->negocio = Negocio::first();
        $this->adminNegocio = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_negocio']);
        $this->salon = Salon::factory()->create(['negocio_id' => $this->negocio->id]);
    }

    public function test_cajero_no_puede_acceder_a_usuarios(): void
    {
        $cajero = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero']);

        $response = $this->actingAs($cajero)->get(route('admin.usuarios.index'));

        $response->assertForbidden();
    }

    public function test_admin_salon_no_puede_acceder_a_usuarios(): void
    {
        $adminSalon = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_salon']);

        $response = $this->actingAs($adminSalon)->get(route('admin.usuarios.index'));

        $response->assertForbidden();
    }

    public function test_crea_admin_salon_con_salones_asignados(): void
    {
        $response = $this->actingAs($this->adminNegocio)->post(route('admin.usuarios.store'), [
            'name' => 'Ana Cajera',
            'email' => 'ana@saloneslapaz.test',
            'password' => 'password123',
            'role' => 'admin_salon',
            'salones' => [$this->salon->id],
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $usuario = User::where('email', 'ana@saloneslapaz.test')->first();
        $this->assertNotNull($usuario);
        $this->assertSame($this->negocio->id, $usuario->negocio_id);
        $this->assertNotNull($usuario->email_verified_at);
        $this->assertTrue($usuario->activo);
        $this->assertCount(1, $usuario->salones);
        $this->assertSame($this->salon->id, $usuario->salones->first()->id);
    }

    public function test_crea_admin_salon_sin_salones_falla_validacion(): void
    {
        $response = $this->actingAs($this->adminNegocio)->post(route('admin.usuarios.store'), [
            'name' => 'Sin Salon', 'email' => 'sinsalon@test.com', 'password' => 'password123', 'role' => 'admin_salon',
        ]);

        $response->assertSessionHasErrors('salones');
        $this->assertDatabaseMissing('users', ['email' => 'sinsalon@test.com']);
    }

    public function test_crea_admin_negocio_sin_pivote(): void
    {
        $this->actingAs($this->adminNegocio)->post(route('admin.usuarios.store'), [
            'name' => 'Otro Admin', 'email' => 'otroadmin@test.com', 'password' => 'password123', 'role' => 'admin_negocio',
        ]);

        $usuario = User::where('email', 'otroadmin@test.com')->first();
        $this->assertNotNull($usuario);
        $this->assertCount(0, $usuario->salones);
    }

    public function test_actualizar_de_admin_salon_a_admin_negocio_limpia_el_pivote(): void
    {
        $usuario = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_salon']);
        $usuario->salones()->attach($this->salon->id);
        $this->assertCount(1, $usuario->salones);

        $this->actingAs($this->adminNegocio)->put(route('admin.usuarios.update', $usuario), [
            'name' => $usuario->name, 'email' => $usuario->email, 'role' => 'admin_negocio',
        ]);

        $this->assertCount(0, $usuario->fresh()->salones);
        $this->assertSame('admin_negocio', $usuario->fresh()->role);
    }

    public function test_actualizar_sin_password_no_cambia_el_hash(): void
    {
        $usuario = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'admin_salon', 'password' => Hash::make('original')]);
        $usuario->salones()->attach($this->salon->id);
        $hashOriginal = $usuario->password;

        $this->actingAs($this->adminNegocio)->put(route('admin.usuarios.update', $usuario), [
            'name' => $usuario->name, 'email' => $usuario->email, 'role' => 'admin_salon', 'salones' => [$this->salon->id],
        ]);

        $this->assertSame($hashOriginal, $usuario->fresh()->password);
    }

    public function test_toggle_activo_invierte_el_estado(): void
    {
        $usuario = User::factory()->create(['negocio_id' => $this->negocio->id, 'role' => 'cajero', 'activo' => true]);

        $this->actingAs($this->adminNegocio)->patch(route('admin.usuarios.toggle-activo', $usuario));

        $this->assertFalse($usuario->fresh()->activo);
    }

    public function test_admin_negocio_no_puede_desactivarse_a_si_mismo(): void
    {
        $response = $this->actingAs($this->adminNegocio)->patch(route('admin.usuarios.toggle-activo', $this->adminNegocio));

        $response->assertSessionHas('error');
        $this->assertTrue($this->adminNegocio->fresh()->activo);
    }

    public function test_usuario_desactivado_no_puede_loguearse(): void
    {
        $usuario = User::factory()->create([
            'negocio_id' => $this->negocio->id, 'role' => 'cajero', 'activo' => false,
            'email' => 'inactivo@test.com', 'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', ['email' => 'inactivo@test.com', 'password' => 'password123']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_aislamiento_multi_tenant_entre_negocios(): void
    {
        $otroNegocio = Negocio::factory()->create();
        $usuarioAjeno = User::factory()->create(['negocio_id' => $otroNegocio->id, 'role' => 'cajero', 'name' => 'Ajeno']);

        $response = $this->actingAs($this->adminNegocio)->get(route('admin.usuarios.index'));
        $response->assertOk();
        $response->assertDontSee('Ajeno');

        // Route model binding no filtra por negocio por sí solo (User no
        // tiene NegocioScope) — el 404 manual en el controller es el que
        // protege esto, verificado acá explícitamente.
        $edit = $this->actingAs($this->adminNegocio)->get(route('admin.usuarios.edit', $usuarioAjeno));
        $edit->assertNotFound();
    }
}
