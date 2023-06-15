<?php



namespace app\admin\service\upload\driver;

use app\admin\service\upload\driver\qnoss\Oss;
use app\admin\service\upload\FileBase;
use app\admin\service\upload\trigger\SaveDb;

/**
 * 七牛云上传
 * Class Qnoss
 * @package EasyAdmin\upload\driver
 */
class Qnoss extends FileBase
{

    /**
     * 重写上传方法
     * @return array|void
     */
    public function save()
    {
        parent::save();
        $upload = Oss::instance($this->uploadConfig)
                     ->save($this->completeFilePath, $this->completeFilePath);
        if ($upload['save'] == true) {
            SaveDb::trigger($this->tableName, [
                'upload_type'   => $this->uploadType,
                'original_name' => $this->file->getOriginalName(),
                'mime_type'     => $this->file->getOriginalMime(),
                'file_ext'      => strtolower($this->file->getOriginalExtension()),
                'url'           => $upload['url'],
                'create_time'   => time(),
            ]);
        }
        $this->rmLocalSave();
        return $upload;
    }

}