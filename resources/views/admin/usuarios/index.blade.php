@extends('layouts.admin')

@section('titulo', 'Usuarios')

@section('content')
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-medium text-gray-900">Usuarios</h1>
            <p class="text-sm text-gray-500">Admin Negocio, Admin Salón y Cajero de tu negocio.</p>
        </div>
        <a href="{{ route('admin.usuarios.create') }}"
           class="inline-flex items-center gap-2 bg-emerald-600 text-white text-sm px-4 py-2 rounded-md hover:bg-emerald-700">
            + Nuevo usuario
        </a>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 text-red-700 text-sm px-4 py-3 border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Rol</th>
                    <th class="text-left px-4 py-3">Salones asignados</th>
                    <th class="text-left px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php
                    $etiquetasRol = ['admin_negocio' => 'Admin Negocio', 'admin_salon' => 'Admin Salón', 'cajero' => 'Cajero'];
                @endphp
                @forelse ($usuarios as $usuario)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $usuario->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $usuario->email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                                {{ $etiquetasRol[$usuario->role] ?? $usuario->role }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($usuario->role === 'admin_negocio')
                                <span class="text-xs text-gray-500">Todos</span>
                            @elseif ($usuario->salones->isEmpty())
                                <span class="text-xs text-red-500">Ninguno</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($usuario->salones as $salon)
                                        <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">{{ $salon->nombre }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full {{ $usuario->activo ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="text-emerald-700 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('admin.usuarios.toggle-activo', $usuario) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-gray-500 hover:underline">
                                        {{ $usuario->activo ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">Todavía no hay usuarios cargados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>
@endsection
