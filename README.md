# sql-translation
 A pure PHP SQL parser to translate custom sql to MySQL or PostgreSQL
 
 ## 安装
 推荐使用composer进行安装，然后引用vendor/autoload.php即可
 > composer require maatwebsite/excel
 
 ## 问题场景
 1. 在BI系统或者和数据有关的场景下，需要让业务人员通过动态写sql进行查询，可以使用公式、运算符、字段、字符串、数字以及以上各种内容的组合来增强灵活性。
    * 期内充值：如数据为`新登时间:2018-10-01;时间:2018-10-03;金额:100.00`，由业务人员加上动态字段`date_diff(时间,新登时间)`识别每条充值记录为玩家新登第n日充值；
    * 权重分摊：如数据有`自营流水、联运流水、投入`等字段，每天的盈利计算公式为`自营+联运*20%-投入`；
 2. 针对同一个公式，需要保证均能正常翻译成MySQL或者PostgreSQL的执行代码，减少上层业务对下层db的直接依赖。
    * 例如拼接字符串函数：MySQL中为concat(str1, str2), PostgreSQL为(str1 || str2)
 3. 保证良好的安全性，避免通过动态sql直接穿透到代码执行区域，减少恶意的sql注入风险。
 
 ## 使用
 todo 完善使用案例


 ## 实现分析
![avatar](./doc/编译器.jpg)

todo 完善介绍逻辑
1. 分词
2. 解析
3. 生成器