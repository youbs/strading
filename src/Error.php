<?php
namespace Gajus\Strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Error {
    private
        $code,
        $message,
        $data;
    
    /**
     * @param string $code
     * @param string $message
     * @param string $data
     */
    public function __construct ($code, $message, $data) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
    
    /**
     * @return string
     */
    public function getCode () {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage () {
        return $this->message;
    }

    /**
     * This tag contains one or more child elements. If the error code is "30000" (Field Error)
     * then this field will contain the field (or fields) which caused the error.
     * 
     * @todo https://github.com/gajus/strading/issues/1
     * @return string
     */
    public function getData () {
        return $this->data;
    }
}