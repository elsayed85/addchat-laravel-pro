<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcGuestsMessagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_guests_messages', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('m_to')->nullable()->default(0);
			$table->integer('m_from')->nullable()->default(0);
			$table->integer('g_to')->nullable()->default(0);
			$table->integer('g_from')->nullable()->default(0);
			$table->integer('messages_count')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_guests_messages');
	}

}
