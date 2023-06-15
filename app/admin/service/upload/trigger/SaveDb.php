<?php



namespace app\admin\service\upload\trigger;


use think\facade\Db;

/**
 * 保存到数据库
 * Class SaveDb
 * @package EasyAdmin\upload\trigger
 */
class SaveDb
{

    /**
     * 保存上传文件
     * @param $tableName
     * @param $data
     */
    public static function trigger($tableName, $data)
    {
        if (isset($data['original_name'])) {
            $data['original_name'] = htmlspecialchars($data['original_name'], ENT_QUOTES);
        }
        Db::name($tableName)->save($data);
    }

}