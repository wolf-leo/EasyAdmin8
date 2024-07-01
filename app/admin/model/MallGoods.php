<?php

namespace app\admin\model;

use app\common\model\TimeModel;
use think\model\relation\BelongsTo;
use think\model\relation\HasOne;

class MallGoods extends TimeModel
{

    protected $table = "";

    protected $deleteTime = 'delete_time';

    // * +++++++++++++++++++++++++++
    // | 以下两种写法适用于 with 关联
    // * +++++++++++++++++++++++++

    //    public function cate(): BelongsTo
    //    {
    //        return $this->belongsTo('app\admin\model\MallCate', 'cate_id', 'id');
    //    }

    public function cate(): HasOne
    {
        return $this->hasOne(MallCate::class, 'id', 'cate_id');
    }

}