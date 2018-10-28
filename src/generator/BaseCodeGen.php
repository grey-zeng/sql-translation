<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 8:23 AM
 */

namespace SQLTranslation\generator;


use SQLTranslation\core\Token;
use SQLTranslation\Translator;

abstract class BaseCodeGen {

    /** @var Translator */
    public $translator;

    /** @var string 字段包含符 */
    protected $columnWrapperSymbol = '';

    protected $variables = [];

    public function __construct($translator) {
        $this->translator = $translator;
    }

    /**
     * 把ast翻译成目标公式
     * @param Token $token
     * @return string
     * @throws \Exception
     */
    public function generator(Token $token) {
        switch ($token->type) {
            // 根节点直接返回
            case Token::TYPE_ROOT:
                return array_reduce($token->child, function ($prev, $node) {
                    return $prev . $this->generator($node);
                }, '');
                break;
            // 函数需要进行求子元素
            case Token::TYPE_FUNCTION:
                $params = [];
                foreach ($token->child as $param) {
                    $params[] = [
                        'value' => $this->generator($param),
                        'type' => Token::getDataType($param)
                    ];
                }
                return $this->translateFunction(strtolower($token->value), $params);
            // 字符串使用双引号显示包含起来
            case Token::TYPE_STRING:
                return '"' . addslashes($token->value) . '"';
            // 字段类型支持设置前缀转码，同时也需要用符号包含起来
            case Token::TYPE_COLUMN:
                return $this->wrapperColumn($token->value);
            // 仅支持二元运算公式，返回拼接后的公式字符串
            case Token::TYPE_EXPRESSION:
                $params = array_map(function ($token) {
                    return $this->generator($token);
                }, $token->child);
                list($firstParam, $secondParam) = $this->wrapperExpr($token->value, $params);
                return $firstParam . $token->value . $secondParam;
            // 认为是用括号包含起来的代码块，直接包含后返回
            case Token::TYPE_BRACKETS:
                return array_reduce($token->child, function ($res, $token) {
                        return $res . $this->generator($token);
                }, '(') . ')';
            // 常规类型直接返回
            case Token::TYPE_NUMBER:
                return $token->value;
            // 关键字：用于循环、分支判断、声明等
            case Token::TYPE_STATEMENT:
                switch ($token->value) {
                    case 'while':
                        $condition = $this->generator(array_shift($token->child));
                        $block = array_map(function($token) {
                            return $this->generator($token);
                        }, $token->child);
                        return sprintf('while%s{%s}', $condition, join(';', $block));
                    // todo 暂时按照mysql的定义来做
                    case 'declare':
                        if (empty($token->child) || empty($token->child[0]->child)) {
                            throw new \Exception('declare error');
                        }
                        list($variable, $value) = $token->child[0]->child;
                        return sprintf('%s:=%s', $this->generator($variable), $this->generator($value));
                    default:
                        throw new \Exception('statement:' . $token->value . ' is undefined');
                }
            case Token::TYPE_VARIABLE:
                return $this->tranVariable($token->value);
            default :
                throw new \Exception($token->type . ' is undefined');
        }
    }

    /**
     * 特定的平台代码
     * @param string $funcName
     * @param array $params
     * @return mixed
     */
    abstract protected function translateFunction($funcName, $params);

    /**
     * 包裹字段名
     * @param $columnName
     * @return string
     */
    protected function wrapperColumn($columnName) {
        $wrapperColumnName = $this->columnWrapperSymbol . $columnName . $this->columnWrapperSymbol;
        if (empty($this->translator->columnPrefix)) {
            return $wrapperColumnName;
        } else {
            return $this->translator->columnPrefix . '.' . $wrapperColumnName;
        }
    }

    protected function wrapperExpr($expr, $params) {
        return $params;
    }

    /**
     * 对变量进行转义
     * @param $name
     * @return string
     */
    protected function tranVariable($name) {
        if (!isset($this->variables[$name])) {
            $this->variables[$name] = ('@n' . count($this->variables));
        }
        return $this->variables[$name];
    }
}