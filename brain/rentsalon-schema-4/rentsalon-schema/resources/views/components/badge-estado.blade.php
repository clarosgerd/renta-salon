@php
    $estilos = [
        'pendiente' => 'bg-amber-50 text-amber-700',
        'confirmada' => 'bg-emerald-50 text-emerald-700',
        'cancelada' => 'bg-gray-100 text-gray-500',
        'finalizada' => 'bg-blue-50 text-blue-700',
        'pagada' => 'bg-emerald-50 text-emerald-700',
    ];
    $etiquetas = [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'finalizada' => 'Finalizada',
        'pagada' => 'Pagada',
    ];
@endphp

<span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $estilos[$estado] ?? 'bg-gray-100 text-gray-600' }}">
    {{ $etiquetas[$estado] ?? ucfirst($estado) }}
</span>
