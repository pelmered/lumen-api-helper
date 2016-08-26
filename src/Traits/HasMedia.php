<?php


namespace App\Traits;


trait HasMedia
{

    public function media()
    {
        return $this->morphMany('App\Media', 'resource');
    }
}
