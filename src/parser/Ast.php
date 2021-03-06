<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/26
 * Time: 11:32 PM
 */

namespace SQLTranslation\parser;


use SQLTranslation\core\Token;

class Ast {
    /**
     * 语法分析-组合token树
     * 主要是按照function、操作符、括号包住的表达式 来进行父子多层树的划分，构成有层次的token树
     * @param array $tokens
     * @param $translator
     * @return Token
     * @throws \Exception
     */
    public static function combineToken($tokens, $translator) {
        $tree = new Token(['type' => Token::TYPE_ROOT], $translator);
        $currentTree = &$tree;
        foreach ($tokens as $token) {
            switch ($token['type']) {
                case Token::TYPE_BRACKET_LEFT:
                    // 进入函数/代码的参数区
                    if ($currentTree->hashChild() && in_array($currentTree->getLastChild()->type,[Token::TYPE_FUNCTION, Token::TYPE_STATEMENT])) {
                        $newNode = $currentTree->getLastChild();
                        $newNode->setParent($currentTree);
                        $currentTree = $newNode;
                    }
                    // 标记为计算表达式
                    else {
                        $newNode = new Token(['type' => Token::TYPE_BRACKETS], $translator);
                        $newNode->setParent($currentTree);
                        $currentTree->addChild($newNode);
                        $currentTree = $newNode;
                    }
                    break;
                case Token::TYPE_BRACKET_RIGHT:
                    while ($currentTree->isComplete()) {
                        $currentTree = $currentTree->parent;
                    }
                    // 从函数参数区出来
                    if ($currentTree->type == Token::TYPE_FUNCTION) {
                        $currentTree->checkParam();
                        $currentTree = $currentTree->parent;
                    }
                    // 代码区的参数解析完毕
                    else if ($currentTree->type == Token::TYPE_STATEMENT) {
                        $newNode = new Token(['type' => Token::TYPE_BRACKETS], $translator);
                        $newNode->setParent($currentTree);
                        foreach ($currentTree->child as $child) {
                            $child->setParent($newNode);
                            $newNode->addChild($child);
                        }
                        $currentTree->child = [$newNode];
                    }
                    // 计算公式
                    elseif ($currentTree->type == Token::TYPE_BRACKETS) {
                        $currentTree = $currentTree->parent;
                    }
                    break;
                // 将operator全部转成expr类型 替换掉上层节点
                case Token::TYPE_OPERATOR:
                    while ($currentTree->isComplete()) {
                        $currentTree = $currentTree->parent;
                    }
                    $newNode = new Token(['type' => Token::TYPE_EXPRESSION, 'value' => $token['value']], $translator);
                    $newNode->addChild(array_pop($currentTree->child));
                    $currentTree->addChild($newNode);
                    $newNode->setParent($currentTree);
                    $currentTree = $newNode;
                    break;
                case Token::TYPE_BRACE_LEFT:
                    if ($currentTree->type != Token::TYPE_STATEMENT) {
                        throw new \Exception('公式错误');
                    }
                    break;
                case Token::TYPE_BRACE_RIGHT:
                    // 代码区的代码块解析完毕
                    $currentTree = $currentTree->parent;
                    break;
                default:
                    // 追加token
                    while ($currentTree->isComplete()) {
                        $currentTree = $currentTree->parent;
                    }
                    $currentTree->addChild(new Token($token, $translator));
                    break;
            }
        };
        return $tree;
    }
}