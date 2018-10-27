<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:13 PM
 */

namespace SQLTranslation;

use SQLTranslation\core\Token;
use SQLTranslation\generator\MysqlCodeGen;
use SQLTranslation\generator\PgsqlCodeGen;
use SQLTranslation\parser\Ast;
use SQLTranslation\scanner\DirectScanner;

class Translator {

    // 用到了反向规则引用做递归检测 http://tieba.baidu.com/p/4117059926?pid=78999579376#78999579376
    // 需要满足：算子 = 算子 (操作符 算子) ... ;算子 = [字段]/公式(算子 (操作符 算子) ...)/数字/字符
    const FORMULA_REG = '(^((((\s*((\[[^\[\]]+\])|\d+|(\'[^\']+\')|("[^"]+"))\s*)([\+\-\*\%\<\>\=/&\|]{1,2}(?5))*)|(\s*\w*\s*\((?2)?((,|[\+\-\*\%\<\>\=/&\|]{1,2})(?2))*\)\s*))([\+\-\*\%\<\>\=/&\|]{1,2}(?2))*)$)';

    const DB_MYSQL = 'mysql';
    const DB_PGSQL = 'pgsql';

    /** @var [] 字段列表 */
    public $columns;
    /** @var string|null 字段别名前缀 */
    public $columnPrefix;
    /** @var string db类型 */
    public $dbType = self::DB_MYSQL;

    /** @var Token 抽象语法树 */
    protected $ast;


    public function setColumns($originColumns) {
        $columns = [];
        foreach ($originColumns as $originColumn) {
            $columns[$originColumn['alias']] = $originColumn;
        }
        $this->columns = $columns;
        return $this;
    }

    public function setColumnPrefix($columnPrefix) {
        $this->columnPrefix = $columnPrefix;
        return $this;
    }


    /**
     * 将公式正式进行翻译
     * @param $originFormula
     * @return Translator
     * @throws \Exception
     */
    public function compile($originFormula) {
        if (!self::isValid($originFormula)) {
            throw new \Exception('公式不符合定义');
        }
        // 通过分词器获取token列表
        $tokenList = DirectScanner::splitToken($originFormula);
        // 将token转化成ast，变成树状结构
        $this->ast = Ast::combineToken($tokenList, $this);
        return $this;
    }

    /**
     * @param string $dbType
     * @return string 目标码
     * @throws \Exception
     */
    public function translate($dbType = self::DB_MYSQL) {
        $this->dbType = $dbType;
        // 进行转码
        switch ($this->dbType) {
            case self::DB_PGSQL:
                $codegen = new PgsqlCodeGen($this);
                break;
            case self::DB_MYSQL:
                $codegen = new MysqlCodeGen($this);
                break;
            default:
                throw new \Exception('错误的目标db类型');
        }
        return $codegen->generator($this->ast);
    }

    /**
     * 检查传入的预定义公式是否满足基本需求
     * @param $formula
     * @return bool
     */
    public static function isValid($formula) {
        if (!is_string($formula) && !is_numeric($formula)) {
            return false;
        }
        return !!preg_match(self::FORMULA_REG, $formula);
    }
}