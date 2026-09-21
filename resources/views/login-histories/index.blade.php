@extends('layouts.app')

@section('title', 'ログイン履歴')

@section('content')
	<h3>ログイン履歴</h3>

	@if (Auth::user()->isAdmin())
		<form class="search-form" action="{{ route('login-histories.index') }}" method="GET">
			<input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="ユーザーID・表示名で絞り込み">
			<button type="submit">検索</button>
		</form>
	@endif

	<table>
		<thead>
			<tr>
				<th>日時</th>
				<th>ユーザーID</th>
				<th>表示名</th>
				<th>結果</th>
				<th>IP</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($histories as $history)
				<tr>
					<td>{{ $history->logged_in_at?->format('Y/m/d H:i:s') }}</td>
					<td>{{ $history->userid }}</td>
					<td>{{ $history->user?->name ?? '（退会済み）' }}</td>
					<td>{{ $history->success ? '成功' : '失敗' }}</td>
					<td>{{ $history->ip_address }}</td>
				</tr>
			@empty
				<tr><td colspan="5">履歴がありません。</td></tr>
			@endforelse
		</tbody>
	</table>

	{{ $histories->links() }}
@endsection
