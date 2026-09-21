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
		Schema::create('users', function (Blueprint $table) {
			$table->id();
			$table->string('userid')->unique();
			$table->string('name');
			$table->string('password');
			$table->tinyInteger('role')->default(1);
			$table->string('avatar_path')->nullable();
			$table->timestamps();
		});

		Schema::create('login_histories', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
			$table->string('userid')->nullable();
			$table->string('ip_address', 45)->nullable();
			$table->text('user_agent')->nullable();
			$table->boolean('success')->default(false);
			$table->timestamp('logged_in_at')->nullable();
			$table->timestamps();
		});

		Schema::create('sessions', function (Blueprint $table) {
			$table->string('id')->primary();
			$table->foreignId('user_id')->nullable()->index();
			$table->string('ip_address', 45)->nullable();
			$table->text('user_agent')->nullable();
			$table->longText('payload');
			$table->integer('last_activity')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('login_histories');
		Schema::dropIfExists('users');
		Schema::dropIfExists('sessions');
	}
};
