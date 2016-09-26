<?php

namespace pelmered\APIHelper\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

use League\Fractal\Manager;

use pelmered\APIHelper\Traits\ControllerResponses;
use pelmered\APIHelper\Traits\ControllerActions;
use pelmered\APIHelper\Traits\ControllerRelationActions;

use pelmered\APIHelper\APIHelper;
use pelmered\APIHelper\ApiSerializer;

abstract class ApiController extends BaseController
{
    use ControllerResponses, ControllerActions, ControllerRelationActions;

    //const RESOURCE_MODEL = '';
    //const RESOURCE_NAME = '';

    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Manager();
        $this->fractal->setSerializer(new ApiSerializer());

        $include = filter_input(INPUT_GET, 'include', FILTER_SANITIZE_STRING);

        if (isset($include)) {
            $this->fractal->parseIncludes($include);
        }

        //parent::__construct();
    }

    public static function getReservedKeywords(  )
    {
        return [
            'limit',  'page', 'per_page'
        ];
    }

    public function columnsFilter( $filters = [] )
    {
        $filterFields =array_diff_key($_GET, array_flip(static::getReservedKeywords()));

        foreach($filterFields as $fieldKey => $fieldValue)
        {
            $filters[] = [
                'field'     => $fieldKey,
                'operator'  => '=',
                'value'     => filter_var($fieldValue, FILTER_SANITIZE_STRING)
            ];
        }

        return $filters;
    }

    protected function getQueryLimit()
    {
        $limit = Input::get('limit') ?: 10;

        if ($limit === 'all')
        {
            return null;
        }

        $limit = (int) $limit;

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
