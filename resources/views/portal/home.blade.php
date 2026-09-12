@extends('layouts.portal')

@section('titulo', $negocio->nombre_comercial)

@section('content')
    <section class="max-w-5xl mx-auto px-6 py-20 text-center">
        <h1 class="text-3xl sm:text-4xl font-semibold text-gray-900">{{ $negocio->nombre_comercial }}</h1>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">
            Encontrá el salón ideal para tu próximo evento y solicitá tu fecha en minutos.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-3 mt-8">
            <a href="{{ route('portal.salones') }}"
               class="btn-marca text-white text-sm px-6 py-3 rounded-md hover:opacity-90">
                Ver salones
            </a>
            <a href="{{ route('portal.paquetes') }}"
               class="border border-gray-300 text-gray-700 text-sm px-6 py-3 rounded-md hover:bg-gray-50">
                Ver paquetes
            </a>
        </div>
    </section>
@endsection
