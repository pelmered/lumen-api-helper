<?php

namespace pelmered\RestTraits\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

use pelmered\RestTraits\Controllers\ControllerResponses;
use pelmered\RestTraits\Controllers\ControllerActions;


abstract class ApiController extends BaseController
{
    use ApiControllerResponsesTrait, ApiControllerActionsTrait;

    //const RESOURCE_MODEL = '';
    //const RESOURCE_NAME = '';

    function __construct()
    {
        //self::MODEL = $this

    }

    protected function getQueryLimit()
    {
        $limit = (int) Input::get('limit') ?: 10;

        /*
        if( $limit > 50 || $limit == 0 )
        {
            $limit = 10;
        }
        */

        return $limit;
    }

    protected function getCurrentPage()
    {
        $page = (int) Input::get('page') ?: 1;

        if( $page == 0 )
        {
            $page = 1;
        }

        return $page;
    }

    public function getResource( $resourceId, $checkPermissions = true, $notFoundResponse = true )
    {
        $m = static::RESOURCE_MODEL;

        if ($checkPermissions && Gate::denies('read', $m ) ) {
            return $this->permissionDeniedResponse();
        }

        $resource = $m::find($resourceId);

        if($notFoundResponse && !$resource) {
            return $this->notFoundResponse();
        }

        return $resource;
    }

    public function getTransformer( )
    {
        $path = '\App\Transformers\\'.static::RESOURCE_NAME.'Transformer';

        return new $path;
    }

    function validate( Request $request, array $rules, array $messages = [], array $customAttributes = [] )
    {
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {

            $errors = $this->formatValidationErrors($validator);

            return $this->setStatusCode(400)
                ->setErrorCode('VALIDATION_ERROR')
                ->setErrorDetails($errors['detail'])
                ->errorResponse($errors['title']);
        }

        return true;
    }

    protected function formatValidationErrors(Validator $validator)
    {
        $errors = $validator->errors()->getMessages();

        $fields = 'Validation failed for: '.implode(', ', array_keys($errors));

        $errorString = '';

        foreach( $errors AS $field => $error )
        {
            $errorString .= ucfirst($field).': '.implode(', ', $error).' ';
        }

        return [
            'title'     => $fields,
            'detail'    => $errorString
        ];
    }






}


