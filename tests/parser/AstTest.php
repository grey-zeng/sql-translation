<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:33 PM
 */

namespace SQLTranslation\tests\parser;

use SQLTranslation\parser\Ast;
use PHPUnit\Framework\TestCase;

class AstTest extends TestCase {
    public function testAst() {
        $ast = new Ast();
        $this->assertEquals($ast->ast(), 'ast');
    }
}
