<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email', 100);
            $table->string('password', 200);
            $table->string('avatar')->default('default.png');
            $table->string('cover')->default('cover.png');
            $table->string('city')->nullable();
            $table->string('work')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('token')->nullable();
        });

        Schema::create('userrelations', function(Blueprint $table) {
            $table->id();
            $table->integer('user_to');
            $table->integer('user_from');
        });

        Schema::create('posts', function(Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->string('type', 20);
            $table->dateTime('created_at');
            $table->text('body');
        });

        Schema::create('postlikes', function(Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_post');
            $table->dateTime('created_at');
        });

        Schema::create('postcomments', function(Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->integer('id_post');
            $table->dateTime('created_at');
            $table->text('body');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('userrelations');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('postslikes');
        Schema::dropIfExists('postcomments');
    }
}
