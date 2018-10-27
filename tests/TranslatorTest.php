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

    public function testHello() {
        $translator = new Translator();
        $this->assertEquals($translator->hello(), 'hello');
    }
}
