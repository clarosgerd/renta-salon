<?php

namespace App\DataTransferObjects;

/**
 * Respuesta uniforme que cualquier adaptador de banco (Unión/BNB/BCP)
 * debe devolver al generar un QR, sin importar el formato nativo de
 * la API de cada banco.
 */
class QrResponse
{
    public function __construct(
        public readonly string $qrIdExterno,
        public readonly string $imagenBase64,   // imagen del QR lista para <img src="data:image/png;base64,...">
        public readonly float $monto,
        public readonly ?\DateTimeInterface $expiraEn = null,
        public readonly array $payloadCrudo = [],
    ) {
    }
}
