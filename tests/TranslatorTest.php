<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:15 PM
 */

namespace SQLTranslation\tests;

use PHPUnit\Framework\TestCase;
use SQLTranslation\Translator;

class TranslatorTest extends TestCase {

    static $columns = [
        '第一' => [
            'alias' => '第一',
            'column' => 'column_1',
            'type' => 'number'
        ],
        '第二' => [
            'alias' => '第二',
            'column' => 'column_2',
            'type' => 'string'
        ],
        '第三' => [
            'alias' => '第三',
            'column' => 'column_3',
            'type' => 'date'
        ],
        '第四' => [
            'alias' => '第四',
            'column' => 'column_4',
            'type' => 'dateStr'
        ],
        'int' => [
            'alias' => 'int',
            'column' => 'c_int',
            'type' => 'int'
        ],
        'float' => [
            'alias' => 'float',
            'column' => 'c_float',
            'type' => 'float'
        ],
        'number' => [
            'alias' => 'number',
            'column' => 'c_number',
            'type' => 'number'
        ],
        'string' => [
            'alias' => 'string',
            'column' => 'c_string',
            'type' => 'string'
        ],
        'date' => [
            'alias' => 'date',
            'column' => 'c_date',
            'type' => 'date'
        ],
        'dateStr' => [
            'alias' => 'dateStr',
            'column' => 'c_dateStr',
            'type' => 'dateStr'
        ],
        'time' => [
            'alias' => 'time',
            'column' => 'c_time',
            'type' => 'time'
        ],
        'timestamp' => [
            'alias' => 'timestamp',
            'column' => 'c_timestamp',
            'type' => 'timestamp'
        ],
        'test_a' => [
            'alias' => 'test_a',
            'column' => 'c_test_a',
            'type' => 'string'
        ],
        'test_b' => [
            'alias' => 'test_b',
            'column' => 'c_test_b',
            'type' => 'string'
        ]
    ];

    /**
     * 校验基于postgresql的公式转化
     */
    public function testCompilePostgreSQL() {
        $formulas = [
            [
                'in' => '1+2',
                'out' => '1+2'
            ],
            [
                'in' => '[第一]',
                'out' => '"column_1"'
            ],
            [
                'in' => '[第一]+2',
                'out' => '"column_1"+2'
            ],
            [
                'in' => '[第一]+[第二]',
                'out' => '"column_1"+"column_2"'
            ],
            [
                'in' => '1+(2/(3+4))',
                'out' => '1+(cast(2 as numeric)/cast((3+4) as numeric))'
            ],
            [
                'in' => 'abs([第一])/[第二]',
                'out' => 'cast(abs("column_1") as numeric)/cast("column_2" as numeric)'
            ],
            [
                'in' => 'count([第一]*5)+[第二]',
                'out' => 'count("column_1"*5)+"column_2"'
            ],
            [
                'in' => 'count_distinct([第一]*5)+[第二]',
                'out' => 'count(distinct("column_1"*5))+"column_2"'
            ],
            [
                'in' => 'abs([第一]*5)+concat([第二]+3,ceil([第三]*5))',
                'out' => 'abs("column_1"*5)+(("column_2"+3)||(ceil("column_3"*5)))'
            ],
            [
                'in' => 'day([第四])',
                'out' => 'EXTRACT(DAY from to_date("column_4", \'YYYY-MM-DD HH24:MI:SS\'))'
            ],
            [
                'in' => 'day([第三])',
                'out' => 'EXTRACT(DAY from to_timestamp("column_3"))'
            ],
            [
                'in' => 'CONCAT([test_a],[test_b])',
                'out' => '(("c_test_a")||("c_test_b"))'
            ],
        ];
        $translator = new Translator();
        $translator->setColumns(self::$columns);
        foreach ($formulas as $formula) {
            $res = $translator->compile($formula['in'])->translate(Translator::DB_PGSQL);
            $this->assertEquals($formula['out'], $res);
        }
    }

    /**
     * 检查mysql引擎下的编译过程
     */
    public function testCompileMysql() {
        $formulas = [
            [
                'in' => '1+2',
                'out' => '1+2'
            ],
            [
                'in' => '[第一]',
                'out' => '`column_1`'
            ],
            [
                'in' => '[第一]+2',
                'out' => '`column_1`+2'
            ],
            [
                'in' => '[第一]+[第二]',
                'out' => '`column_1`+`column_2`'
            ],
            [
                'in' => '1+(2/(3+4))',
                'out' => '1+(2/(3+4))'
            ],
            [
                'in' => 'abs([第一])/[第二]',
                'out' => 'abs(`column_1`)/`column_2`'
            ],
            [
                'in' => 'count([第一]*5)+[第二]',
                'out' => 'count(`column_1`*5)+`column_2`'
            ],
            [
                'in' => 'count_distinct([第一]*5)+[第二]',
                'out' => 'count(distinct(`column_1`*5))+`column_2`'
            ],
            [
                'in' => 'abs([第一]*5)+concat([第二]+3,ceil([第三]*5))',
                'out' => 'abs(`column_1`*5)+concat(`column_2`+3,ceil(`column_3`*5))'
            ],
            [
                'in' => 'day([第四])',
                'out' => 'DATE_FORMAT(`column_4`,\'%d\')'
            ],
            [
                'in' => 'day([第三])',
                'out' => 'DATE_FORMAT(from_unixtime(`column_3`),\'%d\')'
            ],
            [
                'in' => '1 && 1',
                'out' => '1&&1'
            ],
            [
                'in' => '([test_a] || 2) && [第二]',
                'out' => '(`c_test_a`||2)&&`column_2`'
            ],
        ];
        $translator = new Translator();
        $translator->setColumns(self::$columns);
        foreach ($formulas as $formula) {
            $res = $translator->compile($formula['in'])->translate(Translator::DB_MYSQL);
            $this->assertEquals($formula['out'], $res, 'origin:' . $formula['in']);
        }
    }

    /**
     * 测试快速校验公式是否满足规则
     */
    public function testIsValid() {
        // 可以通过校验的公式
        $formulas = [
            '1+2',
            '(1+2)',
            '[第一]+[第二]',
            '[第一]/[第二]',
            '(2+func(1,2))',
            '(2+(1+2))',
            '1+(2/(3+4))',
            'count_distinct([第一]*5)+[第二]',
            'concat([第一], [第二]) + \'%\'',
            '\'+-234ser\' + "234_ )(+2s" + "\'"',
            'abs([第一]*5)+concat([第二]+3,ceil([第三]*5))',
            '[test_a]+[test_b]',
            'now()',
            // 支持与或逻辑
            '1 && 1',
            '([test_a] || 2) && [第二]',
            'if(((([操作系统ID]=2) && ([平台ID]=202) && ([充值渠道]=138)) || (([操作系统ID]=2) && ([平台ID]=202) && ([充值渠道]=139)) || (([操作系统ID]=2) && ([平台ID]=202) && ([充值渠道]=140)) || (([操作系统ID]=1) && ([充值渠道]=29)) || (([操作系统ID]=1) && ([充值渠道]=101)) || (([操作系统ID]=1) && ([充值渠道]=70))),"切支付","非切支付")'
        ];
        foreach ($formulas as $formula) {
            $this->assertTrue(Translator::isValid($formula), $formula);
        }
        // 明显异常的公式
        $errorFormulas = [
            '[无',
            '1+2)',
            '234 fe()',
            'wer,2',
            ' "-"-" + \' \' \' '
        ];
        foreach ($errorFormulas as $errorFormula) {
            $this->assertFalse(Translator::isValid($errorFormula), $errorFormula);
        }
    }
}
