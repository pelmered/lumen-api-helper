<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            //$table->integer('resource_id')->unsigned();
            //$table->string('resource_type', 50);
            $table->morphs('resource');

            $table->text('file');
            $table->string('title');
            $table->text('caption');

            $table->softDeletes();
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
        $this->removeAllMedaFiles();
        Schema::drop('media');
    }

    function removeAllMedaFiles()
    {
        $mediaDir = app()->basePath('public/media/');
        $dir = new DirectoryIterator( $mediaDir );
        /*
        var_dump($dir);
        die();
        */
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                //$mediaDir.$fileinfo->getFilename();
                unlink( $mediaDir.$fileinfo->getFilename() );
            }
        }

    }
}
