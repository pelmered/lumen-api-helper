<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image storage path
    |--------------------------------------------------------------------------
    |
    | Set the path where images should be stored. Leave out for default public/media
    |
    */
    'storagePath' => 'public/media',
    /*
    |--------------------------------------------------------------------------
    | Image sizes
    |--------------------------------------------------------------------------
    |
    | Set the image sizes that should be generated on upload
    |
    */
    'sizes' => [
        'image' => [
            'size'      => [500, 500],
            'suffix'    => '',
            'sharpen'   => 0
        ],
        'image_2x' => [
            'size'      => [1000, 1000],
            'suffix'    => '@2x',
            'sharpen'   => 0
        ],
        'image_3x' => [
            'size'      => [1500, 1500],
            'suffix'    => '@3x',
            'sharpen'   => 0
        ],
        'thumb' => [
            'size'      => [200, 200],
            'suffix'    => '_thumb',
            'sharpen'   => 0
        ],
    ],

];
