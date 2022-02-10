<?php
namespace me\schema\conditions;
class AndCondition extends ConjunctionCondition {
   public function getOperator() {
        return 'AND';
    }
}