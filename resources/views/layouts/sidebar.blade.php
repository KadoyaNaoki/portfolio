@auth
	<aside id="sidebar" class="sidebar" aria-hidden="true">
	{{-- ① ユーザー情報ブロック（クリックでマイページへ）--}}
	<a href="{{ route('users.show', Auth::user()) }}" class="sidebar-user sidebar-user--link">
			@if (Auth::user()->avatarUrl())
				<img src="{{ Auth::user()->avatarUrl() }}" alt="{{ Auth::user()->name }}のアイコン" class="sidebar-avatar">
			@else
				<span class="sidebar-avatar sidebar-avatar--empty">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
			@endif

			<div class="sidebar-user-meta">
				<strong class="sidebar-user-name">{{ Auth::user()->name }}</strong>
				<span class="role-badge role-badge--{{ Auth::user()->isAdmin() ? 'admin' : 'user' }}">
					{{ Auth::user()->role->label() }}
				</span>
			</div>
	</a>

	<nav class="sidebar-nav">
			{{-- ユーザー一覧（管理者はラベルを「ユーザー管理」に）--}}
			{{-- 右端の + を押すとマイページ・プロフィール編集をアコーディオン表示 --}}
			<div class="sidebar-group">
				<a href="{{ route('users.index') }}"
					class="sidebar-link sidebar-link--parent @if (request()->routeIs('users.index')) is-active @endif">
					<span>{{ Auth::user()->isAdmin() ? 'ユーザー管理' : 'ユーザー一覧' }}</span>
				</a>

				<button type="button" class="sidebar-toggle-plus"
					aria-expanded="false" aria-controls="sidebar-users-sub"
					aria-label="ユーザー関連のメニューを開閉する">
					<span class="plus-icon">+</span>
				</button>
			</div>

			<div id="sidebar-users-sub" class="sidebar-submenu">
				<a href="{{ route('users.show', Auth::user()) }}"
					class="sidebar-link sidebar-link--sub @if (request()->routeIs('users.show') && request()->route('user')?->id === Auth::id()) is-active @endif">
					マイページ
				</a>

				<a href="{{ route('users.edit', Auth::user()) }}"
					class="sidebar-link sidebar-link--sub @if (request()->routeIs('users.edit') && request()->route('user')?->id === Auth::id()) is-active @endif">
					プロフィール編集
				</a>
			</div>

			{{-- ログイン履歴 --}}
			<a href="{{ route('login-histories.index') }}"
				class="sidebar-link @if (request()->routeIs('login-histories.*')) is-active @endif">
				ログイン履歴
			</a>

			{{-- 変更履歴（登録は一覧ページ内のボタンから行う）--}}
			<a href="{{ route('change-histories.index') }}"
				class="sidebar-link @if (request()->routeIs('change-histories.*')) is-active @endif">
				変更履歴
			</a>
	</nav>

	<div class="sidebar-footer">
			<form action="{{ route('logout') }}" method="POST">
			@csrf
			@formToken
			<button type="submit" class="sidebar-logout">ログアウト</button>
			</form>
	</div>
	</aside>

	{{-- 開閉時に背後のコンテンツを覆うオーバーレイ --}}
	<div id="sidebar-overlay" class="sidebar-overlay" hidden></div>
@endauth
