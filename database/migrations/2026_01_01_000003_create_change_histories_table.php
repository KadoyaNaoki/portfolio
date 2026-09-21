<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('change_histories', function (Blueprint $table) {
			$table->id();
			// 登録者（退会しても履歴は残す）
			$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
			// 登録時のユーザーIDを控えておく（退会後も誰の記録か分かるように）
			$table->string('userid')->nullable();
			// 変更内容の本文。それなりに長い文章を想定して TEXT 型
			$table->text('body');
			// 履歴登録日（登録日時を持たせる）
			$table->timestamp('registered_at')->nullable();
			$table->timestamps();

			$table->index('registered_at');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('change_histories');
	}
};
