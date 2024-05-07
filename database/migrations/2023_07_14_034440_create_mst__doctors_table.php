<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMstDoctorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mst__doctors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('designation_id');
            $table->string('qualification');
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('mst_users')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('mst_branches')->onDelete('cascade');
            $table->foreign('designation_id')->references('id')->on('mst__designations')->onDelete('cascade');
            $table->timestamps();
        });
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mst__doctors');
    }
}
