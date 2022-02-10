<?php
namespace me\schema\conditions;
class OrCondition extends ConjunctionCondition {
    public function getOperator() {
        return 'OR';
    }
}