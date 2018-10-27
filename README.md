# sql-translation
 A pure PHP SQL parser to translate custom sql to MySQL or PostgreSQL
 
 ## 安装
 推荐使用composer进行安装，然后引用vendor/autoload.php即可
 > composer require maatwebsite/excel
 
 ## What
 * 将自定义的sql伪码转换成MySQL/PostgreSQL可执行sql代码；
 * 提供字段映射和校验，如传入公式为单字段：`[时间]`,通过转化生成数据表真实字段名`rectime`；
 * 由纯PHP代码组成，可通过composer或者下载引用，直接嵌入到现有的PHP服务中；
 
## Why
 1. 在BI系统或者和数据有关的场景下，需要让业务人员通过动态写sql进行查询，可以使用公式、运算符、字段、字符串、数字以及以上各种内容的组合来增强灵活性。
    * 期内充值：如数据为`新登时间:2018-10-01;时间:2018-10-03;金额:100.00`，由业务人员加上动态字段`date_diff(时间,新登时间)`识别每条充值记录为玩家新登第n日充值；
    * 权重分摊：如数据有`自营流水、联运流水、投入`等字段，每天的盈利计算公式为`自营+联运*20%-投入`；
 2. 针对同一个公式，需要保证均能正常翻译成MySQL或者PostgreSQL的执行代码，减少上层业务对下层db的直接依赖。
    * 例如拼接字符串函数：MySQL中为concat(str1, str2), PostgreSQL为(str1 || str2)
 3. 保证良好的安全性，避免通过动态sql直接穿透到代码执行区域，减少恶意的sql注入风险。
 4. 通过别名映射，如`rectime=>时间`展示给用户，避免用户直接拿到数据库真实字段名，同时还可以屏蔽不想被访问到的字段。

## How
1. 初始化
```
$translator = new Translator();
$columns = [
    [
        'alias' => '新登时间',
        'column' => 'first_login_time',
        'type' => Meta::DATA_TYPE_TIMESTAMP
    ],
    ...
];
// 设置参与编译的自定义字段
$translator->setColumns($columns);
```

2. 传入sql伪码进行编译
```
$input='date_diff([时间],[新登时间])';

// 编译得到:datediff(`retime`, `first_login_time`)
$sqlDateDiff = $translator->compile($input)->translate();
```

具体示例可看[demo.php](https://github.com/grey-zeng/sql-translation/blob/master/example/demo.php)

 ## 其他
 ### 实现分析
该sql转化器的具体的实现分析见：[实现分析](https://github.com/grey-zeng/sql-translation/blob/master/doc/detail.md)