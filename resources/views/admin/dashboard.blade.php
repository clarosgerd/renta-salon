@extends('layouts.admin')

@section('titulo', 'Dashboard')

@php
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fechaCorta = fn ($fecha) => $fecha->day . ' ' . $meses[$fecha->month - 1];
    // Mismo mapeo que portal/paquetes/{index,show}.blade.php — se repite
    // acá porque no hay un accessor compartido en el modelo Paquete.
    $etiquetasTipo = ['boda' => 'Boda', 'xv_anos' => 'XV Años', 'corporativo' => 'Corporativo', 'otro' => 'Evento'];
@endphp

@section('content')
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500">{{ app('negocio_actual')->nombre_comercial ?? 'RentSalon Pro' }}</p>
        </div>
        @can('create', \App\Models\Reservacion::class)
            <a href="{{ route('admin.reservaciones.create') }}"
               class="inline-flex items-center gap-1.5 text-sm border border-gray-300 rounded-md px-4 py-2 text-gray-700 hover:bg-gray-50">
                + Nueva reservación
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 text-sm px-4 py-3 border border-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500">Eventos esta semana</p>
            <p class="text-3xl font-semibold text-gray-900 mt-1">{{ $eventosEstaSemana }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500">Solicitudes pendientes</p>
            <p class="text-3xl font-semibold {{ $solicitudesPendientes > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1">
                {{ $solicitudesPendientes }}
            </p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500">Saldo total por cobrar</p>
            <p class="text-2xl sm:text-3xl font-semibold text-gray-900 mt-1">Bs {{ number_format($saldoPorCobrar, 0) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <p class="text-xs text-gray-500">Productos con stock bajo</p>
            <p class="text-3xl font-semibold {{ $productosStockBajo > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">
                {{ $productosStockBajo }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="text-sm font-medium text-gray-900 mb-4">Próximos eventos</h2>

            <div class="space-y-4">
                @forelse ($proximosEventos as $evento)
                    <a href="{{ route('admin.reservaciones.show', $evento) }}" class="flex items-center justify-between hover:bg-gray-50 -mx-2 px-2 py-1 rounded-md">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $evento->paquete ? ($etiquetasTipo[$evento->paquete->tipo_evento] ?? 'Evento') : 'Evento' }} — {{ $evento->cliente_nombre }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $evento->salon->nombre }} · {{ $fechaCorta($evento->fecha_evento) }}
                            </p>
                        </div>
                        <x-badge-estado :estado="$evento->estado" />
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No hay eventos próximos.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <h2 class="text-sm font-medium text-gray-900 mb-4">Solicitudes nuevas</h2>

            <div class="space-y-4">
                @forelse ($solicitudesNuevas as $solicitud)
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $solicitud->cliente_nombre }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $fechaCorta($solicitud->fecha_evento) }} · {{ $solicitud->salon->nombre }}</p>

                        @can('cambiarEstado', $solicitud)
                            <div class="flex gap-2 mt-2">
                                <form method="POST" action="{{ route('admin.reservaciones.rechazar', $solicitud) }}"
                                      onsubmit="return confirm('¿Rechazar esta solicitud? La fecha quedará liberada.')">
                                    @csrf @method('PATCH')
                                    <button class="text-sm border border-gray-300 text-gray-700 px-4 py-1.5 rounded-md hover:bg-gray-50">
                                        Rechazar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.reservaciones.confirmar', $solicitud) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-sm bg-gray-900 text-white px-4 py-1.5 rounded-md hover:bg-gray-800">
                                        Confirmar
                                    </button>
                                </form>
                            </div>
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No hay solicitudes nuevas.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
