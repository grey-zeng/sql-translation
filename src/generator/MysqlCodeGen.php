<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 8:24 AM
 */

namespace SQLTranslation\generator;


class MysqlCodeGen extends BaseCodeGen {

    protected $columnWrapperSymbol = '`';

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
            // 时间类型
            case 'time_convert':
                $funcName = 'from_unixtime';
                break;
            case 'day':
            case 'hour':
            case 'month':
            case 'minus':
            case 'week':
            case 'year':
                $timeFormat = [
                    'day' => '%d',
                    'hour' => '%H',
                    'month' => '%m',
                    'minute' => '%i',
                    'week' => '%v',
                    'year' => '%Y'
                ][$funcName];
                $columnFormat = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                return "DATE_FORMAT({$columnFormat},'{$timeFormat}')";
            case 'quarter':
                $columnFormat = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                return "QUARTER({$columnFormat})";
            case 'date_add':
                $columnFormat = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                return "date_add($columnFormat, interval {$params[1]['value']} day)";
            case 'date_sub':
                $columnFormat = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                return "date_add($columnFormat, interval -{$params[1]['value']} day)";
            case 'day_diff':
            case 'hour_diff':
            case 'minute_diff':
            case 'month_diff':
            case 'second_diff':
            case 'year_diff':
                $timeFormat = substr($funcName, 0, strlen($funcName)-6);
                $first = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                $second = self::tranMySqlTimestamp($params[1]['value'], $params[1]['type']);
                // 默认是后减前
                return "TimeStampDiff({$timeFormat},{$second},{$first})";
            case 'date_diff':
                $first = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                $second = self::tranMySqlTimestamp($params[1]['value'], $params[1]['type']);
                return "datediff({$first}, {$second})";
            case 'to_date':
                return self::getTime2Sql('%Y-%m-%d', $params[0]['type'], $params[0]['value']);
            // mysql独有函数
            case 'period_diff':
                $firstColumn = self::tranMySqlTimestamp($params[0]['value'], $params[0]['type']);
                $secondColumn = self::tranMySqlTimestamp($params[1]['value'], $params[1]['type']);
                return "period_diff(date_format({$firstColumn},'%Y%m'),date_format({$secondColumn},'%Y%m'))";
            case 'coalesce':
                return "ifnull({$params[0]['value']},{$params[1]['value']})";
            default:
        };
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
    private static function tranMySqlTimestamp($value, $type) {
        switch ($type) {
            case 'dateStr':
                return $value;
            case 'date':
                return "from_unixtime({$value})";
            case 'string':
                return $value;
            case 'timestamp':
                return "{$value}";
            case 'dateMs':
                return "from_unixtime({$value}/1000)";
            default:
                throw new \Exception('error timestamp type:' . $type);
        }
    }

    /**
     * @param $timeFormat
     * @param $dataType
     * @param $fname
     * @return string
     * @throws \Exception
     */
    protected static function getTime2Sql($timeFormat, $dataType, $fname) {
        if ($dataType == 'date') {
            $granularityItem = sprintf("from_unixtime(%s, '%s')", $fname, $timeFormat);
        } elseif ($dataType == 'dateStr' || $dataType == 'time') {
            $granularityItem = sprintf("from_unixtime(unix_timestamp(%s), '%s')", $fname, $timeFormat);
        } elseif ($dataType == 'timestamp') {
            $granularityItem = sprintf("date_format(%s, '%s')", $fname, $timeFormat);
        } else {
            throw new \Exception('错误的数据类型');
        }
        return $granularityItem;
    }
}