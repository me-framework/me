<?php
namespace me\schema\query;
trait WhereTrait {
    public $where;
    public function where($where) {
        $this->where = $where;
        return $this;
    }
    public function andWhere($where) {
        if ($this->where === null) {
            $this->where = $where;
        }
        elseif (is_array($this->where) && isset($this->where[0]) && strcasecmp($this->where[0], 'and') === 0) {
            $this->where[] = $where;
        }
        else {
            $this->where = ['and', $this->where, $where];
        }
        return $this;
    }
    public function orWhere($where) {
        if ($this->where === null) {
            $this->where = $where;
        }
        else {
            $this->where = ['or', $this->where, $where];
        }
        return $this;
    }
}