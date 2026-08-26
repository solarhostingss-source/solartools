@extends('layouts.admin')

@section('title')
    SolarTools
@endsection

@section('content-header')
    <h1>SolarTools<small>Solar AI Console + Discord Webhooks</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.extensions') }}">Extensions</a></li>
        <li class="active">SolarTools</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        {{-- Extension Info --}}
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-sun-o"></i> SolarTools v1.0.0
                    </h3>
                </div>
                <div class="box-body">
                    <p>
                        <strong>SolarTools</strong> agrega dos funcionalidades a tu panel Pterodactyl:
                    </p>
                    <ul>
                        <li>
                            <strong>☀️ Solar AI:</strong> Botón de análisis inteligente de consola con Google Gemini.
                            Analiza errores, advertencias y problemas de rendimiento directamente desde la vista de consola del servidor.
                        </li>
                        <li>
                            <strong>🔔 Discord Webhooks:</strong> Sistema de notificaciones por webhook de Discord.
                            Recibe alertas cuando tu servidor cambia de estado (online, offline, reiniciando, etc).
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Gemini API Key Config --}}
        <div class="col-xs-12 col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-key"></i> Configuración de Gemini AI
                    </h3>
                </div>
                <div class="box-body">
                    <p>Para usar Solar AI, debes configurar tu clave de API de Google Gemini.</p>
                    <div class="callout callout-info">
                        <h4>Instrucciones</h4>
                        <ol>
                            <li>Obtén una API key en <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a></li>
                            <li>Añade la variable al archivo <code>.env</code> de tu panel:</li>
                        </ol>
                        <pre>GEMINI_API_KEY=tu_clave_aqui</pre>
                    </div>

                    @php
                        $geminiKey = env('GEMINI_API_KEY');
                    @endphp

                    @if($geminiKey)
                        <div class="callout callout-success">
                            <p><i class="fa fa-check"></i> API Key configurada: <code>{{ substr($geminiKey, 0, 6) }}...{{ substr($geminiKey, -4) }}</code></p>
                        </div>
                    @else
                        <div class="callout callout-danger">
                            <p><i class="fa fa-times"></i> La variable <code>GEMINI_API_KEY</code> no está configurada en el archivo <code>.env</code>.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="col-xs-12 col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-bar-chart"></i> Estadísticas
                    </h3>
                </div>
                <div class="box-body">
                    @php
                        $totalServers = \Pterodactyl\Models\Server::count();
                        $webhookServers = \Pterodactyl\Models\Server::whereNotNull('discord_webhook')->where('discord_webhook', '!=', '')->count();
                    @endphp

                    <div class="row">
                        <div class="col-xs-6">
                            <div class="info-box bg-aqua">
                                <span class="info-box-icon"><i class="fa fa-server"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Servidores</span>
                                    <span class="info-box-number">{{ $totalServers }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="info-box bg-purple">
                                <span class="info-box-icon"><i class="fa fa-bell"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Con Webhook</span>
                                    <span class="info-box-number">{{ $webhookServers }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
