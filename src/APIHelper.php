<?php

namespace pelmered\APIHelper;

use Illuminate\Support\Facades\Facade;

class APIHelper extends Facade
{
    public static function getAccessControlheaders()
    {
        $config = config('api-helper');

        return [
            //'Access-Control-Allow-Origin'   => $config['AllowOriginURL'],
            'Access-Control-Allow-Origin'   => '*',
            'Access-Control-Allow-Methods'  => 'POST, GET, OPTIONS, PUT, PATCH, DELETE',
            'Access-Control-Allow-Headers'  => 'Content-Type, X-Auth-Token, Origin, Authorization, Token, Accept',
        ];
    }

    public static function getExceptionMessage($exception )
    {
        $data = json_decode($exception->getMessage());

        if( !$data )
        {
            return false;
        }
        return $data;
    }

    private static function getTransformer($resourceType)
    {
        $transformerPath = '\App\Transformers\\'.$resourceType.'Transformer';
        return new $transformerPath();
    }

    public static function transformCollection($resource, $resourceType, $includes = [])
    {
        $transformer = static::getTransformer($resourceType);

        return $transformer->transformCollection($resource);
    }

    public static function transform($resource, $resourceType, $includes = [])
    {
        $transformer = static::getTransformer($resourceType);

        if(!isset($_GET['include']))
        {
            return $transformer->transform($resource);
        }

        $includes = explode(',', $_GET['include']);

        $extraData = [];

        if(is_array($includes) && !empty($includes))
        {
            foreach($includes AS $include)
            {
                $methodName = 'include'.ucfirst($include);

                if(method_exists($transformer, $methodName) )
                {
                    $includeObject = $transformer->$methodName($resource);

                    if(is_a($includeObject, 'League\Fractal\Resource\item'))
                    {
                        $extraData[$include] = $includeObject->getData();
                    }
                    else
                    {
                        //$extraData[$include] = $includeObject->toArray();
                    }

                    //call_user_func([$transformer, $methodName], $resource);
                }

            }
        }

        return array_merge($transformer->transform($resource), $extraData);
    }

    public static function stripNameSpace($path)
    {
        if ($pos = strrpos($path, '\\')) {
            $path = substr($path, $pos + 1);
        }

        return $path;
    }

    public static function sanitizeArrayofInts($array)
    {
        return filter_var($array, FILTER_VALIDATE_INT, array(
            'flags'     => FILTER_REQUIRE_ARRAY,
            'options'   => array('min_range' => 1)
        ));
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    public static function getUniqueFileName($fileName, $fileSavePath)
    {
        if (!file_exists($fileSavePath.'/'.$fileName))
        {
            return $fileName;
        }

        $fileParts = pathinfo($fileName);
        $fileExtension = $fileParts['extension'];
        $fileBaseName = $fileParts['filename'];

        $iterator = 1;

        $fileName = $fileBaseName.'_'.$iterator.'.'.$fileExtension;

        while (file_exists($fileSavePath.'/'.$fileName)) {
            $fileName = $fileBaseName.'_'.++$iterator.'.'.$fileExtension;
        }

        return $fileBaseName.'_'.++$iterator.'.'.$fileExtension;
    }

}



