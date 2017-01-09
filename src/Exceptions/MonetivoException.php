<?php

namespace Monetivo\Exceptions;

use Exception;

/**
 * Class MonetivoException
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Exceptions
 */
class MonetivoException extends Exception
{

    /** Raw response string
     * @var null
     */
    private $response;
    /** HTTP status code
     * @var null
     */
    private $httpCode;

    public function __construct($message = '', $code = 0, $httpCode = null, $response = null, Exception $previous = null)
    {
        $this->httpCode = $httpCode;
        $this->response = $response;
        parent::__construct($message, $code = 0, $previous);
    }


    /** Returns raw and unparsed API response
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /** Returns HTTP status code of the response
     * null if the exception is not related to the API response
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }
}
