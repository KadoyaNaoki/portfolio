@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
	<h3>ログイン</h3>

	@if ($errors->any())
		<ul>
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	@endif

	<form action="{{ route('login.attempt') }}" method="POST">
	@csrf
	@formToken

		<label for="userid">ユーザーID</label>
		<input type="text" id="userid" name="userid" value="{{ old('userid') }}" required>

		<label for="password">パスワード</label>
		<input type="password" id="password" name="password" required>

		<button type="submit">ログイン</button>
	</form>

	<div class="link">
		<a href="{{ route('register') }}">新規アカウント作成はこちら</a>
	</div>
@endsection
