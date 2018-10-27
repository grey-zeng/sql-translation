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
            // todo 关键字：用于循环、分支判断等
            case Token::TYPE_STATEMENT:
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
}