<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcBlockedTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_blocked', function(Blueprint $table)
		{
			$table->integer('users_id')->nullable();
			$table->integer('blocked_users_id')->nullable();
			$table->boolean('is_reported')->default(0);
			$table->dateTime('dt_updated')->nullable();
			$table->unique(['users_id','blocked_users_id'], 'user_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_blocked');
	}

}
