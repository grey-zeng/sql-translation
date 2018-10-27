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
        $tree = new Token(['type' => 'root'], $translator);
        $currentTree = &$tree;
        foreach ($tokens as $token) {
            switch ($token['type']) {
                case '(':
                    // 进入函数参数区
                    if ($currentTree->hashChild() && $currentTree->getLastChild()->type == 'function') {
                        $newNode = $currentTree->getLastChild();
                        $newNode->setParent($currentTree);
                        $currentTree = $newNode;
                    } else {
                        // 认为是计算表达式
                        $newNode = new Token(['type' => 'brackets'], $translator);
                        $newNode->setParent($currentTree);
                        $currentTree->addChild($newNode);
                        $currentTree = $newNode;
                    }
                    break;
                case ')':
                    while ($currentTree->isComplete()) {
                        $currentTree = $currentTree->parent;
                    }
                    // 从函数参数区出来
                    if ($currentTree->type == 'function') {
                        $currentTree->checkParam();
                        $currentTree = $currentTree->parent;
                    } elseif ($currentTree->type == 'brackets') {
                        $currentTree = $currentTree->parent;
                    }
                    break;
                // 将operator全部转成expr类型 替换掉上层节点
                case 'operator':
                    while ($currentTree->isComplete()) {
                        $currentTree = $currentTree->parent;
                    }
                    $newNode = new Token(['type' => 'expr', 'value' => $token['value']], $translator);
                    $newNode->addChild(array_pop($currentTree->child));
                    $currentTree->addChild($newNode);
                    $newNode->setParent($currentTree);
                    $currentTree = $newNode;
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