@auth('web')
	<!DOCTYPE html>
	<html lang="ja">
	<head>
	<meta charset="UTF-8">
	<title>@yield('title')</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	@vite(['resources/css/style.css', 'resources/js/app.js'])
	</head>

	<body class="has-sidebar">
	@include('layouts.sidebar')

	<div class="main">
			<header class="topbar">
				{{-- ハンバーガー（全幅で表示。サイドバーの開閉トグル）--}}
				<button type="button" id="sidebar-toggle" class="sidebar-toggle"
					aria-controls="sidebar" aria-expanded="false" aria-label="メニューを開閉する">
					<span></span><span></span><span></span>
				</button>

				<h1 class="topbar-title">@yield('title')</h1>

				<span class="topbar-user">
					{{ Auth::user()->name }}（{{ Auth::user()->role->label() }}）
				</span>
			</header>

			<div class="container">
				@if (session('status'))
					<p class="status">{{ session('status') }}</p>
				@endif

						@yield('content')
					</div>
					</div>

					@stack('scripts')
					</body>
					</html>
				@else
	<!DOCTYPE html>
	<html lang="ja">
	<head>
	<meta charset="UTF-8">
	<title>@yield('title')</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	@vite(['resources/css/style.css', 'resources/js/app.js'])
	</head>

	<body>
	<header class="topbar">
	<h1 class="topbar-title">@yield('title')</h1>
	</header>

	<div class="container">
			@if (session('status'))
				<p class="status">{{ session('status') }}</p>
			@endif

			@yield('content')
	</div>

	@stack('scripts')
	</body>
	</html>
@endauth
