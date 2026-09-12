@extends('layouts.portal')

@section('titulo', 'Solicitud recibida')

@section('content')
    <div class="max-w-lg mx-auto px-6 py-20 text-center">
        <div class="w-14 h-14 rounded-full bg-marca mx-auto flex items-center justify-center text-white text-2xl" style="background-color: var(--brand)">
            &check;
        </div>
        <h1 class="text-2xl font-semibold text-gray-900 mt-6">Tu solicitud fue recibida</h1>
        <p class="text-gray-500 mt-2">Te contactaremos pronto para confirmar tu fecha.</p>

        <div class="bg-white border border-gray-200 rounded-lg p-5 mt-6 inline-block">
            <p class="text-xs text-gray-400 uppercase tracking-wide">Número de solicitud</p>
            <p class="text-lg font-mono font-medium text-gray-900">{{ $folio }}</p>
        </div>

        <div class="mt-8">
            <a href="{{ route('portal.home') }}" class="text-sm text-marca hover:underline">&larr; Volver al inicio</a>
        </div>
    </div>
@endsection
