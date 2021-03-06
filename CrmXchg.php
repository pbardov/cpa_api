<?php

class CrmXchg
{

    private $m_client = NULL;
    private $m_secret = NULL;
    private $m_url    = NULL;
    private $m_ch     = NULL;

    public function __construct($client, $secret, $url)
    {
        $this->m_client = $client;
        $this->m_secret = $secret;
        $this->m_url    = $url;
    }

    /**
     * Добавить заказ
     *
     * @param CrmXchg_Order $order
     * @return mixed
     */
    public function addOrder($order)
    {
        $params = array($order);
        $answer = $this->_request('addOrder', $params);
        return $answer->result;
    }

    public function getOrderStatus(array $order_nmbs)
    {
        $params = array($order_nmbs, 1);
        $answer = $this->_request('getOrderStatus', $params);
        return $answer->result;
    }

    public function getOrderStatusR($rev_nmb = 0)
    {
        $params              = array($rev_nmb, 1);
        $answer              = $this->_request('getOrderStatusR', $params);
        if (!isset($answer->result->rev) || $answer->result->rev < $rev_nmb)
            $answer->result->rev = $rev_nmb;
        return $answer->result;
    }

    public function getOrders($date)
    {
        $params = array($date);
        $answer = $this->_request('getOrders', $params);
        return $answer->result;
    }

    private function _request($method, array $params, $id = NULL, $throw_exception = TRUE)
    {
        $now      = time();
        $id       = isset($id) ? $id : ($now * 1000 + rand(0, 999));
        $req      = array(
            'method' => $method,
            'params' => $params,
            'id'     => $id,
        );
        $req_str  = json_encode($req);
        $chk_str  = $req_str . $this->m_client . $this->m_secret;
        $sign     = md5($chk_str);
        $body_obj = array(
            'sender'  => $this->m_client,
            'sign'    => $sign,
            'request' => $req_str,
        );
        $body_str = json_encode($body_obj);

        $ch = curl_init($this->m_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body_str);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body_str))
        );

        $res     = curl_exec($ch);
        $res_obj = json_decode($res);
        $answer  = json_decode($res_obj->answer);

        if ($throw_exception && !empty($answer->error))
            throw new CrmXchg_Exception($answer->error);

        return $answer;
    }

}

class CrmXchg_Order
{

    public $confirmed    = 0;    // опционально, booelan, позволяет указать что заказ подтвержден
    public $ip           = '';   // опционально, string, ip адрес заказчика
    public $order_id     = '';   // обязательно, string, номер заказа
    public $good_id      = '';   // обязательно, string, идентификатор продукта
    public $kolvo        = 1;    // опционально, uinteger, количество продукта
    public $fio          = '';   // обязательно, string, Фамилия Имя Отчество заказчика
    public $address      = '';   // обязательно, string, адрес заказчика
    public $phone        = '';   // обязательно, string, номер телефона заказчика
    public $country_kod  = 'RU'; // опционально, string, код страны заказа
    public $affiliate_id = '';   // опционально, string, идентификатор аффилиата
    public $comment      = '';   // опционально, string, комментарий к заказу
    public $delcost      = '';   //опционально, только для подтвержд, float, стоимость доставки
    public $deliv_date   = '';   //опционально, только для подтвержд, string Y-m-d дата доставки
    public $deliv_stime  = '';   //опционально, только для подтвержд, string H:i время доставки от
    public $deliv_etime  = '';   //опционально, только для подтвержд, string H:i время доставки до
    public $upsell       = null; //опционально, array(array('good' => 'good_id1', 'price' => 12.34, 'quantity' => 1),)

}

class CrmXchg_Exception extends Exception
{

}
