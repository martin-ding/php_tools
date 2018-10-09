<?php

function getDirFileList($dir, $deep = 1, $showDir = false)
{
    static $fileList = [];
    if (is_dir($dir) && $deep >= 1)
    {
        $hd = opendir($dir);
        if ($hd)
        {
            while (($file = readdir($hd)) !== false)
            {
                if ($file != '.' && $file != '..')
                {
                    $file = $dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($file))
                    {
                        $showDir && $fileList[] = $file;
                        $nextDeep = $deep - 1;
                        getDirFileList($file, $nextDeep);
                    } else {
                        $fileList[] = $file;
                    }
                }
            }
            closedir($hd);
        }
    }
    return $fileList;
}

var_dump(getDirFileList('/Applications/AMPPS/www/ashleyknow'));

