<?php

class TaobaoGoodsDescriotionAPI {

    protected $appkey;
    protected $secret;
    protected $sessionKey;
    protected $header;
    protected $url;

    public function __construct() {
        $this->url = 'http://gw.api.taobao.com/router/rest';
        $this->appkey = 'xxxx';
        $this->secret = 'xxxxx';
        $this->sessionKey = 'xxxxx';
        $this->header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'charset'      => 'utf-8',
        );
    }

    private function curl($url, $header, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_USERAGENT, "top-sdk-php");
        $content = curl_exec($ch);
        $resp_info = curl_getinfo($ch);
        curl_close($ch);
        return $content;
    }

    private function generateSign($params, $secret) {
        ksort($params);

        $stringToBeSigned = $secret;
        foreach ($params as $k => $v) {
            if (is_string($v) && "@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $secret;

        return strtoupper(md5($stringToBeSigned));
    }

   /**
    * 参考文档地址 https://open.taobao.com/docs/doc.htm?spm=a219a.7386797.0.0.nXFwfq&source=search&treeId=1&articleId=102602&docType=1
    * 使用schema 接口更新产品详情信息 比如 title desription 
    * tmall.item.increment.update.schema.get 获取xml
    * @param string $num_iid 商品的唯一标识符
    * @param $type 为 title 或者 desription 等
    */
    public function tmall_item_increment_update_schema_get($num_iid,$type = "description") {
    
        $params = array(
            'method'      => 'tmall.item.increment.update.schema.get',
            'app_key'     => $this->appkey,
            'session'     => $this->sessionKey,
            'format'      => 'xml',
            'timestamp'   => date("Y-m-d H:i:s"),
            'v'           => '2.0',
            'sign_method' => 'md5',
            'item_id'     => $num_iid,
        );
        if($type){
            $params['xml_data'] = '<?xml version="1.0" encoding="UTF-8"?><itemParam><field id="update_fields" name="更新字段列表" type="multiCheck"><values><value>'.$type.'</value></values></field></itemParam>';
        }

        $sign = $this->generateSign($params, $this->secret);
        $params['sign'] = $sign;
        $xml = $this->curl($this->url, $this->header, $params);
        $xml = html_entity_decode($xml);
        return $xml;
    }

    /**
     * xml 转数组
     */
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


    /**
     * 处理从 tmall.item.increment.update.schema.get 获取的xml
     * 步骤
     * 1. 将xml保存到当前所在文件的 goods_description 目录
     * 2. 将xml 转换成数组 保存到 xml 相同目录 同名文件
     * 3. 根据获得的xml 数组获得对应的module 所在的位置 中文名称 和 内容 
     *
     * 4. 返回 整理好的数据 为二维数组
     *    键为module 的 id 
     *    值有 name (名称),content (内容),order (在页面的排序位置)
     * 
     * @param  string $num_iid 产品的唯一id
    */
   
    public function handle_item_desc($num_iid)
    {
        /*获取 xml 保存到本地*/
        $xml = $this->tmall_item_increment_update_schema_get($num_iid);

        $store_path = dirname(__FILE__)."/goods_description"; # xml 文件保存目录
        if (! is_dir($store_path)) {
            mkdir($store_path);
        }
        file_put_contents($store_path."/{$num_iid}.xml",print_r($xml,true));

        /*处理xml 文件*/
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false; # 去掉xml 格式化里面的一些空格
        $dom->loadXML($xml);
        $content_dom = $dom->getElementsByTagName('itemRule');
        
        /*将xml转成arr 存储在同名文件中*/
        $arr = $this->xml2Array($content_dom->item(0));
        file_put_contents($store_path."/{$num_iid}.txt",print_r($arr,true));

        /*获取产品详情页的细节信息*/
        $item_desc_collect = [];    # 用来存 id 对应的内容
        $item_desc_collect_fields = []; # 用来存id对应的名称

        /*获取当前产品详情页拥有的模块信息 数组*/
        if(isset($arr['field'][2])) {
            $item_desc_modules = $arr['field'][2];
            if (isset($item_desc_modules['default-complex-values'])) {
                foreach ($item_desc_modules['default-complex-values']['field'] as $item_desc) {
                    
                    $field_desc = [];

                    if(isset($item_desc['complex-values']['field'])) {
                        
                        foreach ($item_desc['complex-values']['field'] as $description) {
                            if(strpos($description['@attributes']['id'],"_content")){
                                $field_desc["content"] = $description['value'];
                            }

                            if(strpos($description['@attributes']['id'],"_order")){
                                 $field_desc["order"] = $description['value'];
                            }
                        }
                    }

                    $item_desc_collect[$item_desc['@attributes']['id']] =  $field_desc;
                }
            }

            /*得到所有id对应的标签 名称*/
            if (isset($item_desc_modules['fields']['field'])) {
                foreach ($item_desc_modules['fields']['field'] as $field) {
                    $item_desc_collect_fields[$field['@attributes']['id']]["name"] = $field['@attributes']['name'];
                }
            }

            /*整合上面得到的两个数组 得到最终的数据*/
            foreach ($item_desc_collect as $key => $item_desc) {
                $item_desc_collect[$key]["name"] =  $item_desc_collect_fields[$key]['name'];
            }
            
            usort($item_desc_collect, function($a,$b){
                return $a['order'] - $b['order'];
            });

            return $item_desc_collect;
        }
    }
}

/*测试*/

$num_iids = ["554829627975", "521387425254", "562455130024"];
$good_descripton = new TaobaoGoodsDescriotionAPI();

foreach ($num_iids as $num_iid) {
    $field_desc = $good_descripton->handle_item_desc($num_iid);
    file_put_contents(dirname(__FILE__)."/goods_description/{$num_iid}_desc.txt", print_r($field_desc, true));
}







