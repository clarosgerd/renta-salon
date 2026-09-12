@extends('layouts.plataforma')

@section('titulo', 'Negocios')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Negocios</h1>
            <p class="text-sm text-gray-500">Todos los negocios de la plataforma.</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('plataforma.impersonaciones.index') }}" class="text-sm text-gray-500 hover:underline">
                Log de impersonación
            </a>
            <a href="{{ route('plataforma.negocios.create') }}"
               class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
                + Nuevo negocio
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Nombre comercial</th>
                    <th class="text-left px-4 py-3">Subdominio</th>
                    <th class="text-right px-4 py-3">Salones</th>
                    <th class="text-right px-4 py-3">Reservaciones (mes)</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($negocios as $negocio)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $negocio->nombre_comercial }}</td>
                        <td class="px-4 py-3 text-gray-600 font-mono">{{ $negocio->subdominio }}</td>
                        <td class="px-4 py-3 text-right">{{ $negocio->salones_count }}</td>
                        <td class="px-4 py-3 text-right">{{ $negocio->reservaciones_mes_count }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $negocio->estado === 'activo' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $negocio->estado === 'activo' ? 'Activo' : 'Suspendido' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('plataforma.negocios.edit', $negocio) }}" class="text-emerald-700 hover:underline">Editar</a>
                                <a href="{{ route('plataforma.negocios.config-pago.edit', $negocio) }}" class="text-emerald-700 hover:underline">Cobros (QR)</a>
                                <form method="POST" action="{{ route('plataforma.negocios.impersonar', $negocio) }}">
                                    @csrf
                                    <button class="text-amber-700 hover:underline">Impersonar</button>
                                </form>
                                <form method="POST" action="{{ route('plataforma.negocios.toggle-estado', $negocio) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-gray-500 hover:underline">
                                        {{ $negocio->estado === 'activo' ? 'Suspender' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">Todavía no hay negocios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $negocios->links() }}
    </div>
@endsection
