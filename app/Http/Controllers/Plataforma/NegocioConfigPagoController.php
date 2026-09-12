<?php

namespace App\Http\Controllers\Plataforma;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNegocioConfigPagoRequest;
use App\Models\Negocio;
use App\Models\NegocioConfigPago;
use App\Services\PagoQr\PagoQrGatewayFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * SA-4 (11/09/2026) — ver brain/Historias_Usuario_Pantallas_RentSalon_Pro.md.
 * Vive bajo Route::domain('admin.rentsalon-pro.test'), solo Super Admin
 * (misma Policy que el resto de Negocio — ver docblock de NegocioPolicy: no
 * amerita una Policy propia, "configurar cobros" es parte de "administrar
 * el negocio").
 *
 * Mientras no exista un adapter real de banco (Unión/BNB/BCP), "Probar
 * conexión" siempre resuelve a MockQrGateway vía PagoQrGatewayFactory (éxito
 * determinístico) — el día que llegue una integración real solo se
 * descomenta el case correspondiente en el factory, esta pantalla no cambia.
 */
class NegocioConfigPagoController extends Controller
{
    /**
     * GET /plataforma/negocios/{negocio}/config-pago
     */
    public function edit(Negocio $negocio): View
    {
        $this->authorize('update', $negocio);

        return view('plataforma.negocios.config-pago', [
            'negocio' => $negocio,
            'config' => $negocio->configPago,
        ]);
    }

    /**
     * PUT /plataforma/negocios/{negocio}/config-pago
     */
    public function update(UpdateNegocioConfigPagoRequest $request, Negocio $negocio): RedirectResponse
    {
        $data = $request->validated();
        $config = $negocio->configPago ?? new NegocioConfigPago(['negocio_id' => $negocio->id]);

        $config->banco = $data['banco'] ?? null;
        $config->cuenta_destino = $data['cuenta_destino'] ?? null;
        $config->activo = $request->boolean('activo');

        // Las credenciales solo se tocan si el Super Admin marcó
        // explícitamente "reemplazar" — de lo contrario el valor cifrado
        // existente queda intacto (§3.13: nunca se re-muestran en texto
        // plano, solo opción "Reemplazar").
        if ($request->boolean('reemplazar_credenciales')) {
            $config->credenciales_json_cifrado = [
                'numero_comercio' => $data['numero_comercio'],
                'api_key' => $data['api_key'],
            ];
        }

        $config->save();

        return redirect()
            ->route('plataforma.negocios.config-pago.edit', $negocio)
            ->with('success', 'Configuración de cobros actualizada.');
    }

    /**
     * POST /plataforma/negocios/{negocio}/config-pago/probar-conexion
     */
    public function probarConexion(Negocio $negocio, PagoQrGatewayFactory $factory): RedirectResponse
    {
        $this->authorize('update', $negocio);

        $config = $negocio->configPago;

        if (! $config || ! $config->banco || empty($config->credenciales_json_cifrado)) {
            return back()->with('error', 'Selecciona un banco y guarda las credenciales antes de probar la conexión.');
        }

        try {
            $factory->paraNegocio($negocio)->generarQr(1.00, 'PRUEBA-'.$negocio->id.'-'.now()->timestamp);
            $config->update(['ultima_prueba_conexion' => now()]);

            return back()->with('success', 'Conexión de prueba exitosa — se generó un QR de Bs. 1.');
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo generar el QR de prueba: '.$e->getMessage());
        }
    }
}
