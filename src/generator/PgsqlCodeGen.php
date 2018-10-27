<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 8:24 AM
 */

namespace SQLTranslation\generator;


class PgsqlCodeGen extends BaseCodeGen {

    protected $columnWrapperSymbol = '"';


    protected function wrapperExpr($expr, $params) {
        // pgsql在处理 / 的时候，得出的值是整数 需要使用cast(column as numeric)
        if ($expr == '/') {
            return array_map(function($param) {
                return "cast({$param} as numeric)";
            }, $params);
        } else {
            return $params;
        }
    }

    /**
     * 特定的平台代码
     * @param string $funcName
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    protected function translateFunction($funcName, $params) {
        switch ($funcName) {
            case 'count_distinct':
                return "count(distinct({$params[0]['value']}))";
            case 'rand':
                return 'random()';
            case 'pow':
                $funcName = 'power';
                break;
            case 'substr':
                $funcName = 'substring';
                break;
            case 'time_convert':
            case 'from_unixtime':
                $funcName = 'to_timestamp';
                break;
            case 'concat':
                return '(' . join('||', array_map(function ($param) {
                        return '(' . $param['value'] . ')';
                    }, $params)) . ')';
            case 'base64_encode':
                return "encode({$params[0]['value']}, 'base64')";
            case 'base64_decode':
                return "decode({$params[0]['value']}, 'base64')";
            // 时间类型
            case 'day':
            case 'hour':
            case 'month':
            case 'minus':
            case 'quarter':
            case 'week':
            case 'year':
                $timeFormat = strtoupper($funcName);
                $columnFormat = self::tranPgTimestamp($params[0]['value'], $params[0]['type']);
                return "EXTRACT({$timeFormat} from {$columnFormat})";
            case 'date_add':
                $columnFormat = self::tranPgTimestamp($params[0]['value'], $params[0]['type']);
                return "{$columnFormat} + interval '{$params[1]['value']} day'";
            case 'date_sub':
                $columnFormat = self::tranPgTimestamp($params[0]['value'], $params[0]['type']);
                return "{$columnFormat} + interval '{$params[1]['value']} day'";
            case 'day_diff':
            case 'hour_diff':
            case 'minute_diff':
            case 'month_diff':
            case 'second_diff':
            case 'year_diff':
                $timeFormat = substr($funcName, 0, strlen($funcName)-6);
                $first = self::tranPgTimestamp($params[0]['value'], $params[0]['type']);
                $second = self::tranPgTimestamp($params[1]['value'], $params[1]['type']);
                return "extract({$timeFormat} from ({$first} - {$second}))";
            case 'date_diff':
                $first = self::tranPgTimestamp($params[0]['value'], $params[0]['type']);
                $second = self::tranPgTimestamp($params[1]['value'], $params[1]['type']);
                return "{$first}::date-{$second}::date";
            case 'if':
                return "(case when {$params[0]['value']} IS NOT NULL then {$params[1]['value']} else {$params[2]['value']} end)";
            case 'to_date':
                return self::getTime2Sql('YYYY-MM-DD', $params[0]['type'], $params[0]['value'], false);
            default:
        }
        // 默认直接返回function(param1, param2 + param3, function(param4), ...)
        return $funcName . '(' . join(',', array_map(function ($param) {
                return $param['value'];
            }, $params)) . ')';
    }

    /**
     * 底层数据存储不是时间类型，需要在这里使用函数来进行转化
     * @param $value
     * @param $type
     * @return string
     * @throws \Exception
     */
    private static function tranPgTimestamp($value, $type) {
        switch ($type) {
            case 'dateStr':
                return "to_date({$value}, 'YYYY-MM-DD HH24:MI:SS')";
            case 'date':
                return "to_timestamp({$value})";
            case 'string':
                return "TIMESTAMP {$value}";
            case 'timestamp':
                return "{$value}";
            case 'dateMs':
                return "to_timestamp({$value}/1000)";
            default:
                throw new \Exception('error timestamp type:' . $type);
        }
    }
}