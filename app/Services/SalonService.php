<?php

namespace App\Services;

use App\Models\Imagen;
use App\Models\Salon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Reglas de negocio de Salones — separado del Controller mismo criterio
 * que ReservacionService. La galería de fotos se maneja acá: crear() y
 * actualizar() solo AGREGAN imágenes nuevas (nunca tocan las existentes);
 * moverImagen()/eliminarImagen() son las únicas dueñas de reordenar/borrar
 * una imagen puntual, para no mezclar "editar datos del salón" con
 * "gestionar la galería" en un solo submit gigante.
 */
class SalonService
{
    /**
     * @param  UploadedFile[]  $archivosImagenes
     */
    public function crear(array $datos, array $archivosImagenes): Salon
    {
        $salon = Salon::create($datos);

        $this->agregarImagenes($salon, $archivosImagenes);

        return $salon;
    }

    /**
     * @param  UploadedFile[]  $archivosImagenes
     */
    public function actualizar(Salon $salon, array $datos, array $archivosImagenes): Salon
    {
        $salon->update($datos);

        $this->agregarImagenes($salon, $archivosImagenes);

        return $salon->fresh();
    }

    public function toggleActivo(Salon $salon): Salon
    {
        $salon->update(['activo' => ! $salon->activo]);

        return $salon;
    }

    /**
     * Intercambia el `orden` de $imagen con su vecina (anterior si
     * $direccion es 'arriba', siguiente si 'abajo'), dentro de la misma
     * galería (mismo imageable). Sin drag-and-drop — no hay librería
     * instalada para eso, botones simples mantienen el mismo criterio de
     * "vainilla, sin dependencias nuevas" que el resto del panel.
     */
    public function moverImagen(Imagen $imagen, string $direccion): void
    {
        $galeria = Imagen::where('imageable_type', $imagen->imageable_type)
            ->where('imageable_id', $imagen->imageable_id)
            ->orderBy('orden')
            ->get();

        $posicion = $galeria->search(fn (Imagen $i) => $i->id === $imagen->id);
        $posicionVecina = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;

        if ($posicion === false || ! $galeria->has($posicionVecina)) {
            return;
        }

        $vecina = $galeria->get($posicionVecina);

        [$ordenImagen, $ordenVecina] = [$imagen->orden, $vecina->orden];
        $imagen->update(['orden' => $ordenVecina]);
        $vecina->update(['orden' => $ordenImagen]);
    }

    public function eliminarImagen(Imagen $imagen): void
    {
        // El `url` guardado es el público (Storage::url()) — se deriva la
        // ruta real del disco a partir de él para poder borrar el archivo.
        $ruta = str_replace(Storage::disk('public')->url(''), '', $imagen->url);
        Storage::disk('public')->delete($ruta);

        $imagen->delete();
    }

    /**
     * @param  UploadedFile[]  $archivos
     */
    protected function agregarImagenes(Salon $salon, array $archivos): void
    {
        if (empty($archivos)) {
            return;
        }

        $ordenSiguiente = (int) ($salon->imagenes()->max('orden') ?? -1) + 1;

        foreach ($archivos as $archivo) {
            $ruta = $archivo->store('salones', 'public');

            // Vía la relación (no Imagen::create() directo) — Imagen::$fillable
            // solo tiene 'url'/'orden'; imageable_id/imageable_type los
            // setea la propia relación morphMany, sin depender de
            // mass-assignment para esas 2 columnas.
            $salon->imagenes()->create([
                'url' => Storage::disk('public')->url($ruta),
                'orden' => $ordenSiguiente++,
            ]);
        }
    }
}
