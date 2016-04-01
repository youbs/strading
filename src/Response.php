<?php
namespace Gajus\Strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Response
{
    private
        /**
         * @var SimpleXMLElement
         */
        $xml,
        /**
         * @var string
         */
        $type,
        /**
         * @var array
         */
        $transaction,
        /**
         * @var array
         */
        $error,
        /**
         * @var string
         */
        $redirect_url;

    /**
     * @param SimpleXMLElement $xml Response body.
     * @param Request $request Request that produced the response.
     */
    public function __construct(\SimpleXMLElement $xml, Request $request)
    {
        $this->xml = $xml;

        // PHP 5.3 does not allow array access to the method response.
        $response = $this->xml->xpath('/responseblock/response');
        $response = $response[0];

        $this->type = $response->attributes();
        $this->type = (string)$this->type['type'];

        $request_block = $this->xml->xpath('/responseblock');

        $record = $this->xml->xpath('/responseblock/response/record');

        $this->transaction['request_reference'] = (string)$request_block[0]->requestreference;
        $this->transaction['transaction_type'] = empty($response->billing->payment['type']) ? null : (string)$response->billing->payment['type'];
        $this->transaction['transaction_reference'] = empty($response->transactionreference) ? null : (string)$response->transactionreference;
        $this->transaction['timestamp'] = empty($response->timestamp) ? null : (string)$response->timestamp;
        $this->transaction['parent_transaction_reference'] = empty($response->operation->parenttransactionreference) ? null : (string)$response->operation->parenttransactionreference;
        $this->transaction['authcode'] = empty($response->authcode) ? null : (string)$response->authcode;
        $this->transaction['amount'] = empty($response->billing->amount) ? null : (string)$response->billing->amount / 100;
        $this->transaction['paypal_token'] = empty($response->paypal->token) ? null : (string)$response->paypal->token;
        $this->transaction['record'] = empty($record) ? null : $record;

        if (isset($response->error)) {
            $this->error = array(
                'code' => (string)$response->error->code,
                'message' => empty($response->error->message) ? null : (string)$response->error->message,
                'data' => empty($response->error->data) ? null : (string)$response->error->data
            );
        }

        if (!empty($response->paypal->redirecturl)) {
            $this->redirect_url = (string)$response->paypal->redirecturl;
        }
    }

    /**
     * Transaction abstracts access to the most generic information about the response:
     *
     * - request_reference
     * - transaction_type
     * - transaction_reference
     * - timestamp
     * - parent_transaction_reference
     * - authcode
     * - amount
     * - paypal_token
     *
     * Presence of this data will depend on the type of the response you receive, e.g.
     * only PayPal order request will include "paypal_token" parameter.
     *
     * @return array
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Was the transaction successful?
     *
     * @return bool
     */
    public function isOk()
    {
        if (!isset($this->error)) {
            return true;
        }

        if (isset($this->error['code'])) {
            return $this->error['code'] == 0;
        }

        return false;
    }


    /**
     * This information is available when response type is "ERROR".
     *
     * @return null|Gajus\Strading\Error
     */
    public function getError()
    {
        if (!isset($this->error)) {
            return;
        }

        return new Error($this->error['code'], $this->error['message'], $this->error['data']);
    }

    /**
     * This information is available in response to the "paypal/order" request.
     *
     * @return null|string URL to redirect the client to.
     */
    public function getRedirectUrl()
    {
        return $this->redirect_url;
    }

    /**
     * @return string Response type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string Raw XML response.
     */
    public function getXML()
    {
        return $this->xml->asXML();
    }
}