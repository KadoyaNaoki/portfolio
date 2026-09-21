@extends('layouts.app')

@section('title', Auth::user()->isAdmin() ? 'ユーザー管理' : 'ユーザー一覧')

@section('content')
	<h3>{{ Auth::user()->isAdmin() ? 'ユーザー管理' : 'ユーザー一覧' }}</h3>

	{{-- 削除はフォーム全体を 1 つにまとめる（チェックボックスはこの中に入れる）--}}
	<form id="users-delete-form" action="{{ route('users.destroy') }}" method="POST">
	@csrf
	@formToken
	@method('DELETE')
	</form>

	<form class="search-form" action="{{ route('users.index') }}" method="GET">
	<input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="ユーザーID・表示名で検索">
	<button type="submit">検索</button>
	</form>

	<table>
	<thead>
			<tr>
				@can('delete', App\Models\User::class)
				<th class="col-check">
					<input type="checkbox" id="check-all" aria-label="すべて選択">
				</th>
				@endcan
				<th><a href="@sortLink('userid')">ユーザーID<span>@sortMark('userid')</span></a></th>
				<th><a href="@sortLink('name')">表示名<span>@sortMark('name')</span></a></th>
				<th>ロール</th>
				<th><a href="@sortLink('created_at')">登録日<span>@sortMark('created_at')</span></a></th>
			</tr>
	</thead>
	<tbody>
			@forelse ($users as $user)
				<tr>
					@can('delete', App\Models\User::class)
					<td class="col-check">
						<input type="checkbox" name="ids[]" value="{{ $user->id }}"
							form="users-delete-form" class="row-check"
							@disabled($user->id === Auth::id())
							aria-label="{{ $user->userid }} を選択">
					</td>
					@endcan
					<td><a href="{{ route('users.show', $user) }}">{{ $user->userid }}</a></td>
					<td>{{ $user->name }}</td>
					<td>{{ $user->role->label() }}</td>
					<td>{{ $user->created_at->format('Y/m/d H:i') }}</td>
				</tr>
			@empty
				<tr><td colspan="{{ Auth::user()->isAdmin() ? 5 : 4 }}">ユーザーが見つかりません。</td></tr>
			@endforelse
	</tbody>
	</table>

	@can('delete', App\Models\User::class)
	<p class="bulk-action">
	<label class="check-all-label">
	<input type="checkbox" id="check-all-bottom">
	すべて選択
	</label>
	<button type="submit" form="users-delete-form" class="btn-danger"
		onclick="return confirm('選択したユーザーを削除します。よろしいですか？');">
	選択したユーザーを削除
	</button>
	<span class="bulk-note">※ 自分自身は削除できません。</span>
	</p>
	@endcan

	{{ $users->links() }}
@endsection

@push('scripts')
	<script>
	// ヘッダーと下部の「すべて選択」を同期させる
	document.addEventListener('DOMContentLoaded', function () {
	// すべて選択用のチェックボックスは 2 つある（テーブルヘッダーと一覧の下）
	var allBoxes = Array.prototype.slice.call(document.querySelectorAll('#check-all, #check-all-bottom'));
	if (allBoxes.length === 0) {
	return;
	}

	// 自分自身は disabled なので、選択対象から除く
	var rows = function () {
	return Array.prototype.slice.call(document.querySelectorAll('.row-check:not([disabled])'));
	};

	var syncAll = function () {
	var list = rows();
	var checked = list.filter(function (c) { return c.checked; }).length;
	allBoxes.forEach(function (box) {
	box.checked = list.length > 0 && checked === list.length;
	box.indeterminate = checked > 0 && checked < list.length;
	});
	};

	allBoxes.forEach(function (box) {
	box.addEventListener('change', function () {
	var on = box.checked;
	rows().forEach(function (cb) { cb.checked = on; });
		syncAll();
	});
	});

	document.querySelectorAll('.row-check').forEach(function (cb) {
	cb.addEventListener('change', syncAll);
	});
	});
	</script>
@endpush
