<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 4:03 PM
 */
namespace SQLTranslation\tests\scanner;

use SQLTranslation\core\Token;
use SQLTranslation\scanner\DirectScanner;
use PHPUnit\Framework\TestCase;

class DirectScannerTest extends TestCase {

    public function testSplitToken() {
        // 简单的四则运算符：分成3个token
        $caseExpr = [
            'in' => '1+2',
            'out' => [
                ['type' => Token::TYPE_NUMBER, 'value' => 1],
                ['type' => Token::TYPE_OPERATOR, 'value' => '+'],
                ['type' => Token::TYPE_NUMBER, 'value' => 2],
            ],
        ];
        $this->assertEquals($caseExpr['out'], DirectScanner::splitToken($caseExpr['in']));

        // 单一函数:拆分成4个token
        $caseFunc = [
            'in' => 'abs([第一])',
            'out' => [
                ['type' => Token::TYPE_FUNCTION, 'value' => 'abs'],
                ['type' => Token::TYPE_BRACKET_LEFT, 'value' => '('],
                ['type' => Token::TYPE_COLUMN, 'value' => '第一'],
                ['type' => Token::TYPE_BRACKET_RIGHT, 'value' => ')'],
            ],
        ];
        $this->assertEquals($caseFunc['out'], DirectScanner::splitToken($caseFunc['in']));

        // 嵌套函数:if嵌套date_diff函数，需要拆分成n个
        $caseNest = [
            'in' => 'if(date_diff([时间],[新登时间])>3, "前期", "后期")',
            'out' => [
                ['type' => Token::TYPE_FUNCTION, 'value' => 'if'],
                ['type' => Token::TYPE_BRACKET_LEFT, 'value' => '('],
                ['type' => Token::TYPE_FUNCTION, 'value' => 'date_diff'],
                ['type' => Token::TYPE_BRACKET_LEFT, 'value' => '('],
                ['type' => Token::TYPE_COLUMN, 'value' => '时间'],
                ['type' => Token::TYPE_COLUMN, 'value' => '新登时间'],
                ['type' => Token::TYPE_BRACKET_RIGHT, 'value' => ')'],
                ['type' => Token::TYPE_OPERATOR, 'value' => '>'],
                ['type' => Token::TYPE_NUMBER, 'value' => '3'],
                ['type' => Token::TYPE_STRING, 'value' => '前期'],
                ['type' => Token::TYPE_STRING, 'value' => '后期'],
                ['type' => Token::TYPE_BRACKET_RIGHT, 'value' => ')'],
            ]
        ];
        $this->assertEquals($caseNest['out'], DirectScanner::splitToken($caseNest['in']));
    }
}
