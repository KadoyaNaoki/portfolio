@extends('layouts.app')

@section('title', '変更履歴の登録')

@section('content')
	<h3>変更履歴の登録</h3>

	@if ($errors->any())
		<ul>
			@foreach ($errors->all() as $error)
				<li>{{ $error }}</li>
			@endforeach
		</ul>
	@endif

	<form class="edit-form" action="{{ route('change-histories.store') }}" method="POST">
	@csrf
	@formToken

		<label for="body">変更内容</label>
		<textarea id="body" name="body" rows="8" maxlength="20000"
		placeholder="変更した内容を入力してください（改行可・20000文字まで）"
		required>{{ old('body') }}</textarea>

		<p class="char-count"><span id="body-count">0</span> / 20000 文字</p>

		<button type="submit">登録する</button>
	</form>

	<p><a href="{{ route('change-histories.index') }}">一覧に戻る</a></p>
@endsection

@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function () {
		var body = document.getElementById('body');
		var counter = document.getElementById('body-count');

		if (!body || !counter) {
		return;
		}

		var update = function () {
		counter.textContent = body.value.length;
		};

		body.addEventListener('input', update);
		update();
		});
	</script>
@endpush
