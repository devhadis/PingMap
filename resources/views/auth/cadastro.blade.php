@extends('layouts.pingmap')

@section('title', 'Cadastro | PingMap')

@section('content')
    <h1>PingMap</h1>
    <p>Crie sua conta para começar.</p>

    <form method="POST" action="{{ route('cadastro') }}">
        @csrf

        <label for="name">Nome</label>
        <input id="name" name="name" required
               value="{{ old('name') }}" autocomplete="name">
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" required
               value="{{ old('email') }}" autocomplete="email">
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="password">Senha (mínimo 12 caracteres)</label>
        <input id="password" name="password" type="password"
               required autocomplete="new-password">
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="password_confirmation">Confirmar senha</label>
        <input id="password_confirmation"
               name="password_confirmation"
               type="password" required autocomplete="new-password">

        <button type="submit">Criar minha conta</button>
    </form>

    <p class="footer">
        Já possui conta?
        <a href="{{ route('login') }}">Entrar</a>
    </p>
@endsection
