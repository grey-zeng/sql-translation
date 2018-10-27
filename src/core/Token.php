<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:42 PM
 */

namespace SQLTranslation\core;


use Exception;

class Token {
    const ERROR_COLUMN = 1;
    const ERROR_FUNCTION = 2;
    const ERROR_TYPE = 3;

    public $type;
    public $value;
    public $child = [];
    public $parent;

    /**
     * Token constructor.
     * @param array $token
     * @throws Exception
     */
    public function __construct(array $token) {
        $this->type = $token['type'];
        $this->value = isset($token['value']) ? $token['value'] : '';
        if ($this->type == 'function' && !array_key_exists(strtolower($this->value), Meta::$supportFunc)) {
            throw new Exception('function not exits:' . $this->value, self::ERROR_FUNCTION);
        }
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
        if ($this->type == 'expr') {
            if (count($this->child) >= 2) {
                $param = 0;
                foreach ($this->child as $child) {
                    if ($child->type != '(' && $child->type != ')') {
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
            case 'column':
                foreach (CompileFieldService::$columns as $column) {
                    if ($column['column'] == $token->value) {
                        $dataType = $column['type'];
                    }
                }
                if (!isset($dataType)) {
                    foreach (CompileFieldService::$columns as $column) {
                        if ($column['alias'] == $token->value) {
                            $dataType = $column['type'];
                        }
                    }
                }
                if (!isset($dataType)) {
                    throw new Exception('无法找到对应的字段:' . $token->value);
                }
                break;
            case 'number':
                $dataType = 'number';
                break;
            case 'function':
                $dataType = Meta::$supportFunc[$token->value]['out'];
                break;
            case 'expr':
            case 'brackets':
                $dataType = '';
                foreach ($token->child as $child) {
                    $dataType = self::getDataType($child, $dataType);
                }
                break;
            default:
                $dataType = 'string';
                break;
        }
        if (empty($prevType)) {
            return $dataType;
        } else {
            // 非数字操作的输出默认都为string
            $numberFormat = ['number', 'int', 'float', 'date'];
            if (!in_array($prevType, $numberFormat) && !in_array($prevType, $numberFormat)) {
                return 'string';
            } else {
                return 'number';
            }
        }
    }
}