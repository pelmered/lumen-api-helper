<?php

namespace pelmered\APIHelper\Traits;

use pelmered\APIHelper\APIHelper;
use pelmered\APIHelper\ApiSerializer;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use Illuminate\Support\Facades\Gate;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

trait ControllerRelationActions
{
    protected $resource = null;

    public function getSignleRelation($transformer, $resourceId, $relation, $relationId)
    {
        $model = static::RESOURCE_MODEL;

        $resource = $model::find($resourceId);

        if (!$resource) {
            return $this->notFoundResponse();
        }

        if (Gate::denies('read_'.$relation, $resource)) {
            return $this->permissionDeniedResponse();
        }

        $resourceRelation = $resource->$relation()->find($relationId);

        if (!$resourceRelation) {
            return $this->notFoundResponse();
        }

        $item = new Item($resourceRelation, $transformer);

        $data = $this->fractal->createData($item)->toArray();

        return $this->response($data);
    }

    /**
     * Get a listing of the resource.
     *
     * @return Response
     */
    public function getRelationList($transformer, $resourceId, $relation)
    {
        $model = static::RESOURCE_MODEL;

        $resource = $model::find($resourceId);

        if (!$resource) {
            return $this->notFoundResponse();
        }

        if (Gate::denies('read_'.$relation, $resource)) {
            return $this->permissionDeniedResponse();
        }

        /*
         * TODO: find a solution for excluding parent resource from includes
        if( isset($fractal->includeParams[$relation]) )
        {
            unset($fractal->includeParams[$relation]);
        }
        */

        $limit = $this->getQueryLimit();

        $resourceRelation = $resource->$relation()->paginate($limit);

        $collection = new Collection($resourceRelation, $transformer);

        $data = $this->fractal->createData($collection)->toArray();

        return $this->paginatedResponse($resourceRelation, $data);
    }

    public function storeRelationResourceCollection($resourceId, $relation, $key, $model = null)
    {
        if (!$model) {
            $model = static::RESOURCE_MODEL;
        }

        $resource = $model::find($resourceId);

        if (!$resource) {
            return $this->notFoundResponse();
        }

        $this->validateAction($resource, 'store_'.$relation);

        $request = app('request');
        $data    = $request->all();

        if (!isset($data[$key]) || empty($data[$key]))
        {
            return $this->validationErrorResponse('Invalid payload', $resource);
        }

        $relationIds = array_filter($data[$key], function($val) {
            return is_integer($val) || (is_string($val) && ctype_digit($val));
        });

        $resource->$relation()->sync($relationIds);

        $relation = APIHelper::stripNameSpace($relation);

        return $this->setStatusCode(200)->createdResponse(
            [
                'meta' => [
                    'message' => ucfirst($relation).' added to '.APIHelper::stripNameSpace(static::RESOURCE_MODEL).' created with IDs: ' . implode(', ', $relationIds)
                ],
                'data' => $relationIds
            ]
        );

    }

    public function storeRelationResource($resourceId, $relation, $model = null)
    {
        if (!$model) {
            $model = static::RESOURCE_MODEL;
        }

        $resource = $model::find($resourceId);

        if (!$resource) {
            return $this->notFoundResponse();
        }

        $this->validateAction($resource, 'store_'.$relation);

        $resourceData = $this->createResource($relation, ['post_id' => $resourceId]);

        if ($pos = strrpos($relation, '\\')) {
            $relation = substr($relation, $pos + 1);
        }

        return $this->setStatusCode(200)->createdResponse(
            [
            'meta' => [
                'message' => $relation.' created with ID: ' . $resourceData['id']
            ],
            'data' => $resourceData
            ]
        );
    }

    public function updateRelationResource($resourceId, $relation, $relationId, $model = null)
    {
        $request = app('request');
        $model   = static::RESOURCE_MODEL;

        $resource = $model::find($resourceId);

        if (!$resourceObject = $model::find($resourceId)) {
            return $this->notFoundResponse();
        }

        $this->validateAction($resource, 'update_'.$relation);

        /*
        try
        {
            $v = \Validator::make($request->all(), $this->getValidationRules('update'));
            if($v->fails())
            {
                throw new \Exception("ValidationException");
            }
        }catch(\Exception $ex)
        {
            $resourceObject = ['form_validations' => $v->errors(), 'exception' => $ex->getMessage()];
            return $this->validationErrorResponse('Validation error', $resourceObject);
        }
        */

        $relationObject = $resourceObject->$relation()->where('id', $relationId)->get();

        $relationObject->fill($request->all());
        $relationObject->save();

        return $this->setStatusCode(200)->response(
            [
            'meta' => [
                'message' => 'Updated '.static::RESOURCE_NAME.' with ID: ' . $relationObject->id
            ],
            'data' => APIHelper::transform($relationObject, $relation)
            //'data' => $resourceObject->toArray()
            ]
        );
    }
}
