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
            case 'root':
                return array_reduce($token->child, function ($prev, $node) {
                    return $prev . $this->generator($node);
                }, '');
                break;
            case 'function':
                $params = [];
                foreach ($token->child as $param) {
                    $params[] = [
                        'value' => $this->generator($param),
                        'type' => Token::getDataType($param)
                    ];
                }
                return $this->translateFunction(strtolower($token->value), $params);
            case 'string':
                return '"' . addslashes($token->value) . '"';
            case 'column':
                return $this->wrapperColumn($token->value);
            case 'expr':
                $params = array_map(function ($token) {
                    return $this->generator($token);
                }, $token->child);
                list($firstParam, $secondParam) = $this->wrapperExpr($token->value, $params);
                return $firstParam . $token->value . $secondParam;
            case 'brackets':
                return array_reduce($token->child, function ($res, $token) {
                        return $res . $this->generator($token);
                    }, '(') . ')';
            case 'number':
                return $token->value;
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
     * @param $timeFormat
     * @param $dataType
     * @param $fname
     * @param $isMysql
     * @return string
     * @throws \Exception
     */
    protected static function getTime2Sql($timeFormat, $dataType, $fname, $isMysql) {
        if ($dataType == 'date') {
            $granularityItem = $isMysql ? sprintf("from_unixtime(%s, '%s')", $fname, $timeFormat)
                : sprintf("(to_char( to_timestamp(%s), '%s') )", $fname, $timeFormat);
        } elseif ($dataType == 'dateStr' || $dataType == 'time') {
            $granularityItem = $isMysql ?
                sprintf("from_unixtime(unix_timestamp(%s), '%s')", $fname, $timeFormat)
                : sprintf("(to_char( to_timestamp(%s, 'YYYY-MM-DD HH24:MI:SS'), '%s') )", $fname, $timeFormat);
        } elseif ($dataType == 'timestamp') {
            $granularityItem = $isMysql ?
                sprintf("date_format(%s, '%s')", $fname, $timeFormat)
                : sprintf("(to_char( %s, '%s') )", $fname, $timeFormat);
        } else {
            throw new \Exception('错误的数据类型');
        }
        return $granularityItem;
    }
}