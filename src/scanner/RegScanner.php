<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 9:06 PM
 */

namespace SQLTranslation\scanner;

use SQLTranslation\core\Token;

/**
 * 重写分词器，改用正则匹配来进行分词
 * Class RegScanner
 * @package SQLTranslation\scanner
 */
class RegScanner {

    const REG_EMPTY     = '/^([\s,]+)/';
    const REG_STATEMENT = '/^(while)(\s)?\(/';
    const REG_FUNCTION  = '/^(\w+)(\s)?\(/';
    const REG_BRACKET   = '/^(\(|\))/';
    const REG_COLUMN    = '/^(\[([\x{4e00}-\x{9fa5}a-zA-Z0-9_\-]+)\])/u';   // 使用Unicode解析
    const REG_NUMBER    = '/^((-?\d+)(\.\d+){0,1})/';
    const REG_STRING    = '/^((\'|\")([\s\S]*?)(\2))/';                     // 需要非贪婪匹配
    const REG_OPERATOR  = '/^(\+|\-|\*|\/|(>|<)(=)?|=|\&+|\|+)/';


    /**
     * 使用正则匹配进行扫描，不断往右移动
     * @param $formula
     * @return array
     * @throws \Exception
     */
    public static function splitToken($formula) {
        $token = [];

        // 定义规则列表
        $map = [
            [self::REG_STATEMENT,   Token::TYPE_STATEMENT,  1, 1],
            [self::REG_FUNCTION,    Token::TYPE_FUNCTION,   1, 1],
            [self::REG_COLUMN,      Token::TYPE_COLUMN,     2, 1],
            [self::REG_NUMBER,      Token::TYPE_NUMBER,     1, 1],
            [self::REG_STRING,      Token::TYPE_STRING,     3, 1],
            [self::REG_OPERATOR,    Token::TYPE_OPERATOR,   1, 1],
        ];

        while (!empty($formula)) {
            // 空白符忽略不计
            if (preg_match(self::REG_EMPTY, $formula, $matches)) {
                $formula = substr($formula, strlen($matches[1]));
                continue;
            }
            // 按照规则列表进行匹配
            foreach ($map as list($pattern, $tokenType, $index, $delIndex)) {
                if (preg_match($pattern, $formula, $matches)) {
                    $token[] = [
                        'type' => $tokenType,
                        'value' => $matches[$index],
                    ];
                    $formula = substr($formula, strlen($matches[$delIndex]));
                    continue 2;
                }
            }
            // 特殊处理括号
            if (preg_match(self::REG_BRACKET, $formula, $matches)) {
                $token[] = [
                    'type' => $matches[1] == '('? Token::TYPE_BRACKET_LEFT: Token::TYPE_BRACKET_RIGHT,
                    'value' => $matches[1]
                ];
                $formula = substr($formula, 1);
                continue;
            }
            throw new \Exception('公式错误:'.$formula);
        }
        return $token;
    }
}