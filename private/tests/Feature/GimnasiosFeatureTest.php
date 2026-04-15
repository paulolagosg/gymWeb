<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GimnasiosFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_gimnasios_table_and_foreign_keys_exist(): void
    {
        $this->assertTrue(Schema::hasTable('gimnasios'));
        $this->assertTrue(Schema::hasColumn('clientes', 'id_gimnasio'));
        $this->assertTrue(Schema::hasColumn('agendas', 'id_gimnasio'));
        $this->assertTrue(Schema::hasColumn('movimientos_financieros', 'id_gimnasio'));
        $this->assertTrue(Schema::hasColumn('planes', 'id_gimnasio'));
        $this->assertTrue(Schema::hasColumn('users', 'id_gimnasio'));

        $this->assertDatabaseHas('gimnasios', [
            'nombre' => 'Gimnasio Ampaya',
            'slug' => 'gimnasio-ampaya',
            'estado' => 1,
        ]);
    }

    public function test_login_stores_current_gym_in_session(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('id_gimnasio_actual', 1);
    }

    public function test_trainer_cannot_see_or_access_admin_modules(): void
    {
        $trainer = User::factory()->create([
            'email' => 'trainer@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
        ]);

        $dashboardResponse = $this->actingAs($trainer)->get('/clientes');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee('Planes');
        $dashboardResponse->assertDontSee('Gimnasios');
        $dashboardResponse->assertDontSee('Usuarios');
        $dashboardResponse->assertDontSee('Caja');
        $dashboardResponse->assertDontSee('Entrenadores');

        $this->actingAs($trainer)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('fa-circle-left fa-2x', false);

        foreach (['/planes', '/gimnasios', '/usuarios', '/movimientos', '/entrenadores'] as $url) {
            $this->actingAs($trainer)
                ->get($url)
                ->assertForbidden()
                ->assertSeeText('No tiene acceso');
        }
    }

    public function test_forms_only_show_clients_and_trainers_from_current_gym(): void
    {
        DB::table('gimnasios')->insert([
            'id' => 2,
            'nombre' => 'Gimnasio Norte',
            'slug' => 'gimnasio-norte',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = User::factory()->create([
            'email' => 'admin1@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        User::factory()->create([
            'name' => 'Entrenador Mismo Gym',
            'email' => 'trainer.same@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
        ]);

        User::factory()->create([
            'name' => 'Entrenador Otro Gym',
            'email' => 'trainer.other@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 2,
        ]);

        DB::table('planes')->insert([
            [
                'id' => 1,
                'nombre' => 'Plan Gym 1',
                'slug' => 'plan-gym-1',
                'valor' => 100,
                'porcentaje' => 0,
                'estado' => 1,
                'id_gimnasio' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'Plan Gym 2',
                'slug' => 'plan-gym-2',
                'valor' => 100,
                'porcentaje' => 0,
                'estado' => 1,
                'id_gimnasio' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('clientes')->insert([
            [
                'ci' => '1111111',
                'nombres' => 'Cliente Mismo Gym',
                'paterno' => 'Uno',
                'materno' => null,
                'telefono' => '123456',
                'id_genero' => 1,
                'id_plan' => 1,
                'email' => 'cliente1@gym.test',
                'direccion' => null,
                'ciudad' => null,
                'fecha_nacimiento' => '1990-01-01',
                'fecha_ingreso' => now()->toDateString(),
                'fecha_pago' => now()->toDateString(),
                'slug' => 'cliente-mismo-gym',
                'estado' => 1,
                'id_usuario' => $admin->id,
                'fecha_fin' => now()->addMonth()->toDateString(),
                'id_gimnasio' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ci' => '2222222',
                'nombres' => 'Cliente Otro Gym',
                'paterno' => 'Dos',
                'materno' => null,
                'telefono' => '123456',
                'id_genero' => 1,
                'id_plan' => 2,
                'email' => 'cliente2@gym.test',
                'direccion' => null,
                'ciudad' => null,
                'fecha_nacimiento' => '1990-01-01',
                'fecha_ingreso' => now()->toDateString(),
                'fecha_pago' => now()->toDateString(),
                'slug' => 'cliente-otro-gym',
                'estado' => 1,
                'id_usuario' => $admin->id,
                'fecha_fin' => now()->addMonth()->toDateString(),
                'id_gimnasio' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $mensajesResponse = $this->actingAs($admin)->get('/mensajes/create');
        $mensajesResponse->assertOk();
        $mensajesResponse->assertSee('Entrenador Mismo Gym');
        $mensajesResponse->assertDontSee('Entrenador Otro Gym');

        $agendasResponse = $this->actingAs($admin)->get('/agendas/create');
        $agendasResponse->assertOk();
        $agendasResponse->assertSee('Cliente Mismo Gym');
        $agendasResponse->assertDontSee('Cliente Otro Gym');
        $agendasResponse->assertSee('Entrenador Mismo Gym');
        $agendasResponse->assertDontSee('Entrenador Otro Gym');

        $this->actingAs($admin)
            ->get('/clientes')
            ->assertOk()
            ->assertSee('Opciones')
            ->assertDontSee('fa-circle-left fa-2x', false);

        $this->actingAs($admin)
            ->get(route('clientes.opciones.portada', 'cliente-mismo-gym'))
            ->assertOk()
            ->assertSee('Cliente Mismo Gym');

        $this->actingAs($admin)
            ->get(route('clientes.edit', 'cliente-mismo-gym'))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee(route('clientes.opciones.portada', 'cliente-mismo-gym'), false);

        $this->actingAs($admin)
            ->get(route('parq.create', 'cliente-mismo-gym'))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee(route('clientes.opciones.portada', 'cliente-mismo-gym'), false);

        $this->actingAs($admin)
            ->get(route('fitplan.create', 'cliente-mismo-gym'))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee(route('clientes.opciones.portada', 'cliente-mismo-gym'), false);
    }

    public function test_client_user_sees_client_portal_menu(): void
    {
        $trainer = User::factory()->create([
            'name' => 'Entrenador Cliente Menu',
            'email' => 'trainer.client.menu@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
            'slug' => 'entrenador-cliente-menu',
        ]);

        DB::table('planes')->insert([
            'id' => 10,
            'nombre' => 'Plan Cliente Menu',
            'slug' => 'plan-cliente-menu',
            'valor' => 100,
            'porcentaje' => 0,
            'estado' => 1,
            'id_gimnasio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'ci' => '3333333',
            'nombres' => 'Cliente Menu',
            'paterno' => 'Tres',
            'materno' => null,
            'telefono' => '123456',
            'id_genero' => 1,
            'id_plan' => 10,
            'email' => 'cliente.menu@gym.test',
            'direccion' => null,
            'ciudad' => null,
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => now()->toDateString(),
            'fecha_pago' => now()->toDateString(),
            'slug' => 'cliente-menu',
            'estado' => 1,
            'id_usuario' => $trainer->id,
            'fecha_fin' => now()->addMonth()->toDateString(),
            'id_gimnasio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clientUser = User::factory()->create([
            'name' => 'Usuario Cliente Menu',
            'email' => 'usuario.cliente.menu@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 3,
            'id_cliente' => $clienteId,
            'id_gimnasio' => 1,
        ]);

        $response = $this->actingAs($clientUser)->get('/mensajes/create');

        $response->assertOk();
        $response->assertSee('Mi agenda');
        $response->assertSee('Cuenta corriente');
        $response->assertSee('Par-Q');
        $response->assertSee('Evalua al gimnasio');
        $response->assertSee('Cliente Menu');
        $response->assertSee('Plan Cliente Menu');
        $response->assertDontSee('Inicio');
        $response->assertDontSee('Planes');
        $response->assertDontSee('Caja');
        $response->assertDontSee(route('portada'), false);

        $this->actingAs($clientUser)
            ->get(route('clientes.agenda', 'cliente-menu'))
            ->assertOk()
            ->assertSee('Cliente Menu')
            ->assertSee('Plan Cliente Menu')
            ->assertDontSee(route('portada'), false);

        $this->actingAs($clientUser)
            ->get(route('clientes.cuenta_corriente', 'cliente-menu'))
            ->assertOk()
            ->assertSee('Cuenta Corriente')
            ->assertDontSee(route('clientes.opciones.portada', 'cliente-menu'), false)
            ->assertDontSee('fa-circle-left fa-2x', false);

        $this->actingAs($clientUser)
            ->get(route('clientes.pmusculares', 'cliente-menu'))
            ->assertOk()
            ->assertDontSee('!!json_encode', false)
            ->assertDontSee(' - > ', false);

        $this->actingAs($clientUser)
            ->get(route('mensajes.index'))
            ->assertOk()
            ->assertSee('Cliente Menu')
            ->assertSee('Plan Cliente Menu')
            ->assertDontSee(route('portada'), false);

        $this->actingAs($clientUser)
            ->get(route('encuestas.create', 'entrenador-cliente-menu'))
            ->assertOk()
            ->assertSee('Cliente Menu')
            ->assertSee('Plan Cliente Menu')
            ->assertDontSee(route('portada'), false);

        $this->actingAs($clientUser)
            ->get('/survey/cliente-menu')
            ->assertOk();
    }

    public function test_portada_route_works_for_admin_and_client(): void
    {
        $admin = User::factory()->create([
            'email' => 'portada-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $trainer = User::factory()->create([
            'name' => 'Entrenador Portada',
            'email' => 'trainer.portada@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
            'slug' => 'entrenador-portada',
        ]);

        DB::table('planes')->insert([
            'id' => 11,
            'nombre' => 'Plan Portada',
            'slug' => 'plan-portada',
            'valor' => 100,
            'porcentaje' => 0,
            'estado' => 1,
            'id_gimnasio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'ci' => '4444444',
            'nombres' => 'Cliente Portada',
            'paterno' => 'Cuatro',
            'materno' => null,
            'telefono' => '123456',
            'id_genero' => 1,
            'id_plan' => 11,
            'email' => 'cliente.portada@gym.test',
            'direccion' => null,
            'ciudad' => null,
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => now()->toDateString(),
            'fecha_pago' => now()->toDateString(),
            'slug' => 'cliente-portada',
            'estado' => 1,
            'id_usuario' => $trainer->id,
            'fecha_fin' => now()->addMonth()->toDateString(),
            'id_gimnasio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clientUser = User::factory()->create([
            'name' => 'Usuario Portada',
            'email' => 'usuario.portada@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 3,
            'id_cliente' => $clienteId,
            'id_gimnasio' => 1,
        ]);

        $this->actingAs($admin)->get('/portada')->assertRedirect(route('dashboard'));
        $this->actingAs($clientUser)->get('/portada')->assertRedirect(route('clientes.agenda', 'cliente-portada'));
    }

    public function test_dashboard_loads_without_deprecated_retention_panel(): void
    {
        $admin = User::factory()->create([
            'email' => 'dashboard-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('graficoRetencion', false);
        $response->assertDontSee('Tasa de retención mensual por entrenador (%)');
    }

    public function test_operational_pages_show_role_and_gym_labels_without_duplicate_headers(): void
    {
        $admin = User::factory()->create([
            'email' => 'ui-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/mensajes')
            ->assertOk()
            ->assertSeeText('Rol:')
            ->assertSeeText('Gimnasio:')
            ->assertDontSee('fa-envelope fa-2x', false)
            ->assertDontSee('Bandeja de entrada');

        $this->actingAs($admin)
            ->get('/tareas')
            ->assertOk()
            ->assertSeeText('Rol:')
            ->assertSeeText('Gimnasio:')
            ->assertDontSee('<h2 class="text-2xl font-bold p-4">Tareas</h2>', false);

        $this->actingAs($admin)
            ->get('/cursos')
            ->assertOk()
            ->assertSeeText('Rol:')
            ->assertSeeText('Gimnasio:')
            ->assertDontSee('Formación Continua');
    }

    public function test_profile_page_hides_portada_link_for_all_profiles(): void
    {
        $admin = User::factory()->create([
            'email' => 'profile-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $cliente = User::factory()->create([
            'email' => 'profile-cliente@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 3,
            'id_gimnasio' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('<a href="' . route('portada') . '" class="text-gray-700 hover:text-gray-500">', false)
            ->assertDontSee('fa-circle-left fa-2x', false);

        $this->actingAs($cliente)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('<a href="' . route('portada') . '" class="text-gray-700 hover:text-gray-500">', false)
            ->assertDontSee('fa-circle-left fa-2x', false);
    }

    public function test_sidebar_uses_black_and_white_for_hover_and_active_menu_states(): void
    {
        $admin = User::factory()->create([
            'email' => 'sidebar-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('.sidebar-nav-link:hover', false);
        $response->assertSee('background: #000 !important;', false);
        $response->assertSee('color: #fff !important;', false);
        $response->assertSee('is-active bg-black text-white shadow-lg shadow-stone-950/20', false);
    }

    public function test_admin_profile_uses_dashboard_and_hides_superadmin_only_navigation(): void
    {
        $admin = User::factory()->create([
            'email' => 'nav-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/portada')
            ->assertRedirect(route('dashboard'));

        $dashboardResponse = $this->actingAs($admin)->get('/dashboard');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee('Gimnasios');

        $this->actingAs($admin)
            ->get('/gimnasios')
            ->assertForbidden()
            ->assertSeeText('No tiene acceso');

        foreach (['/movimientos', '/entrenadores', '/planes', '/usuarios', '/pagos-entrenadores', '/evaluacion-inicial/catalogo'] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertDontSee('fa-circle-left fa-2x', false)
                ->assertDontSee('<a href="' . route('portada') . '"', false);
        }
    }

    public function test_user_forms_hide_commission_field_and_show_user_type_selector_without_superadmin(): void
    {
        $admin = User::factory()->create([
            'email' => 'usuarios-form-admin@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 1,
            'id_gimnasio' => 1,
        ]);

        $usuario = User::factory()->create([
            'email' => 'usuarios-form-user@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/usuarios/create')
            ->assertOk()
            ->assertSee('Tipo de Usuario')
            ->assertDontSee('Porcentaje Comisión')
            ->assertDontSee('Super usuario');

        $this->actingAs($admin)
            ->get('/usuarios/' . $usuario->id . '/edit')
            ->assertOk()
            ->assertSee('Tipo de Usuario')
            ->assertDontSee('Porcentaje Comisión')
            ->assertDontSee('Super usuario');
    }

    public function test_super_admin_menu_shows_curated_modules_and_hides_finance_navigation(): void
    {
        DB::table('gimnasios')->insert([
            'id' => 2,
            'nombre' => 'Gimnasio Norte',
            'slug' => 'gimnasio-norte',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $superAdmin = User::factory()->create([
            'email' => 'superadmin-menu@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 10,
            'id_gimnasio' => 1,
        ]);

        $trainer = User::factory()->create([
            'name' => 'Entrenador Global',
            'email' => 'entrenador.global@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 2,
        ]);

        DB::table('planes')->insert([
            'id' => 90,
            'nombre' => 'Plan Global',
            'slug' => 'plan-global',
            'valor' => 100,
            'porcentaje' => 0,
            'estado' => 1,
            'id_gimnasio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('clientes')->insert([
            'ci' => '9999999',
            'nombres' => 'Cliente Global',
            'paterno' => 'Prueba',
            'materno' => null,
            'telefono' => '123456',
            'id_genero' => 1,
            'id_plan' => 90,
            'email' => 'cliente.global@gym.test',
            'direccion' => null,
            'ciudad' => null,
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => now()->toDateString(),
            'fecha_pago' => now()->toDateString(),
            'slug' => 'cliente-global',
            'estado' => 1,
            'id_usuario' => $trainer->id,
            'fecha_fin' => now()->addMonth()->toDateString(),
            'id_gimnasio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->get('/dashboard');

        $response->assertOk();
        foreach (['Clientes', 'Agendas', 'Ejercicios', 'Mensajeria', 'Tareas', 'Cursos', 'Entrenadores', 'Planes', 'Gimnasios', 'Usuarios'] as $label) {
            $response->assertSee($label);
        }

        foreach (['Caja', 'Pagos entrenadores', 'Evaluacion inicial'] as $label) {
            $response->assertDontSee($label);
        }

        $this->actingAs($superAdmin)->get('/movimientos')->assertForbidden();
        $this->actingAs($superAdmin)->get('/pagos-entrenadores')->assertForbidden();
        $this->actingAs($superAdmin)->get('/evaluacion-inicial/catalogo')->assertForbidden();

        $gimnasiosResponse = $this->actingAs($superAdmin)->get('/gimnasios');
        $gimnasiosResponse->assertOk();
        $gimnasiosResponse->assertSee('Agregar gimnasio');
        $gimnasiosResponse->assertSee('Eliminar');

        $clientesResponse = $this->actingAs($superAdmin)->get('/clientes');
        $clientesResponse->assertOk();
        $clientesResponse->assertSee('Cliente Global');
        $clientesResponse->assertSee('Gimnasio');
        $clientesResponse->assertDontSee('Opciones');
    }

    public function test_deleting_user_removes_associated_client_record(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'superadmin-delete@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 10,
            'id_gimnasio' => 1,
        ]);

        $trainer = User::factory()->create([
            'name' => 'Entrenador Delete',
            'email' => 'entrenador.delete@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
        ]);

        DB::table('planes')->insert([
            'id' => 91,
            'nombre' => 'Plan Delete',
            'slug' => 'plan-delete',
            'valor' => 100,
            'porcentaje' => 0,
            'estado' => 1,
            'id_gimnasio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'ci' => '56565656',
            'nombres' => 'Cliente Eliminar',
            'paterno' => 'Asociado',
            'materno' => null,
            'telefono' => '123456',
            'id_genero' => 1,
            'id_plan' => 91,
            'email' => 'cliente.eliminar@gym.test',
            'direccion' => null,
            'ciudad' => null,
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => now()->toDateString(),
            'fecha_pago' => now()->toDateString(),
            'slug' => 'cliente-eliminar',
            'estado' => 1,
            'id_usuario' => $trainer->id,
            'fecha_fin' => now()->addMonth()->toDateString(),
            'id_gimnasio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $usuarioCliente = User::factory()->create([
            'email' => 'usuario.cliente.eliminar@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 3,
            'id_cliente' => $clienteId,
            'id_gimnasio' => 1,
        ]);

        $this->actingAs($superAdmin)
            ->delete('/usuarios/' . $usuarioCliente->id)
            ->assertRedirect('/usuarios');

        $this->assertDatabaseMissing('users', ['id' => $usuarioCliente->id]);
        $this->assertDatabaseMissing('clientes', ['id' => $clienteId]);
    }

    public function test_super_admin_client_forms_filter_plan_and_trainer_options_by_selected_gym(): void
    {
        DB::table('gimnasios')->insert([
            'id' => 2,
            'nombre' => 'Gimnasio Norte',
            'slug' => 'gimnasio-norte',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $superAdmin = User::factory()->create([
            'email' => 'superadmin-client-form@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 10,
            'id_gimnasio' => 1,
        ]);

        $trainerGymOne = User::factory()->create([
            'name' => 'Entrenador Gym Uno',
            'email' => 'trainer.gym1@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 1,
        ]);

        $trainerGymTwo = User::factory()->create([
            'name' => 'Entrenador Gym Dos',
            'email' => 'trainer.gym2@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 2,
        ]);

        DB::table('planes')->insert([
            [
                'id' => 93,
                'nombre' => 'Plan Gym Uno',
                'slug' => 'plan-gym-uno',
                'valor' => 100,
                'porcentaje' => 0,
                'estado' => 1,
                'id_gimnasio' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 94,
                'nombre' => 'Plan Gym Dos',
                'slug' => 'plan-gym-dos',
                'valor' => 120,
                'porcentaje' => 0,
                'estado' => 1,
                'id_gimnasio' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $clienteId = DB::table('clientes')->insertGetId([
            'ci' => '78787878',
            'nombres' => 'Cliente Selector',
            'paterno' => 'Gym',
            'materno' => null,
            'telefono' => '123456',
            'id_genero' => 1,
            'id_plan' => 94,
            'email' => 'cliente.selector@gym.test',
            'direccion' => null,
            'ciudad' => null,
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => now()->toDateString(),
            'fecha_pago' => now()->toDateString(),
            'slug' => 'cliente-selector-gym',
            'estado' => 1,
            'id_usuario' => $trainerGymTwo->id,
            'fecha_fin' => now()->addMonth()->toDateString(),
            'id_gimnasio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::factory()->create([
            'email' => 'cliente.selector.user@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 3,
            'id_cliente' => $clienteId,
            'id_gimnasio' => 2,
        ]);

        $createResponse = $this->actingAs($superAdmin)->get('/clientes/create');
        $createResponse->assertOk();
        $createResponse->assertSee('id_gimnasio', false);
        $createResponse->assertSee('data-gimnasio="1"', false);
        $createResponse->assertSee('data-gimnasio="2"', false);
        $createResponse->assertSee('filtrarOpcionesPorGimnasio', false);

        $editResponse = $this->actingAs($superAdmin)->get('/clientes/cliente-selector-gym/edit');
        $editResponse->assertOk();
        $editResponse->assertSee('id_gimnasio', false);
        $editResponse->assertSee('data-gimnasio="2"', false);
        $editResponse->assertSee('Entrenador Gym Dos');
        $editResponse->assertSee('Plan Gym Dos');
        $editResponse->assertSee('filtrarOpcionesPorGimnasio', false);
    }

    public function test_super_admin_operational_pages_show_gym_filters_and_context_columns(): void
    {
        DB::table('gimnasios')->insert([
            'id' => 2,
            'nombre' => 'Gimnasio Norte',
            'slug' => 'gimnasio-norte',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $superAdmin = User::factory()->create([
            'email' => 'superadmin-filtros@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 10,
            'id_gimnasio' => 1,
        ]);

        $trainer = User::factory()->create([
            'name' => 'Entrenador Filtro',
            'email' => 'entrenador.filtro@gym.test',
            'password' => Hash::make('password123'),
            'id_tipo_usuario' => 2,
            'id_gimnasio' => 2,
            'slug' => 'entrenador-filtro',
        ]);

        DB::table('tareas')->insert([
            'nombre' => 'Tarea Global',
            'descripcion' => 'Tarea de prueba',
            'fecha_limite' => now()->toDateString(),
            'id_usuario' => $trainer->id,
            'completada' => 0,
            'slug' => 'tarea-global',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('entrenadores_cursos')->insert([
            'curso' => 'Curso Global',
            'institucion' => 'Instituto Test',
            'modalidad' => 1,
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addMonth()->toDateString(),
            'id_entrenador' => $trainer->id,
            'slug' => 'curso-global',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('planes')->insert([
            'id' => 92,
            'nombre' => 'Plan Filtro',
            'slug' => 'plan-filtro',
            'valor' => 100,
            'porcentaje' => 0,
            'estado' => 1,
            'id_gimnasio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get('/tareas')
            ->assertOk()
            ->assertSee('Filtrar por gimnasio:')
            ->assertSee('Entrenador')
            ->assertSee('Gimnasio');

        $this->actingAs($superAdmin)
            ->get('/cursos')
            ->assertOk()
            ->assertSee('Filtrar por gimnasio:')
            ->assertSee('Entrenador')
            ->assertSee('Gimnasio');

        $this->actingAs($superAdmin)
            ->get('/entrenadores')
            ->assertOk()
            ->assertSee('Filtrar por gimnasio:')
            ->assertSee('Gimnasio')
            ->assertDontSee('Opciones');

        $this->actingAs($superAdmin)
            ->get('/planes')
            ->assertOk()
            ->assertSee('Filtrar por gimnasio:')
            ->assertSee('Gimnasio');
    }
}
