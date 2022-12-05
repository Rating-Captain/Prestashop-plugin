<?php
class RatingCaptain_client{
    protected $apiKey, $order=array(), $products = array();
    private $store_url = 'https://ratingcaptain.com/api/emails', $delete_url="https://ratingcaptain.com/api/website_rate/destroy";
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }
    public function addProduct($id, $name, $price = null, $imageUrl = null, $desc = null, $product_link = null){
        array_push($this->products, [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'imageUrl' => $imageUrl,
            'desc' => $desc,
            'url' => $product_link
        ]);
    }
    private function curl($data, $method = 'post', $url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($method == 'post'){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
    public function send($data){
        $data['hash'] = $this->apiKey;
        foreach (['external_id', 'email'] as $field){
            if(!$this->checkField($data, $field)) return ['errors' => $field.' is required'];
        }
        $this->order['external_id'] = $data['external_id'];
        $this->order['email'] = $data['email'];
        if(array_key_exists('send_date', $data)) $this->order['send_date'] = $data['send_date'];
        if(array_key_exists('send_after', $data)) $this->order['send_after'] = $data['send_after'];
        if(array_key_exists('name', $data)) $this->order['name'] = $data['name'];
        if(array_key_exists('surname', $data)) $this->order['surname'] = $data['surname'];
        $this->order['hash'] = $this->apiKey;
        $arr = $this->order;
        if(count($this->products) > 0) $arr['products'] = $this->products;

        return $this->curl(json_encode($arr), 'post', $this->store_url);
    }
    public function deleteOrder($id){
        return $this->curl(json_encode(['id' => $id, 'hash' => $this->apiKey]), 'post', $this->delete_url);
    }
    private function checkField($arr, $field){
        return array_key_exists($field, $arr);
    }
}