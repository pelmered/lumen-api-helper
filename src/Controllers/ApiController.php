<?php

namespace pelmered\APIHelper\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

use pelmered\APIHelper\Traits\ControllerResponses;
use pelmered\APIHelper\Traits\ControllerActions;

abstract class ApiController extends BaseController
{
    use ControllerResponses, ControllerActions;

    //const RESOURCE_MODEL = '';
    //const RESOURCE_NAME = '';

    public function __construct()
    {
        //parent::__construct();
    }

    protected function getQueryLimit()
    {
        $limit = (int) Input::get('limit') ?: 10;

        if ($limit > 100 || $limit == 0) {
            $limit = 10;
        }

        return $limit;
    }

    protected function getCurrentPage()
    {
        $page = (int) Input::get('page') ?: 1;

        if ($page == 0) {
            $page = 1;
        }

        return $page;
    }

    public function getResource($resourceId, $checkPermissions = true, $notFoundResponse = true)
    {
        $model = static::RESOURCE_MODEL;

        if ($checkPermissions && Gate::denies('read', $model)) {
            return $this->permissionDeniedResponse();
        }

        $resource = $model::find($resourceId);

        if ($notFoundResponse && !$resource) {
            return $this->notFoundResponse();
        }

        return $resource;
    }

    public function getTransformer()
    {
        $path = '\App\Transformers\\'.static::RESOURCE_NAME.'Transformer';

        return new $path;
    }

    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
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

        foreach ($errors as $field => $error) {
            $errorString .= ucfirst($field).': '.implode(', ', $error).' ';
        }

        return [
            'title'     => $fields,
            'detail'    => $errorString
        ];
    }
}
