@extends('layouts.pingmap')

@section('title', 'Login | PingMap')

@section('content')
    <h1>PingMap</h1>
    <p>Entre na sua central de diagnósticos.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">E-mail</label>
        <input id="email" name="email" type="email"
               value="{{ old('email') }}"
               required autocomplete="email">

        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="password">Senha</label>
        <input id="password" name="password" type="password"
               required autocomplete="current-password">

        <button type="submit">Entrar</button>
    </form>

    <p class="footer">
        Ainda não possui conta?
        <a href="{{ route('cadastro') }}">Cadastre-se</a>
    </p>
@endsection
