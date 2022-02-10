<?php
namespace me\schema\conditions;
class HashCondition extends Condition {
    /**
     * @var array|null the condition specification.
     */
    private $hash;
    /**
     * HashCondition constructor.
     *
     * @param array|null $hash
     */
    public function __construct($hash) {
        parent::__construct();
        $this->hash = $hash;
    }
    /**
     * @return array|null
     */
    public function getHash() {
        return $this->hash;
    }
    /**
     * {@inheritdoc}
     */
    public static function fromArrayDefinition($operator, $operands) {
        return new static($operands);
    }
}