<?php

namespace pelmered\APIHelper\Traits;

use pelmered\APIHelper\ApiSerializer;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use Illuminate\Support\Facades\Gate;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

trait ControllerActions
{
    protected $resource = null;

    /*
    public function index()
    {
        $m = self::MODEL;
        return $this->listResponse($m::all());
    }
    */

    public function getCreatedResourceObject()
    {
        return $this->resource;
    }


    /**
     * Get a listing of the resource.
     *
     * @return Response
     */
    public function getList($transformer)
    {
        $fractal = new Manager();

        $model = static::RESOURCE_MODEL;

        if (Gate::denies('read', $model)) {
            return $this->permissionDeniedResponse();
        }


        $include = filter_input(INPUT_GET, 'include', FILTER_SANITIZE_STRING);

        if (isset($include)) {
            $fractal->parseIncludes($include);
        }

        $fractal->setSerializer(new ApiSerializer());

        $limit = $this->getQueryLimit();

        $Resources = $model::orderBy('created_at', 'desc')->paginate($limit);

        $collection = new Collection($Resources, $transformer);

        $data = $fractal->createData($collection)->toArray();

        return $this->paginatedResponse($Resources, $data);
    }




    /**
     * Get the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function getSingle($transformer, $resourceId)
    {
        $fractal = new Manager();

        $model = static::RESOURCE_MODEL;

        if (Gate::denies('read', $model)) {
            return $this->permissionDeniedResponse();
        }

        $resource = $model::find($resourceId);

        if (!$resource) {
            return $this->notFoundResponse();
        }

        $fractal->setSerializer(new ApiSerializer());

        $include = filter_input(INPUT_GET, 'include', FILTER_SANITIZE_STRING);

        if (isset($include)) {
            $fractal->parseIncludes($include);
        }

        $item = new Item($resource, $transformer);

        $data = $fractal->createData($item)->toArray();

        return $this->response($data);
    }

    public function storeResource($model = null)
    {
        if (!$model) {
            $model = static::RESOURCE_MODEL;
        }

        $this->validateAction($model, 'store');

        $resourceData = $this->createResource($model);

        return $this->setStatusCode(200)->createdResponse(
            [
            'meta' => [
                'message' => static::RESOURCE_NAME.' created with ID: ' . $resourceData['id']
            ],
            'data' => \App\transform($this->getCreatedResourceObject(), static::RESOURCE_NAME)
            ]
        );
    }

    protected function createResource($model, $merge = [])
    {
        $request = app('request');
        $data    = $request->all();

        // Author should always by current authenticated user
        $author = Auth::user();
        if ($author) {
            $data['user_id'] = $author->id;
        }

        $data = $data + $merge;

        $resourceObject = $model::create($data);

        $this->resource = $resourceObject;

        $resourceData = $resourceObject->toArray();

        if (isset($data['media']) && method_exists($this, 'processMedia')) {
            $media = $this->processMedia(isset($merge['post_id']) ? $merge['post_id'] : $resourceObject->id, $model);

            if ($media) {
                $resourceData['media'] = $media;
            }
        }

        return $resourceData;
    }

    private function processMedia($resourceId)
    {
        $request = app('request');
        $data    = $request->all();

        if (isset($data['media'])) {
            // Author should always by current authenticated user
            $author                         = Auth::user();
            $data['media']['resource_id']   = $resourceId;
            $data['media']['resource_type'] = static::RESOURCE_NAME;
            $data['media']['user_id']       = $author->id;

            $resourceData = $this->saveMedia($data['media']);

            return $resourceData;
        }

        return false;
    }

    private function saveMedia($mediaData)
    {
        $media = new \pelmered\APIHelper\Models\Media($mediaData);

        $media->save();

        $media->setBase64($mediaData['file'])->generateImageSizes();

        $mediaData              = $media->toArray();
        $mediaData['file_urls'] = $media->getFileUrl();

        return $mediaData;
    }

    public function updateResource($resourceId)
    {
        $request = app('request');
        $model   = static::RESOURCE_MODEL;

        if (!$resourceObject = $model::find($resourceId)) {
            return $this->notFoundResponse();
        }

        if (Gate::denies('update', $resourceObject)) {
            return $this->permissionDeniedResponse();
        }

        try {
            $validator = \Validator::make($request->all(), $this->getValidationRules('update'));
            if ($validator->fails()) {
                throw new \Exception("ValidationException");
            }
        } catch (\Exception $ex) {
            $resourceObject = ['form_validations' => $validator->errors(), 'exception' => $ex->getMessage()];
            return $this->validationErrorResponse('Validation error', $resourceObject);
        }

        $resourceObject->fill($request->all());
        $resourceObject->save();

        return $this->setStatusCode(200)->response(
            [
            'meta' => [
                'message' => 'Updated '.static::RESOURCE_NAME.' with ID: ' . $resourceObject->id
            ],
            'data' => \App\transform($resourceObject, static::RESOURCE_NAME)
            //'data' => $resourceObject->toArray()
            ]
        );
    }

    public function destroyResource($resourceId)
    {
        $model = static::RESOURCE_MODEL;
        if (!$resourceObject = $model::find($resourceId)) {
            return $this->notFoundResponse();
        }

        if (Gate::denies('delete', $resourceObject)) {
            return $this->permissionDeniedResponse();
        }

        $resourceObject->delete();

        return $this->setStatusCode(200)->response(
            [
            'meta' => [
                'message' => 'Deleted '.static::RESOURCE_NAME.' with ID: ' . $resourceObject->id
            ],
            'data' => $resourceObject->toArray()
            ]
        );
    }

    public function validateAction($model, $action)
    {
        $request = app('request');

        if (is_string($model)) {
            $model = new $model();
        }

        if (Gate::denies($action, $model)) {
            return $this->permissionDeniedResponse();
        }

        try {
            $validator = \Validator::make($request->all(), $this->getValidationRules($action));
            if ($validator->fails()) {
                throw new \Exception("ValidationException");
            }
        } catch (\Exception $ex) {
            $resourceObject = ['form_validations' => $validator->errors(), 'exception' => $ex->getMessage()];
            return $this->validationErrorResponse('Validation error', $resourceObject);
        }
    }

    function getValidationRules($type)
    {
        if (isset($this->validationRules[$type])) {
            return $this->validationRules[$type];
        }

        return [];
    }
}
