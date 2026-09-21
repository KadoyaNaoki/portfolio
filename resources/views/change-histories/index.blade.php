@extends('layouts.app')

@section('title', '変更履歴')

@section('content')
	<h3>変更履歴</h3>

	{{-- 削除フォーム（チェックボックスは form 属性でこのフォームに紐づける）--}}
	<form id="histories-delete-form" action="{{ route('change-histories.destroy') }}" method="POST">
	@csrf
	@formToken
	@method('DELETE')
	</form>

	<div class="page-actions">
	@can('create', App\Models\ChangeHistory::class)
	<a href="{{ route('change-histories.create') }}" class="btn-primary">＋ 変更履歴を登録する</a>
	@endcan
	</div>

	<form class="search-form" action="{{ route('change-histories.index') }}" method="GET">
	<input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="変更内容・登録者で絞り込み">
	<button type="submit">検索</button>
	</form>

	@forelse ($histories as $history)
	<article class="history-item">
			<header class="history-item-head">
				@can('delete', App\Models\ChangeHistory::class)
				<input type="checkbox" name="ids[]" value="{{ $history->id }}"
					form="histories-delete-form" class="row-check"
					aria-label="この履歴を選択">
				@endcan
				<time>{{ $history->registered_at?->format('Y/m/d H:i') }}</time>
				<span class="history-item-author">
					{{ $history->user?->name ?? '（退会済み）' }}
					@if ($history->userid)
						（{{ $history->userid }}）
					@endif
				</span>
			</header>

			{{-- 長文・改行をそのまま表示する --}}
			<div class="history-item-body">{{ $history->body }}</div>
	</article>
		@empty
		<p>変更履歴がありません。</p>
		@endforelse
		{{-- 削除操作は一覧の下にまとめる（ユーザー一覧と同じ位置）--}}
		@can('delete', App\Models\ChangeHistory::class)
		@if ($histories->isNotEmpty())
		<p class="bulk-action">
		<label class="check-all-label">
		<input type="checkbox" id="check-all">
		すべて選択
		</label>
		<button type="submit" form="histories-delete-form" class="btn-danger"
			onclick="return confirm('選択した変更履歴を削除します。よろしいですか？');">
		選択した履歴を削除
		</button>
		</p>
		@endif
		@endcan
		{{ $histories->links() }}
	@endsection

@push('scripts')
	<script>
	// 「すべて選択」で全選択 / 全解除する
	document.addEventListener('DOMContentLoaded', function () {
	var all = document.getElementById('check-all');
	if (!all) {
	return;
	}

	var rows = function () {
	return Array.prototype.slice.call(document.querySelectorAll('.row-check'));
	};

	var syncAll = function () {
	var list = rows();
	var checked = list.filter(function (c) { return c.checked; }).length;
	all.checked = list.length > 0 && checked === list.length;
	all.indeterminate = checked > 0 && checked < list.length;
	};

	all.addEventListener('change', function () {
	var on = all.checked;
	rows().forEach(function (cb) { cb.checked = on; });
	syncAll();
	});

	document.querySelectorAll('.row-check').forEach(function (cb) {
	cb.addEventListener('change', syncAll);
	});
	});
	</script>
@endpush
