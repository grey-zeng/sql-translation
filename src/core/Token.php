<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:42 PM
 */

namespace SQLTranslation\core;


use Exception;
use SQLTranslation\Translator;

class Token {

    const TYPE_ROOT = 'root';
    const TYPE_BRACKETS = 'brackets';
    const TYPE_BRACKET_LEFT = '(';
    const TYPE_BRACKET_RIGHT = ')';
    const TYPE_BRACE_LEFT = '{';
    const TYPE_BRACE_RIGHT = '}';
    const TYPE_VARIABLE = '@';
    const TYPE_COLUMN = 'column';
    const TYPE_NUMBER = 'number';
    const TYPE_STRING = 'string';
    const TYPE_OPERATOR = 'operator';
    const TYPE_EXPRESSION = 'expr';
    const TYPE_FUNCTION = 'function';
    const TYPE_STATEMENT = 'statement';

    const ERROR_COLUMN = 1;
    const ERROR_FUNCTION = 2;
    const ERROR_TYPE = 3;

    public $type;
    public $value;
    /** @var Token[] */
    public $child = [];
    public $parent;
    public $translator;

    /**
     * Token constructor.
     * @param array $token
     * @param Translator $translator
     * @throws Exception
     */
    public function __construct(array $token, $translator) {
        $this->type = $token['type'];
        $this->value = isset($token['value']) ? $token['value'] : '';
        if ($this->type == self::TYPE_FUNCTION && !array_key_exists(strtolower($this->value), Meta::$supportFunc)) {
            throw new Exception('function not exits:' . $this->value, self::ERROR_FUNCTION);
        }
        if ($this->type == self::TYPE_COLUMN) {
            if (!array_key_exists($this->value, $translator->columns)) {
                throw new Exception('column not exits:' . $this->value, self::ERROR_FUNCTION);
            }
            $this->value = $translator->columns[$this->value]['column'];
        }
        $this->translator = $translator;
    }

    public function hashChild() {
        return !empty($this->child);
    }

    public function getLastChild() {
        return end($this->child);
    }

    public function addChild(Token $child) {
        $this->child[] = $child;
    }

    public function setParent(Token $node) {
        $this->parent = $node;
    }

    /**
     * 检测当前节点对应着子节点是否已经完成
     */
    public function isComplete() {
        if ($this->type == self::TYPE_EXPRESSION) {
            if (count($this->child) >= 2) {
                $param = 0;
                foreach ($this->child as $child) {
                    if ($child->type != self::TYPE_BRACKET_LEFT && $child->type != self::TYPE_BRACKET_RIGHT) {
                        $param++;
                    }
                }
                return $param >= 2;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 函数token需要检查参数类型 以及区分不同参数
     * @throws Exception
     */
    public function checkParam() {
        $paramTypeList = [];
        foreach ($this->child as $index => $param) {
            // 检验格式是否符合要求
            if (!$param instanceof Token) {
                $paramTypeList[] = $param['data_type'];
            } else {
                $paramTypeList[] = self::getDataType($param);
            }
        }
        // 按照预定规则进行检查
        if (! Meta::checkTypeCorrect(strtolower($this->value), $paramTypeList) ) {
            throw new Exception("params dataType error", self::ERROR_TYPE);
        }
    }

    /**
     * 获取参数的类型
     * @param Token $token
     * @param string $prevType
     * @return string
     * @throws Exception
     */
    public static function getDataType(token $token, $prevType = '') {
        switch ($token->type) {
            case self::TYPE_COLUMN:
                foreach ($token->translator->columns as $column) {
                    if ($column['column'] == $token->value) {
                        $dataType = $column['type'];
                    }
                }
                if (!isset($dataType)) {
                    foreach ($token->translator->columns as $column) {
                        if ($column['alias'] == $token->value) {
                            $dataType = $column['type'];
                        }
                    }
                }
                if (!isset($dataType)) {
                    throw new Exception('无法找到对应的字段:' . $token->value);
                }
                break;
            case self::TYPE_NUMBER:
                $dataType = Meta::DATA_TYPE_NUMBER;
                break;
            case self::TYPE_FUNCTION:
                $dataType = Meta::$supportFunc[$token->value]['out'];
                break;
            case self::TYPE_EXPRESSION:
            case self::TYPE_BRACKETS:
                $dataType = '';
                foreach ($token->child as $child) {
                    $dataType = self::getDataType($child, $dataType);
                }
                break;
            default:
                $dataType = Meta::DATA_TYPE_STRING;
                break;
        }
        if (empty($prevType)) {
            return $dataType;
        } else {
            // 非数字操作的输出默认都为string
            if ($prevType != Meta::DATA_TYPE_NUMBER && !in_array($prevType, Meta::TYPE_STATE[Meta::DATA_TYPE_NUMBER])) {
                return Meta::DATA_TYPE_STRING;
            } else {
                return Meta::DATA_TYPE_NUMBER;
            }
        }
    }
}