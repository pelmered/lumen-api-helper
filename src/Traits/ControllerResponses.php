<?php

namespace pelmered\APIHelper\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ControllerResponses
{
    protected $statusCode = 200;
    protected $errorCode = '';
    protected $errorDetails = '';


    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getErrorCode()
    {
        if( empty( $this->errorCode ) )
        {
            \Log::error( 'Unspecified error(should be specified): ' . "\n" . print_r( debug_backtrace(), true ) );
            return 'UNSPECIFIED_ERROR';
        }
        return $this->errorCode;
    }
    /**
     * @param mixed $errorsCode
     */
    public function setErrorCode($errorsCode)
    {
        $this->errorCode = $errorsCode;
        return $this;
    }


    function getErrorDetails()
    {
        return $this->errorDetails;
    }

    function setErrorDetails($errorDetails)
    {
        $this->errorDetails = $errorDetails;
        return $this;
    }


    function unauthenticatedResponse(
        $message = 'Unauthenticated',
        $errorDetails = 'You need to be authenticated to use this resource. Login using v1/auth/login'
    )
    {
        return $this->setStatusCode(401)
            ->setErrorCode('UNAUTHORIZED_ERROR')
            ->setErrorDetails($errorDetails)
            ->errorResponse($message);
    }
    function permissionDeniedResponse(
        $message = 'Unauthorized',
        $errorDetails = 'Current user does not have permission to access or modify this resource'
    )
    {
        return $this->setStatusCode(401)
            ->setErrorCode('PERMISSION_DENIED_ERROR')
            ->setErrorDetails($errorDetails)
            ->errorResponse($message);
    }

    protected function validationErrorResponse(
        $message = 'Validation error',
        //$errorDetails = 'Current user does not have permission to access or modify this resource',
        $data = array()
    )
    {
        return $this->setStatusCode(401)
            ->setErrorCode('VALIDATION_ERROR')
            ->setErrorDetails($data)
            ->errorResponse($message);

        return response()->json($response, $response['code']);
    }
    function notFoundResponse(
        $message = '',
        $errorDetails = 'The specified resource could not be found.'
    )
    {
        if( $message == '' )
        {
            $message = static::RESOURCE_NAME . ' not found';
        }

        return $this->setStatusCode(404)
            ->setErrorCode('NOT_FOUND_ERROR')
            ->setErrorDetails($errorDetails)
            ->errorResponse( $message );
    }
    function internalErrorResponse(
        $message = 'Internal Error',
        $errorDetails = 'We are sorry, an internal error was encountered on our servers while processing this request. It might be a temporary hiccup so please try again later. If the error persists please contact us with details about your request.'
    )
    {
        return $this->setStatusCode(500)
            ->setErrorCode('INTERNAL_ERROR')
            ->setErrorDetails($errorDetails)
            ->errorResponse( $message );
    }
    function debugErrorResponse($exception)
    {
        $data = [
            'status'    => 'error',
            'errors'     => [
                'code'          => 'INTERNAL_ERROR',
                'status'        => $this->getStatusCode(),
                'error_message' => $exception->getMessage(),
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine(),
                'exception'     => get_class($exception),
                'trace'         => $exception->getTrace()
            ]
        ];

        $headers['Access-Control-Allow-Origin'] = 'http://foodie.dev';

        return response()->json( $data, $this->getStatusCode(), $headers, JSON_PRETTY_PRINT );
    }
    function notImplementedResponse($message = 'Planned feature, but not implemented yet. ' )
    {
        return $this->setStatusCode(501)->setErrorCode('NOT_IMPLEMENTED_ERROR')->errorResponse( $message );
    }

    function createdResponse($data = ['message' => ''] )
    {
        return $this->setStatusCode(201)->response( $data );
    }

    function errorResponse($message )
    {
        return $this->response([
            'status'    => 'error',
            'errors'     => [
                'code'      => $this->getErrorCode(),
                'status'    => $this->getStatusCode(),
                'title'     => $message,
                'details'   => $this->getErrorDetails()
            ]
        ]);
    }

    function pngResponse($data )
    {
        $headers = [
            /*
        'Pragma' => 'public',
        'Expires' => '0',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Cache-Control' => 'private',
        'Content-Disposition' => 'attachment filename="$filename.csv";',
        'Content-Transfer-Encoding' => 'binary',
        */
            'Content-type' => 'image/png'
        ];

        return response()->download( $data, 'banner.png', $headers );
    }

    function response($data, $headers = [] )
    {
        if( !isset($data['status'] ) )
        {
            $data = ['status' => 'ok'] + $data;
        }

        $headers['Access-Control-Allow-Origin'] = 'http://foodie.dev';

        return response()->json( $data, $this->getStatusCode(), $headers );
    }

    function paginatedResponse(LengthAwarePaginator $paginator, $data, $headers = [] )
    {
        $currentPage = $this->getCurrentPage();
        $limit = $this->getQueryLimit();

        if( empty($data) || $currentPage > ceil( $paginator->total() / $paginator->perPage() ) || $currentPage < 0 )
        {
            $this->setStatusCode(404);
        }

        $limitStr = '';

        if( $limit )
        {
            $limitStr = '&limit='.$limit;
        }

        $data = array_merge( $data, [
            'pagination' => [
                'total_count'   => (int) $paginator->total(),
                'total_pages'   => (int) ceil( $paginator->total() / $paginator->perPage() ),
                'current_page'  => (int) $currentPage,
                'limit'         => (int) $paginator->perPage(),

                'prev_link'     => $paginator->previousPageUrl().$limitStr,
                'next_link'     => $paginator->nextPageUrl().$limitStr
            ]
        ]);

        return $this->response( $data, $headers );
    }


}
