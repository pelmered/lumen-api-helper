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

    public function getSignleRelation( $transformer, $resourceId, $relation, $relationId )
    {
        $fractal = new Manager();

        $m = static::RESOURCE_MODEL;

        $resource = $m::find($resourceId);

        if(!$resource)
        {
            return $this->notFoundResponse();
        }

        if (Gate::denies('read_'.$relation, $resource ) ) {
            return $this->permissionDeniedResponse();
        }

        if (isset($_GET['include'])) {
            $fractal->parseIncludes($_GET['include']);
        }

        $fractal->setSerializer(new ApiSerializer());

        $resourceRelation = $resource->$relation()->find($relationId);

        if(!$resourceRelation)
        {
            return $this->notFoundResponse();
        }

        $item = new Item($resourceRelation, $transformer);

        $data = $fractal->createData($item)->toArray();

        return $this->response($data);
    }

    /**
     * Get a listing of the resource.
     *
     * @return Response
     */
    public function getRelationList($transformer, $resourceId, $relation)
    {
        $fractal = new Manager();

        $m = static::RESOURCE_MODEL;

        $resource = $m::find($resourceId);

        if(!$resource)
        {
            return $this->notFoundResponse();
        }

        if (Gate::denies('read_'.$relation, $resource ) ) {
            return $this->permissionDeniedResponse();
        }

        if (isset($_GET['include'])) {
            $fractal->parseIncludes($_GET['include']);
        }

        /*
         * TODO: find a solution for excluding parent resource from includes
        if( isset($fractal->includeParams[$relation]) )
        {
            unset($fractal->includeParams[$relation]);
        }
        */

        $fractal->setSerializer(new ApiSerializer());

        $limit = $this->getQueryLimit();

        $resourceRelation = $resource->$relation()->paginate($limit);

        $collection = new Collection($resourceRelation, $transformer);

        $data = $fractal->createData($collection)->toArray();
/*
        print_r($data);
        die();
*/
        return $this->paginatedResponse($resourceRelation,$data);
    }

    public function storeRelationResource($resourceId, $relation, $m = null )
    {
        if(!$m)
        {
            $m = static::RESOURCE_MODEL;
        }

        $resource = $m::find($resourceId);

        if( !$resource )
        {
            return $this->notFoundResponse();
        }

        $this->validateAction($resource, 'store');

        $resourceData = $this->createResource( $relation, ['post_id' => $resourceId] );

        if( $pos = strrpos($relation, '\\') )
        {
            $relation = substr($relation, $pos + 1);
        }

        return $this->setStatusCode(200)->createdResponse([
            'meta' => [
                'message' => $relation.' created with ID: ' . $resourceData['id']
            ],
            'data' => $resourceData
        ]);
    }

    public function updateRelationResource($resourceId, $relation, $relationId, $m = null )
    {
        $request = app('request');
        $m = static::RESOURCE_MODEL;

        $resource = $m::find($resourceId);

        if(!$resource = $m::find($id))
        {
            return $this->notFoundResponse();
        }

        $this->validateAction($resource, 'store');

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

        $resourceObject->fill($request->all());
        $resourceObject->save();

        return $this->setStatusCode(200)->response([
            'meta' => [
                'message' => 'Updated '.static::RESOURCE_NAME.' with ID: ' . $resourceObject->id
            ],
            'data' => \App\transform($resourceObject, static::RESOURCE_NAME )
            //'data' => $resourceObject->toArray()
        ]);
    }

}
