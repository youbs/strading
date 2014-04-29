<?php
namespace Gajus\Strading;

/**
 * @link https://github.com/gajus/strading for the canonical source repository
 * @license https://github.com/gajus/strading/blob/master/LICENSE BSD 3-Clause
 */
class Response {
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
    public function __construct (\SimpleXMLElement $xml, Request $request) {
        $this->xml = $xml;

        $response = $this->xml->xpath('/responseblock/response')[0];

        $this->type = (string) $response->attributes()['type'];

        // Relevant to card payment only.
        #if (count($this->xml->xpath('/responseblock/response')) !== 1) {
        #    throw new Exception\UnexpectedValueException('Multipart response.');
        #}        

        $this->transaction['request_reference'] = (string) $this->xml->xpath('/responseblock')[0]->requestreference;
        $this->transaction['transaction_type'] =  empty($response->billing->payment['type']) ? null : (string) $response->billing->payment['type'];
        $this->transaction['transaction_reference'] =  empty($response->transactionreference) ? null : (string) $response->transactionreference;
        $this->transaction['timestamp'] = empty($response->timestamp) ? null : (string) $response->timestamp;
        $this->transaction['parent_transaction_reference'] =  empty($response->operation->parenttransactionreference) ? null : (string) $response->operation->parenttransactionreference;
        $this->transaction['authcode'] =  empty($response->authcode) ? null : (string) $response->authcode;
        $this->transaction['amount'] = empty($response->billing->amount) ? null : (string) $response->billing->amount/100;
        $this->transaction['paypal_token'] =  empty($response->paypal->token) ? null : (string) $response->paypal->token;

        if ($this->getType() === 'ERROR') {
            $this->error = [
                'code' => (string) $response->error->code,
                'message' => empty($response->error->message) ? null : (string) $response->error->message,
                'data' => empty($response->error->data) ? null : (string) $response->error->data
            ];
        }

        if (!empty($response->paypal->redirecturl)) {
            $this->redirect_url = (string) $response->paypal->redirecturl;
        }

        /* else if (empty($transaction['authcode'])) {
            throw new \Arqiva\MAMA\Exception\LogicException('Transaction declined. Unknown error.');
        }*/
    }

    /**
     * 
     */
    public function getTransaction () {
        return $this->transaction;
    }

    /**
     * 
     * 
     * @return null|Gajus\Strading\Error
     */
    public function getError () {
        if ($this->getType() !== 'ERROR') {
            return;
        }

        return new Error($this->error['code'], $this->error['message'], $this->error['data']);
    }

    /**
     * This information is available after "paypal/order" request.
     * Client will be returned to the client with information required to finalise the transaction.
     * 
     * @return string URL to redirect to the client to.
     */
    public function getRedirectUrl () {
        return $this->redirect_url;
    }

    /**
     * 
     * @return string Response type can be auth, error or redirect (in case of PayPal).
     */
    public function getType () {
        return $this->type;
    }

    /**
     * @return SimpleXMLElement Raw XML response.
     */
    public function getXML () {
        return $this->xml;
    }
}