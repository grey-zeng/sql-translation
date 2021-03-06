<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:33 PM
 */

namespace SQLTranslation\tests\parser;

use SQLTranslation\core\Meta;
use SQLTranslation\core\Token;
use SQLTranslation\parser\Ast;
use PHPUnit\Framework\TestCase;
use SQLTranslation\Translator;

class AstTest extends TestCase {
    public function testCombineToken() {
        $translatror = new Translator();

        /**
         * 输入公式为(1+3)
         * 输出ast为
         *    root
         *     ↓
         *     ()
         *     ↓
         *  expr(表达式)
         *     ↓
         *  1, +, 3
         */
        $caseExprTokens = [
            ['type' => Token::TYPE_BRACKET_LEFT],
            ['type' => Token::TYPE_NUMBER, 'value' => '1'],
            ['type' => Token::TYPE_OPERATOR, 'value' => '+'],
            ['type' => Token::TYPE_NUMBER, 'value' => '3'],
            ['type' => Token::TYPE_BRACKET_RIGHT],
        ];
        $caseExprRoot = 'O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:4:"root";s:5:"value";s:0:"";s:5:"child";a:1:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:8:"brackets";s:5:"value";s:0:"";s:5:"child";a:1:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:4:"expr";s:5:"value";s:1:"+";s:5:"child";a:2:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"number";s:5:"value";s:1:"1";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";O:25:"SQLTranslation\Translator":4:{s:7:"columns";a:0:{}s:12:"columnPrefix";N;s:6:"dbType";s:5:"mysql";s:6:" * ast";N;}}i:1;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"number";s:5:"value";s:1:"3";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";r:18;}}s:6:"parent";r:5;s:10:"translator";r:18;}}s:6:"parent";r:1;s:10:"translator";r:18;}}s:6:"parent";N;s:10:"translator";r:18;}';
        $this->assertEquals($caseExprRoot, serialize(Ast::combineToken($caseExprTokens, $translatror)));

        /**
         * 输入公式为：if(date_diff([时间],[新登时间])>3, "后期", "前期")
         * 输出ast为
         *                  root
         *                    ↓
         *                   if
         *                    ↓
         *             expr,  后期,  前期
         *               ↓
         *    date_diff, >, 3
         *        ↓
         *   时间，新登时间
         */
        $caseNestTokens = [
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
        ];
        $translatror->setColumns([
            ['alias' => '时间', 'column' => 'rectime', 'type' => Meta::DATA_TYPE_TIMESTAMP],
            ['alias' => '新登时间', 'column' => 'first_login_time', 'type' => Meta::DATA_TYPE_TIMESTAMP],
        ]);
        $caseNestRoot = 'O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:4:"root";s:5:"value";s:0:"";s:5:"child";a:1:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:8:"function";s:5:"value";s:2:"if";s:5:"child";a:3:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:4:"expr";s:5:"value";s:1:">";s:5:"child";a:2:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:8:"function";s:5:"value";s:9:"date_diff";s:5:"child";a:2:{i:0;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"column";s:5:"value";s:7:"rectime";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";O:25:"SQLTranslation\Translator":4:{s:7:"columns";a:2:{s:6:"时间";a:3:{s:5:"alias";s:6:"时间";s:6:"column";s:7:"rectime";s:4:"type";s:9:"timestamp";}s:12:"新登时间";a:3:{s:5:"alias";s:12:"新登时间";s:6:"column";s:16:"first_login_time";s:4:"type";s:9:"timestamp";}}s:12:"columnPrefix";N;s:6:"dbType";s:5:"mysql";s:6:" * ast";N;}}i:1;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"column";s:5:"value";s:16:"first_login_time";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";r:22;}}s:6:"parent";r:5;s:10:"translator";r:22;}i:1;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"number";s:5:"value";s:1:"3";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";r:22;}}s:6:"parent";r:5;s:10:"translator";r:22;}i:1;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"string";s:5:"value";s:6:"前期";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";r:22;}i:2;O:25:"SQLTranslation\core\Token":5:{s:4:"type";s:6:"string";s:5:"value";s:6:"后期";s:5:"child";a:0:{}s:6:"parent";N;s:10:"translator";r:22;}}s:6:"parent";r:1;s:10:"translator";r:22;}}s:6:"parent";N;s:10:"translator";r:22;}';
        var_export(unserialize($caseNestRoot));
        $this->assertEquals($caseNestRoot, serialize(Ast::combineToken($caseNestTokens, $translatror)));

    }

    public function testCombineTokenErrorColumn() {
        $translator = new Translator();
        // 测试公式：[不存在的字段]+3
        $tokens = [
            ['type' => Token::TYPE_COLUMN, 'value' => '不存在的字段'],
            ['type' => Token::TYPE_OPERATOR, 'value' => '+'],
            ['type' => Token::TYPE_NUMBER, 'value' => '3'],
        ];
        $this->expectException(\Exception::class);
        Ast::combineToken($tokens, $translator);
    }
}
