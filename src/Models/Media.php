<?php

namespace pelmered\APIHelper\Models;

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

    protected $config;

    protected $rawImage;

    protected $imageFilePath;


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setupConfig();
    }

    private function setupConfig()
    {
        $this->config = config('media');

        if (!isset($this->config['storagePath']))
        {
            $this->imageFilePath = app()->basePath('public/media');
        }
        else
        {
            $this->imageFilePath = app()->basePath($this->config['storagePath']);
        }
    }

    /*
    public function author()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
    */

    public function resource()
    {
        return $this->morphedByMany();
        return $this->morphTo();
    }

    protected function getImageManager()
    {
        //$manager = new ImageManager(array('driver' => 'imagick'));
        $manager = new ImageManager();

        return $manager;
    }

    public function fetchRemoteFile($uri)
    {
        $localPath = '/tmp/'.basename($uri);

        copy($uri, $localPath);

        $manager = $this->getImageManager();

        $this->rawImage = $manager->make($localPath)->encode('jpg', 90);

        return $this;
    }
    public function fetchLocalFile($uri, $uploadsDir)
    {
        $split = explode('wp-content/uploads', $uri);
        if (!isset($split[1]))
        {
            return $this;
        }

        $localPath = $uploadsDir.$split[1];

        $manager = $this->getImageManager();

        $this->rawImage = $manager->make($localPath)->encode('jpg', 90);

        return $this;
    }

    public function setBase64($base64)
    {
        $manager = $this->getImageManager();

        $this->rawImage = $manager->make($base64)->encode('jpg', 90);

        return $this;
    }

    public function generateImageSizes($fileName = '')
    {
        if(!isset($this->rawImage))
        {
            return $this;
        }
        $imageSizes = $this->config['sizes'];

        $image = $this->rawImage;

        $file = $this->extractFileExtension($fileName);

        $fileBaseName = $this->getUniqueFileName($file['basename'], $file['extension']);

        $image->backup();

        $actualSizes = [];

        foreach ($imageSizes as $imageSizeName => $imageSize) {
            $image->fit($imageSize['size'][0], $imageSize['size'][1]);
            $image->sharpen($imageSize['sharpen']);

            $filePath = $this->imageFilePath.'/'.$fileBaseName.$imageSize['suffix'].'.'.$file['extension'];
            $image->save($filePath);

            $size = getimagesize($filePath);
            $actualSizes[$imageSizeName] = [
                'width'     => $size[0],
                'height'    => $size[1],
            ];

            $image->reset();
        }

        $this->file = $fileBaseName.'.'.$file['extension'];
        $this->actual_sizes = json_encode($actualSizes);

        $this->save();

        return $this;
    }

    public function getUniqueFileName($fileBaseName, $fileExtension)
    {
        if (!file_exists($this->imageFilePath.'/'.$fileBaseName.'.'.$fileExtension))
        {
            return $fileBaseName;
        }


        $iterator = 1;

        $fileName = $fileBaseName.'_'.$iterator.'.'.$fileExtension;

        while (file_exists($this->imageFilePath.'/'.$fileName)) {
            $fileName = $fileBaseName.'_'.++$iterator.'.'.$fileExtension;
        }

        return $fileBaseName.'_'.++$iterator;
    }

    public function extractFileExtension( $fileName )
    {
        $dotPos = strrpos($fileName, '.');

        return [
            'basename'  => substr($fileName, 0, $dotPos),
            'extension' => substr($fileName, $dotPos+1)
        ];
    }

    public function getFileUrl($type = null)
    {
        $imageSizes = config('media.sizes');

        if (!$type) {
            $files = [];

            foreach ($imageSizes as $imageSizeName => $imageSize) {
                $files[$imageSizeName] = url('media/'.$this->file.$imageSize['suffix'].'.jpg');
            }

            return $files;
        }

        //TODO: return specific image size
    }

    public function getFileSizes()
    {
        $imageSizes = config('media.sizes');

        $files = [];

        $actualSizes = json_decode($this->actual_sizes, true);


        $file = $this->extractFileExtension($this->file);

        foreach ($imageSizes as $imageSizeName => $imageSize)
        {

            $fileName = $file['basename'].$imageSize['suffix'].'.'.$file['extension'];

            if (file_exists($this->imageFilePath.'/'.$fileName))
            {

                $files[$imageSizeName] = $actualSizes[$imageSizeName] + [
                    'src' => url('media/'.$fileName)
                ];

            }
            else
            {
                die($this->imageFilePath.'/'.$fileName);
            }

        }

        return $files;
    }
}
