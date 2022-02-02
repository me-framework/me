<?php
namespace me\components;
class Response extends Component {
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
        echo json_encode($this->data);
        exit;
    }
}