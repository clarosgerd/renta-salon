# Configuración inicial del proyecto Laravel — RentSalon Pro

Esta guía cubre desde `composer create-project` hasta tener el proyecto corriendo con
multi-tenant, roles, PDFs y el esquema de base de datos ya integrado.

---

## Paso 1 — Requisitos previos

- PHP 8.3+
- Composer 2.x
- Node.js 18+ y npm
- MySQL 8 / MariaDB
- (Opcional pero recomendado) Laravel Herd, Valet o Sail para desarrollo local con subdominios

> **Importante para el multi-tenant:** como cada negocio se identifica por subdominio
> (`negocio.rentsalonpro.test`), necesitas que tu entorno local resuelva subdominios comodín.
> Laravel Herd y Valet lo hacen automáticamente. Si usas Sail/Docker sin esas herramientas,
> agrega entradas manuales en tu archivo `hosts` para cada negocio de prueba (ver Paso 8).

---

## Paso 2 — Crear el proyecto

```bash
composer create-project laravel/laravel rentsalon-pro
cd rentsalon-pro
```

Configura la base de datos en `.env`:

```env
APP_NAME="RentSalon Pro"
APP_URL=http://rentsalon-pro.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rentsalon_pro
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos:

```bash
mysql -u root -p -e "CREATE DATABASE rentsalon_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## Paso 3 — Instalar autenticación (Breeze, stack Blade)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run build
```

Esto genera las vistas de login/registro en Blade y la migración base de `users`, que
más adelante extenderemos con `negocio_id` y `role`.

---

## Paso 4 — Instalar paquetes clave del proyecto

```bash
# Roles y permisos
composer require spatie/laravel-permission

# Generación de PDF (recibos, estado de cuenta)
composer require barryvdh/laravel-dompdf

# Auditoría de acciones (recomendado para el módulo de Usuarios)
composer require spatie/laravel-activitylog

# Tailwind ya viene con Breeze; confirmamos que esté instalado
npm install -D tailwindcss postcss autoprefixer
```

Publica la configuración de `spatie/laravel-permission`:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Esto crea su propia migración de `roles`, `permissions` y tablas pivote — la dejamos
correr junto con las nuestras en el Paso 6.

---

## Paso 5 — Copiar el esquema ya preparado

Copia el contenido del paquete `rentsalon-schema.zip` (entregado en el paso anterior)
dentro de tu proyecto recién creado:

```bash
# Desde la raíz de rentsalon-pro/
cp ruta/al/zip/rentsalon-schema/database/migrations/*.php database/migrations/
cp -r ruta/al/zip/rentsalon-schema/app/Scopes app/
cp -r ruta/al/zip/rentsalon-schema/app/Traits app/
cp ruta/al/zip/rentsalon-schema/app/Http/Middleware/IdentificarNegocio.php app/Http/Middleware/
```

**Verifica el orden de migraciones**: las que trae Breeze (`create_users_table`,
`create_password_reset_tokens_table`, etc.) y las de Spatie deben ejecutarse **antes**
de `2024_01_01_000003_add_negocio_fields_to_users_table.php`, porque esa migración
modifica la tabla `users` que ya debe existir. Laravel ordena por el prefijo de fecha
del nombre de archivo, así que confirma que los timestamps de Breeze/Spatie sean
anteriores a `2024_01_01_...` (por defecto lo son, ya que Breeze los genera con la
fecha real de instalación).

---

## Paso 6 — Crear el modelo Negocio y correr las migraciones

Antes de migrar, necesitas el modelo `Negocio` (referenciado por el middleware y el trait):

```bash
php artisan make:model Negocio
```

```php
// app/Models/Negocio.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Negocio extends Model
{
    protected $fillable = [
        'nombre_comercial', 'subdominio', 'dominio_personalizado',
        'logo_url', 'color_primario', 'telefono_contacto',
        'email_contacto', 'estado', 'plan',
    ];
}
```

Ahora sí, corre las migraciones:

```bash
php artisan migrate
```

Si algo falla por orden de llaves foráneas, revisa el mensaje de error — casi siempre
indica qué tabla referenciada aún no existe, y basta con renombrar el archivo de
migración con una fecha ligeramente posterior.

---

## Paso 7 — Registrar el middleware `IdentificarNegocio`

En Laravel 11, los middlewares se registran en `bootstrap/app.php`:

```php
// bootstrap/app.php
use App\Http\Middleware\IdentificarNegocio;

->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('negocio', [
        IdentificarNegocio::class,
    ]);
})
```

Y en `routes/web.php`, separa claramente las rutas de la plataforma central de las
rutas por negocio:

```php
// Rutas de la plataforma central — Super Admin, SIN IdentificarNegocio
Route::domain('admin.rentsalon-pro.test')->group(function () {
    Route::get('/plataforma/negocios', [NegocioController::class, 'index']);
    // ...
});

// Rutas del portal público + panel de cada negocio — CON IdentificarNegocio
Route::middleware('negocio')->group(function () {
    Route::get('/', [PortalPublicoController::class, 'home']);
    Route::get('/salones', [PortalPublicoController::class, 'salones']);

    Route::middleware('auth')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        // ... resto de módulos del panel
    });
});
```

---

## Paso 8 — Configurar subdominios en desarrollo local

**Con Laravel Herd o Valet:** solo asegúrate que el proyecto esté "parkeado" y los
subdominios comodín funcionan automáticamente (`cualquier-cosa.rentsalon-pro.test`).

**Sin Herd/Valet (ej. Sail o servidor manual):** agrega entradas en tu archivo hosts
por cada negocio de prueba:

```
# /etc/hosts (Mac/Linux) o C:\Windows\System32\drivers\etc\hosts (Windows)
127.0.0.1   rentsalon-pro.test
127.0.0.1   admin.rentsalon-pro.test
127.0.0.1   saloneslapaz.rentsalon-pro.test
127.0.0.1   saloneselegantes.rentsalon-pro.test
```

---

## Paso 9 — Seeder de datos de prueba

```bash
php artisan make:seeder NegocioDemoSeeder
```

```php
// database/seeders/NegocioDemoSeeder.php
public function run(): void
{
    $negocio = \App\Models\Negocio::create([
        'nombre_comercial' => 'Salones La Paz',
        'subdominio' => 'saloneslapaz',
        'color_primario' => '#1D9E75',
        'estado' => 'activo',
    ]);

    \App\Models\User::create([
        'negocio_id' => $negocio->id,
        'name' => 'Admin Demo',
        'email' => 'admin@saloneslapaz.test',
        'password' => bcrypt('password'),
        'role' => 'admin_negocio',
    ]);
}
```

```bash
php artisan db:seed --class=NegocioDemoSeeder
```

Ahora puedes entrar a `http://saloneslapaz.rentsalon-pro.test/admin` y loguearte con
`admin@saloneslapaz.test` / `password`.

---

## Paso 10 — Instalar dependencias de frontend específicas

```bash
npm install alpinejs
npm install --save fullcalendar
```

En `resources/js/app.js`, registra Alpine (si Breeze no lo dejó ya configurado) y
FullCalendar para el módulo de Calendario.

```bash
npm run build
```

---

## Paso 11 — Verificación final

Lista de chequeo antes de empezar a programar los módulos:

- [ ] `php artisan migrate:fresh --seed` corre sin errores
- [ ] Puedes acceder a `saloneslapaz.rentsalon-pro.test` y ver el portal público (aunque
      esté vacío/sin diseño todavía)
- [ ] Puedes hacer login en `/admin` con el usuario sembrado
- [ ] Si creas un `Salon::create([...])` desde `php artisan tinker` estando "dentro" del
      contexto de un negocio, el registro guarda `negocio_id` automáticamente (prueba el
      trait `BelongsToNegocio`)
- [ ] Un negocio no puede ver los salones de otro negocio al consultar `Salon::all()`

---

## Siguiente paso sugerido

Con el proyecto ya configurado y el esquema corriendo, lo natural es generar los
**Eloquent Models con sus relaciones** (`Salon`, `Paquete`, `Reservacion`, `PagoAbono`,
`Producto`, `Inventario`, `VentaPos`, etc.), aplicando el trait `BelongsToNegocio` a
cada uno y dejando listas las relaciones `hasMany`/`belongsTo`/`belongsToMany` para
empezar a construir los controladores del panel.
