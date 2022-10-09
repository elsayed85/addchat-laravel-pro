<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAcContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ac_contacts', function(Blueprint $table)
		{
			$table->integer('users_id');
			$table->integer('contacts_id');
			$table->dateTime('dt_updated');
			$table->unique(['users_id','contacts_id'], 'users_id_2');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ac_contacts');
	}

}
