<?php
/**
* @author martin ding
* @version v1
* reference : http://cn2.php.net/manual/zh/class.domdocument.php
* 需求将xml文件转换成一个数组包含 attribute 属性
*
* 说明 使用DOMDocument 类读取xml 文件 如有属性 将属性值保存在 键值是 *'@attribute' 对应的value中
* 比如<node name="nodename">Textnode</node> 将会变成
* 如果出现 <field>text1</field><field>text2</field>
* 将会出现 array('field'=>array(0=>text1,1=>text2))
*/
class ArrXmlExchange{

    public function xml2Array($root) {
        $result = array();
        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }
        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                    ? $result['_value']
                    : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->xml2Array($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->xml2Array($child);
                }
            }
        }

        return $result;
    }

    public function array2Xml($arr,$dom=null,$node=null,$root='',$cdata=false){ 
        $attributes_arr = [];
        if (!$dom){  
            $dom = new DOMDocument('1.0','utf-8');
            $dom->preserveWhiteSpace = false;
        }  
        if(!$node){  
            $node = $dom;
        }

        foreach ($arr as $key=>$value){
            try{
                if(!in_array($key,['@attributes']) || is_numeric($key)){
                    if(is_numeric($key)){ # 如果是数字键就是表示 是<field>text1</field><field>text2</field> 这样的格式
                        #如果是数字格式的按照 父节点创建element
                        $child_node = $dom->createElement(is_string($root) ? $root : 'node');
                    }else{
                        $root = $key;
                        $child_node = $dom->createElement(is_string($key) ? $key : 'node');
                    }
                }else{
                    $attributes_arr = $value;
                    foreach ($attributes_arr as $att_key => $attr_val) {
                        try{
                            $attribute = $dom->createAttribute($att_key);
                            $attribute->value = $attr_val;
                            $node->appendChild($attribute);
                        }catch (Exception $e){
                            var_dump($e->getMessage());
                            exit;
                            //TO DO other error logging
                        }
                    }
                    continue;
                }
            }catch (Exception $e){
                echo $key;
                echo $e->getMessage();
            }

            $node->appendChild($child_node);

            if (!is_array($value)){  
                if (!$cdata) {  
                    $data = $dom->createTextNode($value);  
                }else{  
                    $data = $dom->createCDATASection($value);  
                }  
                $child_node->appendChild($data);  
            }else {  
                $this->array2Xml($value,$dom,$child_node,$root,$cdata);  
            }  
        }
        return $dom->saveXml();  
    }
}

/*使用方法*/

// xml2Array
$xml_file = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<field id="update_fields" name="更新字段列表" type="multiCheck">
<rules>
<rule name="requiredRule" value="true"/>
<rule name="requiredRule" value="true2"/>
</rules>
<options>
<option displayName="商品描述" value="description"/>
</options>
<default-values>
<default-value>description</default-value>
</default-values>
</field>
EOF;
$arrxmlexchange = new ArrXmlExchange();
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false; # 去掉xml 格式化里面的一些空格
$dom->loadXML($xml_file);
$arr = $arrxmlexchange->xml2Array($dom);
// file_put_contents("arrxmlexchange_demo.txt",print_r($arr,true)); #方便查看 保存在txt 文件


//array2Xml 直接将上面一步获得的$arr 转换成xml 文件
$xmlafter = $arrxmlexchange->array2Xml($arr);
// file_put_contents("arrxmlexchange_demo.xml",print_r($xmlafter,true)); #方便查看 保存在xml 文件


/*
*说明
* array2Xml 方法中生成的没有content的标签会是 <rule name="requiredRule" value="true2"></rule> 
* 格式的 不会是<rule name="requiredRule" value="true2"/> 含义是一样的
*/



