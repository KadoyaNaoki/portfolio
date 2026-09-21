<?php

namespace App\Providers;

use App\Repositories\Contracts\ChangeHistoryRepositoryInterface;
use App\Repositories\Contracts\LoginHistoryRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ChangeHistoryRepository;
use App\Repositories\Eloquent\LoginHistoryRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
	/**
	 * インターフェースと実装のバインド.
	 */
	public array $bindings = [
		UserRepositoryInterface::class => UserRepository::class,
		LoginHistoryRepositoryInterface::class => LoginHistoryRepository::class,
		ChangeHistoryRepositoryInterface::class => ChangeHistoryRepository::class,
	];

	public function register(): void
	{
		//
	}

	public function boot(): void
	{
		//
	}
}
