<?php

namespace App\Http\Controllers;

use App\Services\LoginHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginHistoryController extends Controller
{
	public function __construct(
		private readonly LoginHistoryService $loginHistoryService,
	) {}

	public function index(Request $request): View
	{
		$histories = $this->loginHistoryService->listFor(
			Auth::user(),
			$request->query('keyword'),
		);

		return view('login-histories.index', compact('histories'));
	}
}
