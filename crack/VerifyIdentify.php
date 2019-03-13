<?php
class VerifyIdentify
{

    private $imagePath,$imageType,$pixelRGB;

    //图片句柄
    private $imageHandle , $imageWidth , $imageHeight;

    //灰度化图片地址
    private $grayImagePath , $grayImageWidth , $grayImageHeight;

    //灰度化图片句柄
    private static $isDoBinaryImage = false;

    //灰度化像素 & RGB
    private $grayPixel = [] , $grayRGB = [],$grayPixel_hw = [],$grayPixel_wh = [];
    
    private $grayImageHandle;

    public function __construct($imagePath = '' , $imageType = 'jpg' , $RGB = 200)
    {
        $this->imagePath = $imagePath;
        $this->imageType = $imageType;
        $this->pixelRGB  = 50;

        //为图片生成唯一Hash
        $this->imageHash = md5(filesize($this->imagePath).$imagePath.$imageType);
    }

    public function run($printBinary = false)
    {
        //二值化图片
        $this->doBinaryImage();

        //是否打印二值化信息
        if($printBinary)
        {
            $binary = '';
            foreach ($this->grayPixel as $v) $binary .= implode('',$v)."\n";
            echo $binary; die();
        }
    }
    
    private function doBinaryImage(){
        if(!self::$isDoBinaryImage){
            $this->binaryImage();
            self::$isDoBinaryImage = true;
        }
    }


    public function getY()
    {
        //二值化图片
        $this->doBinaryImage();

        
        foreach ($this->grayPixel_hw as $k=> $v){
            $p = [];
            foreach ($v as $v2) {
                $p[] = $v2 == 0 ? 1 : 0;
            }
            if(array_sum($p) >= 3){
                return $k;
            }

        }
         
    }
    
    public function getX()
    {
        //二值化图片
        $this->doBinaryImage();

        foreach ($this->grayPixel_wh as $k=> $v){
            $p = [];
            foreach ($v as $v2) {
                $p[] = $v2 == 0 ? 1 : 0;
            }
            if(array_sum($p) >= 3){
                return $k;
            }
        }
        
    }

    //图片灰度化处理
    private function grayImage()
    {
        //得到图片句柄
        $this->imageHandle = $this->imageElement();

        //得到图片宽高
        $this->imageWidth = imagesx($this->imageHandle);
        $this->imageHeight= imagesy($this->imageHandle);

        //循环像素点
        for($h = 0 ; $h < $this->imageHeight ; $h++)
        {
            $this->grayRGB[$h] = [];
            for($w = 0 ; $w < $this->imageWidth ; $w++)
            {
                //获取当前像素点RGB信息
                $imageRGB = imagecolorat($this->imageHandle,$w,$h);
                $R = ($imageRGB >> 16) & 0xFF;
                $G = ($imageRGB >> 8) & 0xFF;
                $B = $imageRGB & 0xFF;

                //灰度化处理
                $imageGray  = round(($R+$G+$B)/3);
                $imageColor = imagecolorallocate($this->imageHandle,$imageGray,$imageGray,$imageGray);
                imagesetpixel($this->imageHandle,$w,$h,$imageColor);
                $this->grayRGB[$h][] = $imageGray;
            }
        }

        //灰度化图片保存地址
        $this->grayImagePath = str_replace(basename($this->imagePath),'',$this->imagePath)."gray_{$this->imageHash}.{$this->imageType}";

        //保存图片
        $this->imageElement('save',$this->grayImagePath);
    }

    //图片二值化
    private function binaryImage($prototype = true)
    {

        if(!$prototype)
            //图片灰度处理
            $this->grayImage();

        $dealImagePath = $prototype ? $this->imagePath:$this->grayImagePath;

        //得到灰度化图片句柄
        $this->grayImageHandle = $this->imageElement('handle',$dealImagePath);

        //得到灰度化图标元素
        $this->grayImageWidth  = imagesx($this->grayImageHandle);
        $this->grayImageHeight = imagesy($this->grayImageHandle);

        // 遍历所有像素点
        for ($h = 0; $h < $this->grayImageHeight; $h++) {
            $binaryDots[$h] = [];
            for ($w = 0; $w < $this->grayImageWidth; $w++) {
                $pixelRGB = imagecolorsforindex($this->grayImageHandle,imagecolorat($this->grayImageHandle,$w,$h));

                // 对颜色值过滤 进行二值化
                $this->grayPixel[$h][] = (int) ($pixelRGB['red'] < $this->pixelRGB || $pixelRGB['green'] < $this->pixelRGB || $pixelRGB['blue'] < $this->pixelRGB);
                $this->grayPixel_hw[$h][] = (int) ($pixelRGB['red'] < $this->pixelRGB || $pixelRGB['green'] < $this->pixelRGB || $pixelRGB['blue'] < $this->pixelRGB);  
            }
        }
        
        for ($w = 0; $w < $this->grayImageWidth; $w++) {
            for ($h = 0; $h < $this->grayImageHeight; $h++) {
                $pixelRGB = imagecolorsforindex($this->grayImageHandle,imagecolorat($this->grayImageHandle,$w,$h));
                $this->grayPixel_wh[$w][] = (int) ($pixelRGB['red'] < $this->pixelRGB || $pixelRGB['green'] < $this->pixelRGB || $pixelRGB['blue'] < $this->pixelRGB);  
            }
        }
    }
    
    

    //获取图片元素
    private function imageElement($type = 'handle' , $param = '')
    {
        switch ($type)
        {
            case 'handle':

                switch ($this->imageType)
                {
                    case 'jpg':
                    case 'jpeg':
                        $res = imagecreatefromjpeg($param?:$this->imagePath);
                        break;
                    default:
                        $res = imagecreatefrompng($param?:$this->imagePath);
                }

                break;

            case 'save':

                switch ($this->imageType)
                {
                    case 'jpg':
                    case 'jpeg':
                        $res = imagejpeg($this->imageHandle,$param);
                        break;
                    default:
                        $res = imagepng($this->imageHandle,$param);
                        break;
                }

                break;

            default:
                $res = '';

        }
        return $res;
    }
}
?>