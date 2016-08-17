<?php

namespace pelmered\RestTraits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;

class Media extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'caption', 'author', 'resource_id', 'resource_type',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];


    protected $rawImage;

    protected $imageFilePath;


    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);


        $this->imageFilePath = app()->basePath('public/media');
    }

    public function author()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
    public function resource()
    {
        return $this->morphTo();
    }

    public function setBase64( $base64 )
    {


        //$manager = new ImageManager(array('driver' => 'imagick'));
        $manager = new ImageManager();

        $this->rawImage = $manager->make($base64);
        //$image = $manager->make($base64);

        $this->rawImage->encode('jpg', 90);

        return $this;
    }

    public function generateImageSizes( )
    {
        $imageSizes = config('media.sizes');

        $image = $this->rawImage;

        $fileName = $this->getUniqueFileName();

        $image->backup();

        foreach($imageSizes AS $imageSize )
        {
            $image->fit($imageSize['size'][0],$imageSize['size'][1]);
            $image->sharpen($imageSize['sharpen']);

            $image->save( $this->imageFilePath.'/'.$fileName.$imageSize['suffix'].'.jpg' );

            $image->reset();
        }

        $this->file = $fileName;

        $this->save();

        return $this;
    }

    public function getUniqueFileName()
    {
        $i = 1;

        $imageName = $this->id.'_'.$i.'.jpg';

        while( file_exists($this->imageFilePath.'/'.$imageName) )
        {
            $imageName = $this->id.'_'.++$i.'.jpg';
        }

        return $this->id.'_'.++$i;
    }

    public function getFileUrl( $type = null )
    {
        $imageSizes = config('media.sizes');

        if( !$type )
        {
            $files = [];

            foreach( $imageSizes AS $imageSizeName => $imageSize )
            {
                $files[$imageSizeName] = url('media/'.$this->file.$imageSize['suffix'].'.jpg');
            }

            return $files;
        }

        //TODO: return specific image size

    }

}
