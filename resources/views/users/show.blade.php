@extends('layouts.app')

@section('title', 'ユーザー詳細')

@section('content')
	<h3>ユーザー詳細</h3>

	@if ($user->avatarUrl())
		<p><img src="{{ $user->avatarUrl() }}" alt="プロフィール画像" width="120"></p>
	@endif

	<p>ユーザーID：{{ $user->userid }}</p>
	<p>表示名：{{ $user->name }}</p>
	<p>ロール：{{ $user->role->label() }}</p>
	<p>登録日：{{ $user->created_at->format('Y/m/d H:i') }}</p>

	@can('update', $user)
		<p><a href="{{ route('users.edit', $user) }}">編集する</a></p>
	@endcan

	<p><a href="{{ route('users.index') }}">一覧に戻る</a></p>
@endsection
