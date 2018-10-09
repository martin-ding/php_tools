<?php

function lastServalLineOfFile($file, $line)
{
    $arrLine = [];
    $pos = -1;
    $eof = "";
    if ($fp = fopen($file, "r"))
    {
        while ($line > 0) {

            while (fgetc($fp) != "\n")
            {
                if (fseek($fp, $pos, SEEK_END) === 0)
                {
                    $pos--;
                } else {
                    break;
                }
            }

            if (ftell($fp) == 1) //到开始了
            {
                rewind($fp);
                $arrLine[] = trim(fgets($fp));
                break;
            }
            $arrLine[] =  trim(fgets($fp));

            $line --;
        }
        fclose($fp);
    }
    return $arrLine;
}


var_dump(lastLineOfFile("cookie.txt",10));
