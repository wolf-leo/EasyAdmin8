<?php



namespace app\admin\service\upload\interfaces;

interface OssDriver
{

    public function save($objectName,$filePath);

}