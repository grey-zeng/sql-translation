<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:46 PM
 */

namespace SQLTranslation\core;


class Meta {
// 对照下方的数据类型转化表
    private static $typeState = [
        'number' => ['int', 'float', 'date'],
        'string' => ['dateStr', 'timestamp'],
        'dateStr' => ['timestamp'],
        'time' => ['string', 'dateStr', 'timestamp', 'date']
    ];

    /**
     * 检查函数入
     * @param string $func
     * @param array $paramType
     * @return bool
     */
    public static function checkTypeCorrect($func, $paramType) {
        $funcInput = self::$supportFunc[$func]['in'];
        if (empty($funcInput)) {
            return true;
        } else if (!is_array($funcInput)) {
            $funcInput = array_fill(0, count($paramType), $funcInput);
        }
        // 一个个检测
        foreach ($funcInput as $index => $funcInputItem) {
            if (!self::_checkTypeCorrect($funcInputItem, $paramType[$index])) {
                return false;
            }
        }
        return true;
    }

    private static function _checkTypeCorrect($funcInput, $paramType) {
        return $funcInput == 'any' || $funcInput == $paramType || in_array($paramType, self::$typeState[$funcInput]);
    }

    // pgsql常用函数：http://blog.csdn.net/sun5769675/article/details/50628979
    // 计算字段不支持使用聚合函数
    public static $supportFunc = [
        // 聚合
        'avg' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'max' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'min' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'sum' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'count' => [
            'in' => 'any',
            'out' => 'number'
        ]
        , 'count_distinct' => [
            'in' => 'any',
            'out' => 'number'
        ]
        // 数值
        , 'abs' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'ceil' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'floor' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'ln' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'log' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'pow' => [
            'in' => 'number',
            'out' => 'number'
        ]
        , 'rand' => [
            'in' => 'any',
            'out' => 'number'
        ]
        , 'round' => [
            'in' => 'number',
            'out' => 'number'
        ]
        // 字符串
        , 'concat' => [
            'in' => 'any',
            'out' => 'string'
        ]
        , 'base64_decode' => [
            'in' => 'string',
            'out' => 'string'
        ]
        , 'base64_encode' => [
            'in' => 'string',
            'out' => 'string'
        ]
        , 'length' => [
            'in' => 'string',
            'out' => 'number'
        ]
        , 'lower' => [
            'in' => 'string',
            'out' => 'string'
        ]
        , 'reverse' => [
            'in' => 'string',
            'out' => 'string'
        ]
        , 'repeat' => [
            'in' => ['string', 'number'],
            'out' => 'string'
        ]
        , 'substr' => [
            'in' => ['string', 'number'],
            'out' => 'string'
        ]
        , 'trim' => [
            'in' => 'string',
            'out' => 'string'
        ]
        , 'upper' => [
            'in' => 'string',
            'out' => 'string'
        ]
        // 时间
        // todo 需要进一步补全函数
        , 'from_unixtime' => [
            'in' => 'number',
            'out' => 'timestamp'
        ]
        , 'time_convert' => [
            'in' => 'number',
            'out' => 'timestamp'
        ], 'day' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'hour' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'month' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'minute' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'now' => [
            'in' => 'any',
            'out' => 'timestamp'
        ]
        , 'quarter' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'week' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'year' => [
            'in' => 'time',
            'out' => 'int'
        ]
        , 'date_add' => [
            'in' => ['time', 'number'],
            'out' => 'time'
        ]
        , 'date_sub' => [
            'in' => ['time', 'number'],
            'out' => 'time'
        ]
        , 'day_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'date_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'hour_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'minute_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'month_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'second_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'year_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ]
        , 'to_date' => [
            'in' => 'time',
            'out' => 'string'
        ]
        // 逻辑
        , 'if' => [
            'in' => 'any',
            'out' => 'any'
        ]
        , 'coalesce' => [
            'in' => 'any',
            'out' => 'any'
        ]
        // 自定义追加
        , 'period_diff' => [
            'in' => ['time', 'time'],
            'out' => 'number'
        ],
    ];
}