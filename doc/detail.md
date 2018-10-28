## 实现分析

### 流程图
![流程图](./编译器.jpg)

### 步骤分析
#### 1. 分词

使用正则进行分词：
```
使用正则表达式来做nfa匹配
    const REG_EMPTY     = '/^([\s,]+)/';                                    // 空值或者逗号
    const REG_STATEMENT = '/^(while|declare)(\s)?\(/';                      // 预定义关键字，目前支持while和declare
    const REG_FUNCTION  = '/^(\w+)(\s)?\(/';                                // 函数，类似funcName(
    const REG_BRACKET   = '/^(\(|\))/';                                     // 左右括号
    const REG_COLUMN    = '/^(\[([\x{4e00}-\x{9fa5}a-zA-Z0-9_\-]+)\])/u';   // 匹配使用[]包含的自定义字段，需要使用Unicode解析中文
    const REG_NUMBER    = '/^((-?\d+)(\.\d+){0,1})/';                       // 正负整型及浮点数
    const REG_STRING    = '/^((\'|\")([\s\S]*?)(\2))/';                     // 使用""或者''闭合的字符串，需要非贪婪匹配
    const REG_OPERATOR  = '/^(\+|\-|\*|\/|(>|<)(=)?|=|\&+|\|+)/';           // 匹配操作符
    const REG_BRACE     = '/^({|})/';                                       // 匹配花括号
    const REG_VARIABLE  = '/^(@\w+)/';                                      // 运行时变量，如mysql的`select @num:=1`,其中的@num

$tokenList = [];
while (!empyt($str)) {
    foreach (REG_LIST as REG) {
        if preg_match(REG, $str) {
            $tokenList = 捕获的内容
            $str = substr($str, len(命中的内容)); // 向右遍历
        }
    }
}
```
得到tokenList：
```
这里的tokenList为{type:类型,val:值}组成的数组，将字符聚合成最小解释单元（空格和逗号将不记录）。
类型有：
    const TYPE_BRACKET_LEFT     = '(';          // 左括号
    const TYPE_BRACKET_RIGHT    = ')';          // 右括号
    const TYPE_COLUMN           = 'column';     // 使用[]标识的字段，比如[新登时间]
    const TYPE_NUMBER           = 'number';     // 数字，比如100、90.12
    const TYPE_STRING           = 'string';     // 使用单引号或者双引号标识的内容，比如'字符串1'
    const TYPE_OPERATOR         = 'operator';   // 运算符，比如+-*\或者><=等
    const TYPE_FUNCTION         = 'function';   // 以一连串字母和下划线组成的，带(标识的内容，比如`abs(...`将识别成abs和(两个token
    const TYPE_BRACE_LEFT       = '{';          // 左花括号
    const TYPE_BRACE_RIGHT      = '}';          // 右花括号
    const TYPE_VARIABLE         = '@';          // 变量
    // 以下用于解析复合语句
    const TYPE_ROOT             = 'root';
    const TYPE_BRACKETS         = 'brackets'; 
    const TYPE_EXPRESSION       = 'expr';  
    const TYPE_STATEMENT        = 'statement';  // 关键字,用于循环、分支、声明等操作

```

#### 2. 解析

语法分析部分，使用从底向上的思路，定义S为原语，同时可以通过传入运算符、函数、括号、语句声明生成4种状态的复合S。
![状态](./LR过程.jpg)

```
// 由于存在类似S -> S oper S的左递归生成式，LL无法使用，而且LR暂时无法手写出来，改成了手动代码进行状态转移。
$tree = new Token(['type' => root]);
foreach ($tokenList as $token) {
    switch($token->type) {
        case 左括号
            if tree的末尾是函数 then 替换tree为函数节点进入I2
            else if tree的末尾是关键字 then 标识为代码块进入I4
            else 标识为括号表达式，进入I3
        case 右括号
            逐层判断父节点是否完毕，进行上升
        case 运算符
            标识为计算表达式，进入I1
        默认
            生成token并把token加到当前节点的子节点列表中
    }
}
```

示例
```
输入公式为：if(date_diff([时间],[新登时间])>3, "后期", "前期")
对应ast为
                       root
                         ↓
                      if:func
                         ↓
                  expr, 后期:str, 前期:str
                    ↓
   date_diff:func, >:oper, 3:num
       ↓
时间:col，新登时间:col

```

#### 3. 生成器

基础流程
```
function generator($token) {
    switch($token->type) {
        case 根节点 then 返回generator(token->child)
        case 函数 then 
            params=generator(token->child)      // dfs解析参数
            token->checkParam()                 // 进行函数参数校验
            返回translatFunciton(token, params)  // 不同的db不同处理
        case 计算表达式 then 
        caes 自定义字段 then 返回真实物理字段，没有则报错
        case 字符串/数字 then 返回内容
        case 变量 then 返回转码后的变量别名，防止被攻击
        case 代码块 then                        // 思路为根据不同的关键字定义不同的生成规则
            switch 关键字
                case while 实现了循环
                case declare 实现了变量声明和赋值
    }
}
```

针对不同db生成不同的函数目标码

![生成器逻辑](./生成器逻辑.jpg)
```
/**
 * @param string $funcName 函数名
 * @param array $params [[val=>参数sql, type=>数据类型], ... ]
 */
function translatFunciton($funcName, $parmas) {
    // 显示指定函数对应的转化规则
    switch ($funcName) {
        case 函数名: 返回`函数名(预定义参数, 参数sql1, 参数sql2, ...)` 
        case 其他: 返回自定义规则，比如携带自定义参数、改写函数名等
    }
}
```