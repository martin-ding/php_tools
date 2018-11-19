<?php
/**
 * 图片上传类
 * @author
 */

class UploadPic
{
    public $imgtype;
    public $mimetype;
    public $path;
    public $error;

    public $picname;
    public $pic_u_name;
    public $picwidth;
    public $picheight;

    /**
     * 返回函数
     * @param string $str str
     * @return null
     */
    public function goback($str)
    {
        echo "<script>alert('".$str."');history.go(-1);</script>";
        exit();
    }

    /**
     * //检查文件类型
     * @param string $file str
     * @return null
     */
    public function check($file)
    {
        //if($_FILES[$file]["name"] == "")
        // $this->goback("没有文件要上传");"image/gif",
        $types = array("image/jpeg","image/pjpeg","image/png","image/gif");
        if (!in_array($_FILES[$file]["type"], $types))
        {
            $this->goback("请用jpg/png/gif格式的图片");
        }

        $extension = ['png', 'jpg', 'jpeg', 'gif'];
        $fileExt = strtolower(pathinfo($_FILES[$file]["name"], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $extension))
        {
            $this->goback("文件扩展名异常");
        }

        $this->mimetype = $_FILES[$file]["type"];
        $size = getimagesize($_FILES[$file]['tmp_name']);
        $this->picwidth = $size[0];
        $this->height = $size[1];
        $this->path = $_FILES[$file]["tmp_name"];
        $this->error = $_FILES[$file]["error"];
        $this->getType();
    }

    /**
     * //返回文件扩展名
     * @return null
     */
    public function getType()
    {
        switch($this->mimetype)
        {
            case "image/gif":
                $this->imgtype = ".gif";
                break;
            case "image/jpeg":
                $this->imgtype = ".jpg";
                break;
            case "image/pjpeg":
                $this->imgtype = ".jpg";
                break;
            case "image/png":
                $this->imgtype = ".png";
                break;
        }
    }

    /**
     * //异常
     * @return null
     */
    public function getError()
    {
        switch($this->error)
        {
            case 0:
                break;
            case 1:
                $this->goback("文件大小超过限制，请缩小后再上传");
                break;
            case 2:
                $this->goback("文件大小超过限制，请缩小后再上传");
                break;
            case 3:
                $this->goback("文件上传过程中出错，请稍后再上传");
                break;
            case 4:
                $this->goback("文件上传失败，请重新上传");
                break;
        }
    }

    /**
     * 获取上传的文件
     * @param string $path    //保存路径
     * @param string $file    //上传的名称
     * @param string $custom_file_name    //自定义文件名 不包含扩展名
     * @return null
     */
    public function upfile($path, $file, $custom_file_name = null)
    {
        $this->check($file);
        //$this->goback($this->mimetype);
        if (!$custom_file_name)
        {
            $this->picname = date('YmdHis').rand(0, 9);
        }else{
            $this->picname = $custom_file_name;
        }
        $this->pic_u_name = $this->picname;
        $this->getError();
        if (!is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        move_uploaded_file($this->path, $path.$this->picname.$this->imgtype);

        $this->picname = $this->picname.$this->imgtype;
    }
}
