<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 8:14 AM
 */

namespace SQLTranslation\scanner;


class DirectScanner {
    /**
     * 词法分析-分割token 采用直接扫描法
     * @param $formula
     * @return array
     */
    public static function splitToken($formula) {
        $token = [];
        $index = 0;
        $formula .= ' ';
        while ($index < strlen($formula)) {
            // token类型有：[字段],数字,字符,公式,操作符,空格,()
            $ch = $formula[$index];
            // 空格或逗号
            if ($ch == ' ' || $ch == ',') {
                $index++;
            } // ()
            elseif ($ch == '(' || $ch == ')') {
                $token[] = [
                    'type' => $ch,
                    'value' => $ch
                ];
                $index++;
            } // 字段类型
            elseif ($ch == '[') {
                $word = '';
                while ($formula[++$index] != ']') {
                    $word .= $formula[$index];
                }
                $token[] = [
                    'type' => 'column',
                    'value' => $word
                ];
                $index++;
            } // 数字
            elseif (strstr('0123465789.', $ch)) {
                $word = $ch;
                while (strstr('0123465789.', $formula[++$index])) {
                    $word .= $formula[$index];
                }
                $token[] = [
                    'type' => 'number',
                    'value' => floatval($word)
                ];
            } // 字符
            elseif ($ch == '\'' || $ch == '"') {
                $left = $ch;
                $word = '';
                $prevCh = $formula[$index];
                $nextCh = $formula[++$index];
                while ($prevCh == '\\' || $nextCh != $left ) {
                    $word .= $nextCh;
                    $prevCh = $nextCh;
                    $nextCh = $formula[++$index];
                }
                $index++;
                $token[] = [
                    'type' => 'string',
                    'value' => stripslashes($word)
                ];
            } // 操作符
            elseif (in_array($ch, ['<', '>', '=', '+', '-', '*', '/', '%', '&', '|'])) {
                $word = $ch;
                $index ++;
                if ($formula[$index] == '=' || $formula[$index] == $ch) {
                    $word .= $formula[$index];
                    $index++;
                }
                $token[] = [
                    'type' => 'operator',
                    'value' => $word
                ];
            } // 函数 禁止数字开头的函数
            elseif (is_string($ch)) {
                $word = $ch;
                while ($formula[++$index] != '(') {
                    $word .= $formula[$index];
                }
                $token[] = [
                    'type' => 'function',
                    'value' => strtolower($word)
                ];
            }
        }
        return $token;
    }
}