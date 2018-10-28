<?php
/**
 * Created by PhpStorm.
 * User: zqx
 * Date: 2018/10/27
 * Time: 3:26 PM
 */

require __DIR__ . '/../vendor/autoload.php';

use SQLTranslation\core\Meta;
use SQLTranslation\Translator;

print_r("场景：需要计算用户ltv3，定义为用户自新登当天内3天的充值总额*分成比例\n");
print_r("步骤：用户根据已有的表定义得知对应的字段关系，新增逻辑字段，使用扩充后的逻辑字段进行查询\n\n");

// 数据表中的各个字段
$tableColumns = [
    [
        'alias' => '新登时间',
        'column' => 'first_login_time',
        'type' => Meta::DATA_TYPE_TIMESTAMP
    ],
    [
        'alias' => '时间',
        'column' => 'retime',
        'type' => Meta::DATA_TYPE_TIMESTAMP
    ],
    [
        'alias' => '账号id',
        'column' => 'uid',
        'type' => Meta::DATA_TYPE_STRING
    ],
    [
        'alias' => '金额',
        'column' => 'source_money',
        'type' => Meta::DATA_TYPE_NUMBER
    ],
    [
        'alias' => '分成比例',
        'column' => 'divide_rate',
        'type' => Meta::DATA_TYPE_NUMBER
    ],
];

print_r("用户从前端录入公式: \n");

print_r("几日充值=date_diff([时间],[新登时间])\n");
$formulaDateDiff = 'date_diff([时间],[新登时间])';
print_r("单笔利润=[金额]*[分成比例]\n");
$formulaMoney = '[金额]*[分成比例]';

// 开始进行编译
$translator = new Translator();
$translator->setColumns($tableColumns);
$sqlDateDiff = $translator->compile($formulaDateDiff)->translate();
$sqlDateDiffPg = $translator->compile($formulaDateDiff)->translate(Translator::DB_PGSQL);
$sqlMoney = $translator->compile($formulaMoney)->translate();
$sqlMoneyPg = $translator->compile($formulaMoney)->translate(Translator::DB_PGSQL);

print_r("\n发起ltv3查询计划：数值=sum(单笔利润)，维度=账号id，筛选=几日充值<=2\n");
// 用户使用自己定义的公式组合后进行分析
$sql = "select sum({$sqlMoney}) as ltv3, uid from demo_table where {$sqlDateDiff} < 2 group by uid";
print_r(  "执行sql进行查询：$sql\n");
$pgSql = "select sum({$sqlMoneyPg}) as ltv3, uid from demo_table where {$sqlDateDiffPg} < 2 group by uid";
print_r(  "PostgreSQL为：$pgSql\n");
