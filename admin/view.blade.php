@extends('layouts.admin')

@section('title')
    SolarTools
@endsection

@section('content-header')
    <h1>SolarTools<small>Solar AI Console + Discord Webhooks</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">SolarTools</li>
    </ol>
@endsection

@section('content')
    <div class="row">
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
                            <strong>Solar AI:</strong> Boton de analisis inteligente de consola con Google Gemini.
                        </li>
                        <li>
                            <strong>Discord Webhooks:</strong> Sistema de notificaciones por webhook de Discord.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-key"></i> Configuracion de Gemini AI
                    </h3>
                </div>
                <div class="box-body">
                    <div class="callout callout-info">
                        <h4>Instrucciones</h4>
                        <ol>
                            <li>Obten una API key en <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a></li>
                            <li>Anade la variable al archivo <code>.env</code> de tu panel:</li>
                        </ol>
                        <pre>GEMINI_API_KEY=tu_clave_aqui</pre>
                        <p>Luego ejecuta: <code>php artisan config:clear</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
