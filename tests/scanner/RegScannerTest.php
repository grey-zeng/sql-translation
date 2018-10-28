<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 9:42 PM
 */

namespace SQLTranslation\tests\scanner;


use PHPUnit\Framework\TestCase;
use SQLTranslation\core\Token;
use SQLTranslation\scanner\RegScanner;

class RegScannerTest extends TestCase {


    public function testRegEMPTY() {
        $this->assertEquals(1, preg_match(RegScanner::REG_EMPTY, ' ', $matches1));
        $this->assertEquals(' ', $matches1[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_EMPTY, ' , ', $matches2));
        $this->assertEquals(' , ', $matches2[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_EMPTY, "  ,, \n ", $matches3));
        $this->assertEquals("  ,, \n ", $matches3[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_EMPTY, ' , 123', $matches4));
        $this->assertEquals(' , ', $matches4[1]);

        $this->assertEquals(0, preg_match(RegScanner::REG_EMPTY, '1 '));
    }

    public function testRegSTATEMENT() {
        $this->assertEquals(1, preg_match(RegScanner::REG_STATEMENT, 'while(', $matches1));
        $this->assertEquals('while', $matches1[1]);

        $this->assertEquals(0, preg_match(RegScanner::REG_STATEMENT, 'abs('));
    }

    public function testRegFUNCTION() {
        $this->assertEquals(1, preg_match(RegScanner::REG_FUNCTION, 'abs(', $matches1));
        $this->assertEquals('abs', $matches1[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_FUNCTION, 'count_distinct (', $matches2));
        $this->assertEquals('count_distinct', $matches2[1]);

        $this->assertEquals(0, preg_match(RegScanner::REG_FUNCTION, '1,a('));
    }

    public function testRegBRACKET() {
        $this->assertEquals(1, preg_match(RegScanner::REG_BRACKET, '( ', $matches1));
        $this->assertEquals('(', $matches1[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_BRACKET, '(1', $matches2));
        $this->assertEquals('(', $matches2[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_BRACKET, ')123', $matches3));
        $this->assertEquals(')', $matches3[1]);

        $this->assertEquals(0, preg_match(RegScanner::REG_BRACKET, ' ('));
    }

    public function testRegCOLUMN() {
        $this->assertEquals(1, preg_match(RegScanner::REG_COLUMN, '[111]', $matches1));
        $this->assertEquals('111', $matches1[2]);
        $this->assertEquals(1, preg_match(RegScanner::REG_COLUMN, '[abc_1]', $matches2));
        $this->assertEquals('abc_1', $matches2[2]);
        $this->assertEquals(1, preg_match(RegScanner::REG_COLUMN, '[第一]', $matches3));
        $this->assertEquals('第一', $matches3[2]);

        $this->assertEquals(0, preg_match(RegScanner::REG_COLUMN, '[col'));
        $this->assertEquals(0, preg_match(RegScanner::REG_COLUMN, 'col]'));
    }

    public function testRegNUMBER() {
        $this->assertEquals(1, preg_match(RegScanner::REG_NUMBER, '111', $matches1));
        $this->assertEquals('111', $matches1[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_NUMBER, '0.3', $matches2));
        $this->assertEquals('0.3', $matches2[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_NUMBER, '-1.3', $matches3));
        $this->assertEquals('-1.3', $matches3[1]);

        $this->assertEquals(0, preg_match(RegScanner::REG_NUMBER, '--1'));
    }

    public function testRegSTRING() {
        $this->assertEquals(1, preg_match(RegScanner::REG_STRING, '"111"', $matches1));
        $this->assertEquals('111', $matches1[3]);
        $this->assertEquals(1, preg_match(RegScanner::REG_STRING, "'1\"1'1'", $matches2));
        $this->assertEquals('1"1', $matches2[3]);
        $this->assertEquals(1, preg_match(RegScanner::REG_STRING, "'1 1-\n' ", $matches3));
        $this->assertEquals("1 1-\n", $matches3[3]);
        $this->assertEquals(1, preg_match(RegScanner::REG_STRING, '"前期", "后期"', $matches4));
        $this->assertEquals('前期', $matches4[3]);
        $this->assertEquals(1, preg_match(RegScanner::REG_STRING, '""', $matches5));

        $this->assertEquals(0, preg_match(RegScanner::REG_NUMBER, '"234\''));
    }

    public function testRegOPERATOR() {
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '+', $matches1));
        $this->assertEquals('+', $matches1[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '-', $matches2));
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '*', $matches3));
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '/', $matches4));

        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '>', $matches5));
        $this->assertEquals('>', $matches5[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '<', $matches6));
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '<=', $matches7));
        $this->assertEquals('<=', $matches7[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '>=', $matches8));
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '=', $matches9));
        $this->assertEquals('=', $matches9[1]);

        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '&', $matches10));
        $this->assertEquals('&', $matches10[1]);
        $this->assertEquals(1, preg_match(RegScanner::REG_OPERATOR, '|', $matches11));
        $this->assertEquals('|', $matches11[1]);
    }

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
        $this->assertEquals($caseExpr['out'], RegScanner::splitToken($caseExpr['in']));

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
        $this->assertEquals($caseFunc['out'], RegScanner::splitToken($caseFunc['in']));

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
        $this->assertEquals($caseNest['out'], RegScanner::splitToken($caseNest['in']));
    }



}