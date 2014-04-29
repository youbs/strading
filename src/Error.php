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
    
    public function getCode () {
        return $this->code;
    }

    public function getMessage () {
        return $this->message;
    }

    public function getData () {
        return $this->data;
    }
}