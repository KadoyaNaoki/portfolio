@extends('layouts.app')

@section('title', '新規登録')

@section('content')
	<h3>新規登録</h3>

	@if ($errors->any())
		<ul>
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	@endif

	<form action="{{ route('register.store') }}" method="POST">
	@csrf
	@formToken

		<label for="userid">ユーザーID（半角英数字・4〜30文字）</label>
		<input type="text" id="userid" name="userid" value="{{ old('userid') }}" required>

		<label for="name">表示名</label>
		<input type="text" id="name" name="name" value="{{ old('name') }}" required>

		<label for="password">パスワード（8文字以上）</label>
		<input type="password" id="password" name="password" required>

		<label for="password_confirmation">パスワード（確認）</label>
		<input type="password" id="password_confirmation" name="password_confirmation" required>

		<button type="submit">登録する</button>
	</form>

	<div class="link">
		<a href="{{ route('login') }}">ログインはこちら</a>
	</div>
@endsection
