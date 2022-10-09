<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcGuestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_guests', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('fullname', 256);
			$table->string('email', 256);
			$table->integer('user_id')->nullable();
			$table->boolean('status')->default(1);
			$table->timestamp('dt_created')->default(\DB::raw('CURRENT_TIMESTAMP'));
			$table->dateTime('dt_updated')->default(\Carbon\Carbon::now());
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_guests');
	}

}
