<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:46 PM
 */

namespace SQLTranslation\core;


class Meta {
    
    const DATA_TYPE_INT = 'int';
    const DATA_TYPE_FLOAT = 'float';
    const DATA_TYPE_DATE = 'date';
    const DATA_TYPE_DATE_STR = 'dateStr';
    const DATA_TYPE_TIMESTAMP = 'timestamp';
    const DATA_TYPE_STRING = 'string';
    const DATA_TYPE_NUMBER = 'number';
    const DATA_TYPE_TIME = 'time';
    const DATA_TYPE_ANY = 'any';
    
    // 对照下方的数据类型转化表
    const TYPE_STATE = [
        self::DATA_TYPE_NUMBER   => [self::DATA_TYPE_INT, self::DATA_TYPE_FLOAT, self::DATA_TYPE_DATE],
        self::DATA_TYPE_STRING   => [self::DATA_TYPE_DATE_STR, self::DATA_TYPE_TIMESTAMP],
        self::DATA_TYPE_DATE_STR => [self::DATA_TYPE_TIMESTAMP],
        self::DATA_TYPE_TIME     => [self::DATA_TYPE_STRING, self::DATA_TYPE_DATE_STR, self::DATA_TYPE_TIMESTAMP, self::DATA_TYPE_DATE]
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
        return $funcInput == 'any' || $funcInput == $paramType || in_array($paramType, self::TYPE_STATE[$funcInput]);
    }

    // pgsql常用函数：http://blog.csdn.net/sun5769675/article/details/50628979
    // 计算字段不支持使用聚合函数
    public static $supportFunc = [
        // 聚合
        'avg' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'max' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'min' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ]
        , 
        'sum' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'count' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'count_distinct' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_NUMBER
        ],
        // 数值
        'abs' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'ceil' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ]
        , 
        'floor' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'ln' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'log' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'pow' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'rand' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'round' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_NUMBER
        ],
        // 字符串
        'concat' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_STRING
        ], 
        'base64_decode' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_STRING
        ], 
        'base64_encode' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_STRING
        ], 
        'length' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'lower' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_STRING
        ], 
        'reverse' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_STRING
        ], 
        'repeat' => [
            'in' => [self::DATA_TYPE_STRING, self::DATA_TYPE_NUMBER],
            'out' => self::DATA_TYPE_STRING
        ], 
        'substr' => [
            'in' => [self::DATA_TYPE_STRING, self::DATA_TYPE_NUMBER],
            'out' => self::DATA_TYPE_STRING
        ], 
        'trim' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_STRING
        ], 
        'upper' => [
            'in' => self::DATA_TYPE_STRING,
            'out' => self::DATA_TYPE_STRING
        ],
        // 时间
        'from_unixtime' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_TIMESTAMP
        ], 
        'time_convert' => [
            'in' => self::DATA_TYPE_NUMBER,
            'out' => self::DATA_TYPE_TIMESTAMP
        ], 
        'day' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'hour' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'month' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'minute' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'now' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_TIMESTAMP
        ], 
        'quarter' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'week' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'year' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_INT
        ], 
        'date_add' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_NUMBER],
            'out' => self::DATA_TYPE_TIME
        ], 
        'date_sub' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_NUMBER],
            'out' => self::DATA_TYPE_TIME
        ], 
        'day_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'date_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'hour_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'minute_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'month_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'second_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'year_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ], 
        'to_date' => [
            'in' => self::DATA_TYPE_TIME,
            'out' => self::DATA_TYPE_STRING
        ],
        'period_diff' => [
            'in' => [self::DATA_TYPE_TIME, self::DATA_TYPE_TIME],
            'out' => self::DATA_TYPE_NUMBER
        ],
        // 逻辑
        'if' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_ANY
        ], 
        'coalesce' => [
            'in' => self::DATA_TYPE_ANY,
            'out' => self::DATA_TYPE_ANY
        ],
    ];
}