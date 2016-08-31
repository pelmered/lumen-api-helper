<?php

namespace pelmered\APIHelper;

class APIHelper
{

    public static function getExceptionMessage( $e )
    {
        $data = json_decode($e->getMessage());

        if( !$data )
        {
            return false;
        }
        return $data;
    }

    public static function elasticsearchToCollection( $esdata, $collect = false, $distanceQuery = false )
    {
        if( !isset($esdata['hits']['hits']) || !is_array($esdata['hits']['hits']) )
        {
            return false;
        }

        $data = [];

        //TODO: Maybe we should just pull the venues from DB / cache using the IDs instead of converting the array?

        foreach( $esdata['hits']['hits'] AS $hit )
        {
            if(isset($hit['_source']['location']['lat']) && $hit['_source']['location']['lon'])
            {
                $hit['_source']['lat'] = $hit['_source']['location']['lat'];
                $hit['_source']['lon'] = $hit['_source']['location']['lon'];
                unset($hit['_source']['location']);
            }

            $resourceType = rtrim(ucfirst($hit['_type']), 's');

            //$model = new $modelPath;
            $modelPath = '\App\\'.$resourceType;
            $model = new $modelPath;

            $model->fill($hit['_source']);
            $model->id = $hit['_source']['id'];


            $extraData = [
                'type' => $resourceType,
                'score' => $hit['_score'],
            ];

            /*
            if($distanceQuery && isset($hit['sort'][0]) && is_numeric($hit['sort'][0]))
            {
                $extraData = ['distance' => $hit['sort'][0]] + $extraData;
            }
            */

            $data[] = transform($model, $resourceType) + $extraData;
        }

        if($collect)
        {
            return collect($data);
        }

        return $data;
    }

    public static function recursive_array_intersect_key(array $array1, array $array2) {
        $array1 = array_intersect_key($array1, $array2);
        foreach ($array1 as $key => &$value) {
            if (is_array($value) && is_array($array2[$key])) {
                $value = recursive_array_intersect_key($value, $array2[$key]);
            }
        }
        return $array1;
    }

    public static function elasticsearchGetMeta($esdata)
    {
        $fields = [
            'took' => '',
            '_shards' => '',
            'hits' => [
                'total' => '',
                'max_score' => ''
            ]
        ];

        $esdata = recursive_array_intersect_key($esdata, $fields);

        return $esdata;
    }

    public static function transform($resource, $resourceType)
    {
        $transformerPath = '\App\Transformers\\'.$resourceType.'Transformer';
        $transformer = new $transformerPath();

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

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}



