@extends('layouts.admin')

@section('titulo', 'Editar usuario')

@section('content')
    <div class="max-w-2xl">
        <div class="mb-5">
            <a href="{{ route('admin.usuarios.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Usuarios</a>
            <h1 class="text-lg font-medium text-gray-900 mt-1">Editar usuario — {{ $usuario->name }}</h1>
        </div>

        <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}"
              class="bg-white border border-gray-200 rounded-lg p-6">
            @csrf
            @method('PUT')

            @include('admin.usuarios._form')

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('admin.usuarios.index') }}"
                   class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600">Cancelar</a>
                <button class="px-4 py-2 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
@endsection
