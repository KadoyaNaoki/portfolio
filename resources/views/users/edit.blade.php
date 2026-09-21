@extends('layouts.app')

@section('title', 'プロフィール編集')

@section('content')
	<h3>プロフィール編集</h3>

	@if ($errors->any())
		<ul>
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	@endif

	<form class="edit-form" action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
	@csrf
	@formToken
	@method('PATCH')

		<label for="userid">ユーザーID</label>
		<input type="text" id="userid" name="userid" value="{{ old('userid', $user->userid) }}" required>

		<label for="name">表示名</label>
		<input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>

		<label for="password">パスワード（変更する場合のみ）</label>
		<input type="password" id="password" name="password" autocomplete="new-password">

		<label for="password_confirmation">パスワード（確認）</label>
		<input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">

		@if ($user->avatarUrl())
			<p>現在の画像：<img src="{{ $user->avatarUrl() }}" alt="現在の画像" width="100"></p>
		@endif
		<label for="avatar">プロフィール画像（2MBまで）</label>
		<input type="file" id="avatar" name="avatar" accept="image/*">

			{{-- ロール変更は管理者のみ（Policy の changeRole）--}}
		@can('changeRole', $user)
		<label for="role">ロール</label>
			<select id="role" name="role">
				@foreach ($roles as $value => $label)
					<option value="{{ $value }}" @selected(old('role', $user->role->value) == $value)>{{ $label }}</option>
				@endforeach
				</select>
			@endcan
			<button type="submit">更新する</button>
	</form>

	<p><a href="{{ route('users.show', $user) }}">詳細に戻る</a></p>
@endsection
