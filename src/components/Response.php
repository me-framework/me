<?php
namespace me\components;
use me\core\Component;
use me\helpers\JsonHelper;
class response extends Component {
    public $code    = 200;
    public $data    = [];
    public $headers = [
        'content-type' => 'application/json',
    ];
    public function send() {
        http_response_code($this->code);
        foreach ($this->headers as $key => $value) {
            header("$key:$value");
        }
        echo JsonHelper::encode($this->data);
        exit;
    }
}