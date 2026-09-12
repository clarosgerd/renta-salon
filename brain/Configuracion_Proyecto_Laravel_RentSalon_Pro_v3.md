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

> **¿Vas a usar XAMPP?** Funciona perfecto, pero cambian varias cosas puntuales — ver el
> recuadro "Con XAMPP" al final de cada paso relevante (1, 2, 7 y 8). El resumen: el proyecto
> **no va en `htdocs`** como un sitio PHP tradicional (solo la carpeta `public/` se expone),
> necesitas revisar la versión de PHP que trae tu XAMPP, y el manejo de subdominios se hace
> con un Virtual Host de Apache en vez de la resolución automática de Herd/Valet. Todo lo
> demás de esta guía (Composer, migraciones, modelos, middleware) es idéntico.

**Con XAMPP — Paso 1:**
1. Verifica la versión de PHP que trae tu instalación: abre `http://localhost/dashboard/phpinfo.php`
   o corre `C:\xampp\php\php.exe -v` (Windows) / `/Applications/XAMPP/xamppfiles/bin/php -v` (Mac).
   Los XAMPP recientes traen PHP 8.2, que **no cumple** el mínimo de Laravel 11 (PHP 8.2 sí es
   compatible en realidad — Laravel 11 requiere 8.2+, así que confirma que tengas al menos 8.2;
   si tu XAMPP trae 8.1 o menor, tendrás que instalar PHP 8.2+ aparte, o usar Laravel Herd para
   el intérprete de PHP y XAMPP solo para MySQL/phpMyAdmin).
2. Habilita las extensiones de PHP que Laravel necesita, editando `php.ini` (Panel de Control de
   XAMPP → Apache → Config → `php.ini`) y quitando el `;` de estas líneas si están comentadas:
   `extension=openssl`, `extension=pdo_mysql`, `extension=mbstring`, `extension=fileinfo`,
   `extension=curl`, `extension=zip`. Reinicia Apache después de guardar.
3. Composer se instala igual, apuntando al PHP de XAMPP si lo usas como intérprete principal
   (agrega `C:\xampp\php` a tu variable de entorno `PATH` en Windows).

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

**Con XAMPP — Paso 2:**
- MySQL de XAMPP por defecto usa usuario `root` **sin contraseña**, así que tu `.env` queda:
  ```env
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- Puedes crear la base de datos desde **phpMyAdmin** (`http://localhost/phpmyadmin`) en vez de
  la terminal: botón "Nueva", nombre `rentsalon_pro`, cotejamiento `utf8mb4_unicode_ci`.
- Asegúrate de que el servicio **MySQL esté en verde/corriendo** en el Panel de Control de XAMPP
  antes de correr cualquier `php artisan migrate`.
- **No pongas el proyecto dentro de `htdocs/`** siguiendo la convención de PHP plano (ej.
  `htdocs/rentsalon-pro/index.php`). Laravel expone solo la carpeta `public/` como raíz web; si
  metes todo el proyecto en `htdocs` y entras por `localhost/rentsalon-pro`, vas a estar
  navegando la raíz del proyecto completo (inseguro) y las rutas se rompen porque Laravel espera
  ser la raíz del dominio. La forma correcta es crear el proyecto **fuera** de `htdocs` (ej. en
  `C:\laravel\rentsalon-pro` o `~/laravel/rentsalon-pro`) y apuntar un Virtual Host de Apache
  directo a su carpeta `public/` — ver el recuadro del Paso 8.

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

**Con XAMPP — Paso 8 (configuración completa):** aquí es donde más difiere de Herd/Valet,
porque Apache no resuelve subdominios comodín solo — hay que decírselo explícitamente con
un Virtual Host.

1. **Habilita el módulo de Virtual Hosts** en Apache: abre
   `C:\xampp\apache\conf\httpd.conf` y confirma que esta línea NO esté comentada:
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```

2. **Edita el archivo de Virtual Hosts** (`C:\xampp\apache\conf\extra\httpd-vhosts.conf` en
   Windows, o `/Applications/XAMPP/xamppfiles/etc/extra/httpd-vhosts.conf` en Mac) y agrega:
   ```apache
   <VirtualHost *:80>
       ServerName rentsalon-pro.test
       ServerAlias *.rentsalon-pro.test
       DocumentRoot "C:/laravel/rentsalon-pro/public"

       <Directory "C:/laravel/rentsalon-pro/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
   La línea clave es `ServerAlias *.rentsalon-pro.test` — el asterisco es lo que le da a
   Apache el mismo comportamiento de "wildcard" que Herd/Valet hacen automáticamente.
   Cualquier subdominio (`saloneslapaz.rentsalon-pro.test`, `admin.rentsalon-pro.test`, el
   que sea) apunta a la misma carpeta `public/`, y es tu middleware `IdentificarNegocio` el
   que decide qué negocio mostrar según el `Host` recibido — no Apache.

3. **Registra los hosts** que vayas a usar para pruebas (Apache necesita el `ServerAlias`
   arriba, pero el sistema operativo también necesita saber que esos nombres son `127.0.0.1`):
   ```
   # /etc/hosts (Mac/Linux) o C:\Windows\System32\drivers\etc\hosts (Windows, como Administrador)
   127.0.0.1   rentsalon-pro.test
   127.0.0.1   admin.rentsalon-pro.test
   127.0.0.1   saloneslapaz.rentsalon-pro.test
   127.0.0.1   saloneselegantes.rentsalon-pro.test
   ```
   A diferencia del `ServerAlias` con asterisco de Apache, el archivo hosts del sistema
   operativo **no admite comodines** — cada negocio nuevo que crees en desarrollo necesita su
   propia línea aquí. (En producción esto no aplica: ahí usas DNS real con un registro
   wildcard `*.rentsalon-pro.com` apuntando a tu servidor, sin tocar ningún archivo hosts.)

4. **Habilita `mod_rewrite`** (necesario para las rutas "limpias" de Laravel): en
   `httpd.conf`, confirma que esta línea esté sin comentar:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

5. **Reinicia Apache** desde el Panel de Control de XAMPP para que tome los cambios de
   `httpd.conf` y `httpd-vhosts.conf`.

6. Ahora `php artisan migrate`, `php artisan db:seed`, etc. los sigues corriendo por
   terminal exactamente igual que en el resto de la guía — XAMPP solo reemplaza a
   Herd/Valet en la parte de servir las páginas por Apache, no en la parte de Artisan/Composer.

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
