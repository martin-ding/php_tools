reference : http://cn2.php.net/manual/zh/class.domdocument.php

需求将xml文件转换成一个数组 其中xml包含属性

### 说明 

使用DOMDocument 类读取xml 文件 如有属性 将属性值保存在 键值是'@attribute' 对应的value

比如`<node name="nodename">Textnode</node>` 将会变成
```
array
(
    "node" => array
        (
            "@attributes" => array
                (
                    "name" => "nodename"
                )
            "_value" => "Textnode"
        )
)
```

比如 `<fields><field>text1</field><field>text2</field></fields>`

将会出现 
```
Array
(
    [fields] => Array
        (
            [field] => Array
                (
                    [0] => text1
                    [1] => text2
                )
        )
)
```

arrxmlexchange_demo.txt 和 arrxmlexchange_demo.xml 是测试生成的文件

如果想要更加定制化的 xml 可以参考 http://cn2.php.net/manual/zh/class.domdocument.php 文档自行修改

:)