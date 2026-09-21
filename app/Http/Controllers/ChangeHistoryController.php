<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteChangeHistoriesRequest;
use App\Http\Requests\StoreChangeHistoryRequest;
use App\Models\ChangeHistory;
use App\Services\ChangeHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChangeHistoryController extends Controller
{
	public function __construct(
		private readonly ChangeHistoryService $changeHistoryService,
	) {}

	/**
	 * 一覧（一般・管理者とも閲覧可）.
	 */
	public function index(Request $request): View
	{
		$histories = $this->changeHistoryService->search($request->query('keyword'));

		return view('change-histories.index', compact('histories'));
	}

	/**
	 * 登録フォーム（管理者のみ）.
	 */
	public function create(): View
	{
		Gate::authorize('create', ChangeHistory::class);

		return view('change-histories.create');
	}

	/**
	 * 登録処理（管理者のみ）.
	 */
	public function store(StoreChangeHistoryRequest $request): RedirectResponse
	{
		$this->changeHistoryService->create(Auth::user(), $request->validated()['body']);

		return redirect()->route('change-histories.index')
			->with('status', '変更履歴を登録しました。');
	}

	/**
	 * チェックされた変更履歴を一括削除する（管理者のみ）.
	 */
	public function destroy(DeleteChangeHistoriesRequest $request): RedirectResponse
	{
		$deleted = $this->changeHistoryService->deleteByIds($request->validated()['ids']);

		return redirect()->route('change-histories.index')
			->with('status', $deleted.' 件の変更履歴を削除しました。');
	}
}
