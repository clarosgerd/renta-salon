@extends('layouts.plataforma')

@section('titulo', 'Impersonaciones')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Log de impersonación</h1>
            <p class="text-sm text-gray-500">Historial de accesos de soporte técnico (SA-5), solo lectura.</p>
        </div>
        <a href="{{ route('plataforma.negocios.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Negocios</a>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Inicio</th>
                    <th class="text-left px-4 py-3">Super Admin</th>
                    <th class="text-left px-4 py-3">Negocio</th>
                    <th class="text-left px-4 py-3">Impersonó a</th>
                    <th class="text-left px-4 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600">{{ $log->iniciada_en->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $log->superAdmin->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $log->negocio->nombre_comercial ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $log->usuarioImpersonado->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($log->finalizada_en)
                                <span class="text-xs text-gray-500">Finalizada {{ $log->finalizada_en->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Activa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400">Todavía no hay impersonaciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endsection
