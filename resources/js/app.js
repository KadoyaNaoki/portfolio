import './bootstrap';

/**
 * サイドバー開閉（画面幅を問わずスライド開閉）
 *
 * 画面幅による出し分けは CSS 側が担当し、ここは状態の付け外しだけを行う。
 * これでデスクトップ・モバイルどちらでも同じ挙動になる。
 */
const initSidebar = () => {
	const toggle = document.getElementById('sidebar-toggle');
	const sidebar = document.getElementById('sidebar');
	const overlay = document.getElementById('sidebar-overlay');

	if (!toggle || !sidebar || !overlay) {
	return;
	}

	// 初期状態は閉じている（サーバー側で開いた状態は復元しない）
	const open = () => {
		sidebar.classList.add('is-open');
		overlay.hidden = false;
		overlay.classList.add('is-visible');
		toggle.setAttribute('aria-expanded', 'true');
		sidebar.setAttribute('aria-hidden', 'false');
	};

	const close = () => {
		sidebar.classList.remove('is-open');
		overlay.classList.remove('is-visible');
		overlay.hidden = true;
		toggle.setAttribute('aria-expanded', 'false');
		sidebar.setAttribute('aria-hidden', 'true');
	};

	toggle.addEventListener('click', () => {
	if (sidebar.classList.contains('is-open')) {
	close();
	} else {
	open();
	}
	});

	overlay.addEventListener('click', close);

	document.addEventListener('keydown', (event) => {
	if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
	close();
		toggle.focus();
	}
	});

	// リンク遷移時は閉じる（幅の判定は不要。開いていれば閉じる）
	sidebar.querySelectorAll('a').forEach((link) => {
	link.addEventListener('click', close);
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initSidebar);
} else {
	initSidebar();
}

/**
 * サイドメニューのアコーディオン開閉.
 *
 * 「ユーザー一覧」の右端にある + / − ボタンで、
 * マイページ・プロフィール編集のサブメニューを開閉する。
 * サーバーサイドで現在地がサブメニュー内なら、初期状態で開いておく。
 */
const initSidebarAccordion = () => {
	// 複数ページで使われる可能性を考え、対象を全部処理する
	document.querySelectorAll('.sidebar-toggle-plus').forEach((button) => {
	const targetId = button.getAttribute('aria-controls');
	const submenu = targetId ? document.getElementById(targetId) : null;

	if (!submenu) {
	return;
	}

	// 現在地がサブメニュー内なら初期から開く
	if (submenu.querySelector('.sidebar-link--sub.is-active')) {
		submenu.classList.add('is-open');
	button.classList.add('is-open');
	button.setAttribute('aria-expanded', 'true');
	}

	button.addEventListener('click', () => {
	const isOpen = submenu.classList.toggle('is-open');
	button.classList.toggle('is-open', isOpen);
	button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
	});
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initSidebarAccordion);
} else {
	initSidebarAccordion();
}

/**
 * 送信ボタンの連打対策.
 *
 * フォーム送信後にボタンを無効化し、二重送信を防ぐ。
 * 対象は「送信系のボタン」すべて（ログイン・登録・更新・一括削除など）。
 *
 * ポイント:
 *   - 対象フォームには hidden の _token がある（@csrf）ので送信内容は変わらない
 *   - 検索フォーム（GET）は連打されても副作用がないため無効化しない
 *   - 送信をキャンセルした場合（confirm のキャンセル等）は元に戻す
 *   - bfcache から戻ってきた場合に備え、pageshow で状態を復元する
 */
const initSubmitGuard = () => {
	// GET の検索フォームは副作用がないので対象外（連打しても壊れない）
	const isSearchForm = (form) => {
		return form && form.method.toLowerCase() === 'get';
	};

	// 一度押されたら「送信中」の見た目にして押せなくする
	const lock = (button) => {
	// 二重ロックを避ける
		if (button.dataset.submitting === 'true') {
			return false;
	}

	button.dataset.submitting = 'true';
	button.dataset.originalText = button.textContent;
	button.disabled = true;
	button.classList.add('is-submitting');
	button.textContent = '送信中…';

		return true;
	};

	// 送信されなかった場合に元に戻す
	const unlock = (button) => {
		if (!button || button.dataset.submitting !== 'true') {
			return;
	}

	button.disabled = false;
	button.classList.remove('is-submitting');
	button.textContent = button.dataset.originalText || button.textContent;
		delete button.dataset.submitting;
	};

	// フォーム送信時にまとめてロックする
	document.addEventListener('submit', (event) => {
		const form = event.target;

		if (!(form instanceof HTMLFormElement) || isSearchForm(form)) {
			return;
	}

	// このフォームに紐づく送信ボタンを全部ロックする
	// （form="..." 属性で別フォームから送信する削除ボタンも含める）
		const buttons = Array.from(
			document.querySelectorAll('button[type="submit"], input[type="submit"]'),
	).filter((b) => b.form === form || b.getAttribute('form') === form.id);

	buttons.forEach((b) => lock(b));
	});

	// confirm() のキャンセルやバリデーション失敗で送信されなかった場合に戻す
	// （submit イベントは「送信が実行された」ときだけ発火するので、
	//  キャンセル時は pagehide が来ない。一定時間後に状態を見て戻す）
	document.addEventListener('click', (event) => {
		const button = event.target.closest('button[type="submit"], input[type="submit"]');

		if (!button) {
			return;
	}

		const form = button.form
			|| (button.getAttribute('form') ? document.getElementById(button.getAttribute('form')) : null);

		if (!form || isSearchForm(form)) {
			return;
	}

	// 送信が始まらなかった場合（confirm キャンセル等）に備えて解除する。
	// 実際に送信されればページが遷移するので、この解除は走らない。
	window.setTimeout(() => {
			if (document.visibilityState === 'visible') {
				unlock(button);
			}
	}, 1500);
	});

	// ブラウザの「戻る」で戻ってきた時にボタンが無効のままになるのを防ぐ
	window.addEventListener('pageshow', (event) => {
		if (!event.persisted) {
			return;
	}

	document.querySelectorAll('[data-submitting="true"]').forEach((b) => unlock(b));
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initSubmitGuard);
} else {
	initSubmitGuard();
}
