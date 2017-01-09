<?php

namespace Monetivo\Api;

use ArrayAccess;
use JsonSerializable;
use Monetivo\Exceptions\MonetivoException;

/**
 * Class ApiResponse
 * @author Jakub Jasiulewicz <jjasiulewicz@monetivo.com>
 * @package Monetivo\Api
 */
class ApiResponse implements JsonSerializable, ArrayAccess
{
    /** Parsed response
     * @var
     */
    private $response = array();

    /** HTTP status code
     * @var
     */
    private $httpCode;

    public function __construct($headers, $body, $httpCode)
    {
        $this->parseResponse($body, $httpCode);
        $this->httpCode = $httpCode;
    }

    /** Parses the response to an array
     * @param $response
     * @param $httpCode
     * @throws MonetivoException
     */
    private function parseResponse($response, $httpCode)
    {
        if(!is_string($response)) {
            return;
        }

        $parsed = json_decode($response, 1);
        if (json_last_error() !== 0) {
            throw new MonetivoException('API response is malformed: ' . json_last_error_msg(), 0, $httpCode, $response);
        }

        $this->response = $parsed;
    }

    /** Checks if the response is successful (2xx)
     * @return bool
     */
    public function isOK()
    {
        return 0 === strpos((string)$this->httpCode, '2');
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /** Serialize response to JSON string
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /** Casts response to an array
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->response, ['httpCode' => $this->getHttpCode()]);
    }

    /** Returns HTTP status code
     * @return mixed
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset)
    {
        return isset($this->response[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset)
    {
        return isset($this->response[$offset]) ? $this->response[$offset] : null;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->response[] = $value;
        } else {
            $this->response[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset)
    {
        unset($this->response[$offset]);
    }

    /** __get magic method implementation
     * @param $offset
     * @return mixed|null
     */
    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    /** __set magic method implementation
     * @param $offset
     * @param $value
     */
    public function __set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /** __isset magic method implementation
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }


}
