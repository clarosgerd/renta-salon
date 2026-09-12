@extends('layouts.admin')

@section('titulo', 'Nuevo paquete')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-5">
            <a href="{{ route('admin.paquetes.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Paquetes</a>
            <h1 class="text-lg font-medium text-gray-900 mt-1">Nuevo paquete</h1>
        </div>

        <form method="POST" action="{{ route('admin.paquetes.store') }}"
              class="bg-white border border-gray-200 rounded-lg p-6">
            @csrf

            @include('admin.paquetes._form')

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('admin.paquetes.index') }}"
                   class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600">Cancelar</a>
                <button class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Guardar paquete
                </button>
            </div>
        </form>
    </div>
@endsection
