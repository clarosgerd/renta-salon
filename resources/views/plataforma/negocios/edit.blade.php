@extends('layouts.plataforma')

@section('titulo', 'Editar negocio')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-5">
            <a href="{{ route('plataforma.negocios.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Negocios</a>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-lg font-medium text-gray-900">Editar negocio — {{ $negocio->nombre_comercial }}</h1>
                <a href="{{ route('plataforma.negocios.config-pago.edit', $negocio) }}" class="text-sm text-emerald-700 hover:underline">Cobros (QR) &rarr;</a>
            </div>
        </div>

        <form method="POST" action="{{ route('plataforma.negocios.update', $negocio) }}"
              class="bg-white border border-gray-200 rounded-lg p-6">
            @csrf
            @method('PUT')

            @include('plataforma.negocios._form')

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('plataforma.negocios.index') }}"
                   class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600">Cancelar</a>
                <button class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
@endsection
