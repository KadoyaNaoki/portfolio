@if ($paginator->hasPages())
	<nav class="pagination" role="navigation" aria-label="ページ送り">
	{{-- 前へ --}}
	@if ($paginator->onFirstPage())
			<span class="pagination-item is-disabled" aria-disabled="true">前へ</span>
	@else
			<a href="{{ $paginator->previousPageUrl() }}" class="pagination-item" rel="prev">前へ</a>
	@endif

	{{-- ページ番号 --}}
	@foreach ($elements as $element)
			{{-- 「…」の区切り --}}
			@if (is_string($element))
				<span class="pagination-item is-disabled" aria-disabled="true">{{ $element }}</span>
			@endif

			{{-- ページ番号のリンク --}}
			@if (is_array($element))
				@foreach ($element as $page => $url)
					@if ($page == $paginator->currentPage())
						<span class="pagination-item is-current" aria-current="page">{{ $page }}</span>
					@else
						<a href="{{ $url }}" class="pagination-item">{{ $page }}</a>
					@endif
				@endforeach
			@endif
	@endforeach

	{{-- 次へ --}}
	@if ($paginator->hasMorePages())
			<a href="{{ $paginator->nextPageUrl() }}" class="pagination-item" rel="next">次へ</a>
	@else
			<span class="pagination-item is-disabled" aria-disabled="true">次へ</span>
	@endif
	</nav>

	{{-- 件数の表示 --}}
	<p class="pagination-summary">
	{{ $paginator->firstItem() }}〜{{ $paginator->lastItem() }} 件目 / 全 {{ $paginator->total() }} 件
	</p>
@endif
