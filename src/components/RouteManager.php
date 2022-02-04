<?php
namespace me\components;
use Me;
use Exception;
use me\core\Component;
class RouteManager extends Component {
    /**
     * @var \me\components\RouteRule[] Route Rules
     */
    public $rules      = [];
    /**
     * @var array Rule Config such as class, pattern, verb, route
     */
    public $ruleConfig = ['class' => RouteRule::class];
    /**
     * 
     */
    public function init() {
        parent::init();
        $this->rules = $this->buildRules($this->rules);
    }
    /**
     * Build Rules
     * @param array $ruleDeclarations Rule Declarations
     * @return \me\components\RouteRule[] Built Rules
     */
    private function buildRules($ruleDeclarations) {
        $builtRules = [];
        foreach ($ruleDeclarations as $key => $rule) {
            $builtRules[] = $this->buildRule($key, $rule);
        }
        return $builtRules;
    }
    /**
     * Build Rule
     * @param string $pattern Pattern
     * @param string|array|\me\components\RouteRule $rule Rule
     */
    private function buildRule($pattern, $rule) {
        if (is_string($rule)) {
            $verbs   = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';
            $rule    = ['route' => $rule];
            $matches = [];
            if (preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $pattern, $matches)) {
                $rule['verb'] = explode(',', $matches[1]);
                $pattern          = $matches[4];
            }
            $rule['pattern'] = $pattern;
        }
        if (is_array($rule)) {
            $rule = Me::createObject(array_merge($this->ruleConfig, $rule));
        }
        if (!($rule instanceof RouteRule)) {
            throw new Exception('Route rule class must implement RouteRule.');
        }
        return $rule;
    }
    /**
     * @param \me\components\Request $request Request
     * @return array [$route, $params]
     */
    public function parseRequest($request) {
        foreach ($this->rules as $rule) {
            $result = $rule->parseRequest($request);
            if ($result !== false) {
                return $result;
            }
        }
        $route = $request->getPathInfo();
        return [$route, []];
    }
}