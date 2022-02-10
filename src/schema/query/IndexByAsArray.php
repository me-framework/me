<?php
namespace me\schema\query;
trait IndexByAsArray {
    public $asArray = false;
    public $indexBy;
    public function asArray($asArray = true) {
        $this->asArray = $asArray;
        return $this;
    }
    public function indexBy($column) {
        $this->indexBy = $column;
        return $this;
    }
}