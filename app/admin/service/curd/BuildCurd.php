<?php

namespace app\admin\service\curd;

use app\admin\service\curd\exceptions\TableException;
use app\admin\service\tool\CommonTool;
use Exception;
use think\exception\FileException;
use think\facade\Db;

/**
 * 快速构建系统CURD
 * Class BuildCurd
 * @package EasyAdmin\curd
 */
class BuildCurd
{

    /**
     * 当前目录
     * @var string
     */
    protected string $dir;

    /**
     * 应用目录
     * @var string
     */
    protected string $rootDir;

    /**
     * 分隔符
     * @var string
     */
    protected string $DS = DIRECTORY_SEPARATOR;

    /**
     * 数据库名
     * @var string
     */
    protected mixed $dbName;

    /**
     *  表前缀
     * @var string
     */
    protected mixed $tablePrefix = 'ea';

    /**
     * 主表
     * @var string
     */
    protected string $table;

    /**
     * 表注释名
     * @var string
     */
    protected string $tableComment;

    /**
     * 主表列信息
     * @var array
     */
    protected array $tableColumns;

    /**
     * 数据列表可见字段
     * @var string
     */
    protected string $fields;

    /**
     * 是否软删除模式
     * @var bool
     */
    protected bool $delete = false;

    /**
     * 是否强制覆盖
     * @var bool
     */
    protected bool $force = false;

    /**
     * 关联模型
     * @var array
     */
    protected array $relationArray = [];

    /**
     * 控制器对应的URL
     * @var string
     */
    protected string $controllerUrl;

    /**
     * 生成的控制器名
     * @var string
     */
    protected string $controllerFilename;


    /**
     * 控制器命名
     * @var string
     */
    protected string $controllerName;

    /**
     * 控制器命名空间
     * @var string
     */
    protected string $controllerNamespace;

    /**
     * 视图名
     * @var string
     */
    protected string $viewFilename;

    /**
     * js文件名
     * @var string
     */
    protected string $jsFilename;

    /**
     * 生成的模型文件名
     * @var string
     */
    protected string $modelFilename;

    /**
     * 主表模型命名
     * @var string
     */
    protected string $modelName;

    /**
     * 复选框字段后缀
     * @var array
     */
    protected array $checkboxFieldSuffix = [];

    /**
     * 单选框字段后缀
     * @var array
     */
    protected array $radioFieldSuffix = [];

    /**
     * 单图片字段后缀
     * @var array
     */
    protected array $imageFieldSuffix = ['image', 'logo', 'photo', 'icon'];

    /**
     * 多图片字段后缀
     * @var array
     */
    protected array $imagesFieldSuffix = ['images', 'photos', 'icons'];

    /**
     * 单文件字段后缀
     * @var array
     */
    protected array $fileFieldSuffix = ['file'];

    /**
     * 多文件字段后缀
     * @var array
     */
    protected array $filesFieldSuffix = ['files'];

    /**
     * 时间字段后缀
     * @var array
     */
    protected array $dateFieldSuffix = ['time', 'date'];

    /**
     * 开关组件字段
     * @var array
     */
    protected array $switchFields = ['status'];

    /**
     * 下拉选择字段
     * @var array
     */
    protected array $selectFields = [];

    /**
     * 富文本字段
     * @var array
     */
    protected array $editorFields = [];

    /**
     * 排序字段
     * @var array
     */
    protected array $sortFields = [];

    /**
     * 忽略字段
     * @var array
     */
    protected array $ignoreFields = ['update_time', 'delete_time'];

    /**
     * 外键字段
     * @var array
     */
    protected array $foreignKeyFields = [];

    /**
     * 相关生成文件
     * @var array
     */
    protected array $fileList = [];

    /**
     * 表单类型
     * @var array
     */
    protected array $formTypeArray = ['text', 'image', 'images', 'file', 'files', 'select', 'switch', 'date', 'editor', 'textarea', 'checkbox', 'radio'];

    /**
     * 初始化
     * BuildCurd constructor.
     */
    public function __construct()
    {
        $this->tablePrefix = config('database.connections.mysql.prefix');
        $this->dbName      = config('database.connections.mysql.database');
        $this->dir         = __DIR__;
        $this->rootDir     = root_path();
        return $this;
    }

    public function setTablePrefix($prefix): static
    {
        $this->tablePrefix = $prefix;
        return $this;
    }

    /**
     * 设置主表
     * @param $table
     * @return $this
     * @throws TableException
     */
    public function setTable($table): static
    {
        $this->table = $table;
        try {

            // 获取表列注释
            $columns = Db::query("SHOW FULL COLUMNS FROM {$this->tablePrefix}{$this->table}");
            foreach ($columns as $vo) {
                $colum = [
                    'type'     => $vo['Type'],
                    'comment'  => !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'],
                    'required' => $vo['Null'] == "NO",
                    'default'  => $vo['Default'],
                ];

                // 格式化列数据
                $this->buildColum($colum);

                $this->tableColumns[$vo['Field']] = $colum;

                if ($vo['Field'] == 'delete_time') {
                    $this->delete = true;
                }
            }
            $this->tableComment = $this->table;
        }catch (Exception $e) {
            throw new TableException($e->getMessage());
        }

        // 初始化默认控制器名
        $nodeArray = explode('_', $this->table);
        if (count($nodeArray) == 1) {
            $this->controllerFilename = ucfirst($nodeArray[0]);
        }else {
            foreach ($nodeArray as $k => $v) {
                if ($k == 0) {
                    $this->controllerFilename = "{$v}{$this->DS}";
                }else {
                    $this->controllerFilename .= ucfirst($v);
                }
            }
        }

        // 初始化默认模型名
        $this->modelFilename = ucfirst(CommonTool::lineToHump($this->table));

        $this->buildViewJsUrl();

        // 构建数据
        $this->buildStructure();

        return $this;
    }

    /**
     * 设置关联表
     * @param $relationTable
     * @param $foreignKey
     * @param null $primaryKey
     * @param null $modelFilename
     * @param array $onlyShowFields
     * @param null $bindSelectField
     * @return $this
     * @throws TableException
     */
    public function setRelation($relationTable, $foreignKey, $primaryKey = null, $modelFilename = null, array $onlyShowFields = [], $bindSelectField = null): static
    {
        if (!isset($this->tableColumns[$foreignKey])) {
            throw new TableException("主表不存在外键字段：{$foreignKey}");
        }
        if (!empty($modelFilename)) {
            $modelFilename = str_replace('/', $this->DS, $modelFilename);
        }
        try {
            $columns       = Db::query("SHOW FULL COLUMNS FROM {$this->tablePrefix}{$relationTable}");
            $formatColumns = [];
            $delete       = false;
            if (!empty($bindSelectField) && !in_array($bindSelectField, array_column($columns, 'Field'))) {
                throw new TableException("关联表{$relationTable}不存在该字段: {$bindSelectField}");
            }
            foreach ($columns as $vo) {
                if (empty($primaryKey) && $vo['Key'] == 'PRI') {
                    $primaryKey = $vo['Field'];
                }
                if (!empty($onlyShowFields) && !in_array($vo['Field'], $onlyShowFields)) {
                    continue;
                }
                $colum = [
                    'type'    => $vo['Type'],
                    'comment' => $vo['Comment'],
                    'default' => $vo['Default'],
                ];

                $this->buildColum($colum);

                $formatColumns[$vo['Field']] = $colum;
                if ($vo['Field'] == 'delete_time') {
                    $delete = true;
                }
            }

            $modelFilename = empty($modelFilename) ? ucfirst(CommonTool::lineToHump($relationTable)) : $modelFilename;
            $modelArray    = explode($this->DS, $modelFilename);
            $modelName     = array_pop($modelArray);

            $relation = [
                'modelFilename'   => $modelFilename,
                'modelName'       => $modelName,
                'foreignKey'      => $foreignKey,
                'primaryKey'      => $primaryKey,
                'bindSelectField' => $bindSelectField,
                'delete'          => $delete,
                'tableColumns'    => $formatColumns,
            ];
            if (!empty($bindSelectField)) {
                $relationArray                                      = explode('\\', $modelFilename);
                $this->tableColumns[$foreignKey]['bindSelectField'] = $bindSelectField;
                $this->tableColumns[$foreignKey]['bindRelation']    = end($relationArray);
            }
            $this->relationArray[$relationTable] = $relation;
            $this->selectFields[]                = $foreignKey;
        }catch (Exception $e) {
            throw new TableException($e->getMessage());
        }
        return $this;
    }

    /**
     * 设置控制器名
     * @param $controllerFilename
     * @return $this
     */
    public function setControllerFilename($controllerFilename): static
    {
        $this->controllerFilename = str_replace('/', $this->DS, $controllerFilename);
        $this->buildViewJsUrl();
        return $this;
    }

    /**
     * 设置模型名
     * @param $modelFilename
     * @return $this
     */
    public function setModelFilename($modelFilename): static
    {
        $this->modelFilename = str_replace('/', $this->DS, $modelFilename);
        $this->buildViewJsUrl();
        return $this;
    }

    /**
     * 设置显示字段
     * @param $fields
     * @return $this
     */
    public function setFields($fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 设置删除模式
     * @param $delete
     * @return $this
     */
    public function setDelete($delete): static
    {
        $this->delete = $delete;
        return $this;
    }

    /**
     * 设置是否强制替换
     * @param $force
     * @return $this
     */
    public function setForce($force): static
    {
        $this->force = $force;
        return $this;
    }

    /**
     * 设置复选框字段后缀
     * @param $array
     * @return $this
     */
    public function setCheckboxFieldSuffix($array): static
    {
        $this->checkboxFieldSuffix = array_merge($this->checkboxFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置单选框字段后缀
     * @param $array
     * @return $this
     */
    public function setRadioFieldSuffix($array): static
    {
        $this->radioFieldSuffix = array_merge($this->radioFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置单图片字段后缀
     * @param $array
     * @return $this
     */
    public function setImageFieldSuffix($array): static
    {
        $this->imageFieldSuffix = array_merge($this->imageFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置多图片字段后缀
     * @param $array
     * @return $this
     */
    public function setImagesFieldSuffix($array): static
    {
        $this->imagesFieldSuffix = array_merge($this->imagesFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置单文件字段后缀
     * @param $array
     * @return $this
     */
    public function setFileFieldSuffix($array): static
    {
        $this->fileFieldSuffix = array_merge($this->fileFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置多文件字段后缀
     * @param $array
     * @return $this
     */
    public function setFilesFieldSuffix($array): static
    {
        $this->filesFieldSuffix = array_merge($this->filesFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置时间字段后缀
     * @param $array
     * @return $this
     */
    public function setDateFieldSuffix($array): static
    {
        $this->dateFieldSuffix = array_merge($this->dateFieldSuffix, $array);
        return $this;
    }

    /**
     * 设置开关字段
     * @param $array
     * @return $this
     */
    public function setSwitchFields($array): static
    {
        $this->switchFields = array_merge($this->switchFields, $array);
        return $this;
    }

    /**
     * 设置下拉选择字段
     * @param $array
     * @return $this
     */
    public function setSelectFields($array): static
    {
        $this->selectFields = array_merge($this->selectFields, $array);
        return $this;
    }

    /**
     * 设置排序字段
     * @param $array
     * @return $this
     */
    public function setSortFields($array): static
    {
        $this->sortFields = array_merge($this->sortFields, $array);
        return $this;
    }

    /**
     * 设置忽略字段
     * @param $array
     * @return $this
     */
    public function setIgnoreFields($array): static
    {
        $this->ignoreFields = array_merge($this->ignoreFields, $array);
        return $this;
    }

    /**
     * 获取相关的文件
     * @return array
     */
    public function getFileList(): array
    {
        return $this->fileList;
    }

    /**
     * 构建基础视图、JS、URL
     * @return $this
     */
    protected function buildViewJsUrl(): static
    {
        $nodeArray   = explode($this->DS, $this->controllerFilename);
        $formatArray = [];
        foreach ($nodeArray as $vo) {
            $formatArray[] = CommonTool::humpToLine(lcfirst($vo));
        }
        $this->controllerUrl = implode('.', $formatArray);
        $this->viewFilename  = implode($this->DS, $formatArray);
        $this->jsFilename    = $this->viewFilename;

        // 控制器命名空间
        $namespaceArray            = $nodeArray;
        $this->controllerName      = array_pop($namespaceArray);
        $namespaceSuffix           = implode('\\', $namespaceArray);
        $this->controllerNamespace = empty($namespaceSuffix) ? "app\admin\controller" : "app\admin\controller\\{$namespaceSuffix}";

        // 主表模型命名
        $modelArray = explode($this->DS, $this->modelFilename);

        $this->modelName = array_pop($modelArray);

        return $this;
    }

    /**
     * 构建字段
     * @return $this
     */
    protected function buildStructure(): static
    {
        foreach ($this->tableColumns as $key => $val) {

            // 排序
            if ($key == 'sort') {
                $this->sortFields[] = $key;
            }

            // 富文本
            if (in_array($key, ['describe', 'content', 'details'])) {
                $this->editorFields[] = $key;
            }

        }
        return $this;
    }

    /**
     * 构建必填
     * @param $require
     * @return string
     */
    protected function buildRequiredHtml($require): string
    {
        return $require ? 'lay-verify="required"' : "";
    }

    /**
     * 构建初始化字段信息
     * @param $colum
     * @return array
     */
    protected function buildColum(&$colum): array
    {

        $string = $colum['comment'];

        // 处理定义类型
        preg_match('/{[\s\S]*?}/i', $string, $formTypeMatch);
        if (!empty($formTypeMatch) && isset($formTypeMatch[0])) {
            $colum['comment'] = str_replace($formTypeMatch[0], '', $colum['comment']);
            $formType         = trim(str_replace('}', '', str_replace('{', '', $formTypeMatch[0])));
            if (in_array($formType, $this->formTypeArray)) {
                $colum['formType'] = $formType;
            }
        }

        // 处理默认定义
        preg_match('/\([\s\S]*?\)/i', $string, $defineMatch);
        if (!empty($formTypeMatch) && isset($defineMatch[0])) {
            $colum['comment'] = str_replace($defineMatch[0], '', $colum['comment']);
            if (isset($colum['formType']) && in_array($colum['formType'], ['images', 'files', 'select', 'switch', 'radio', 'checkbox', 'date'])) {
                $define = str_replace(')', '', str_replace('(', '', $defineMatch[0]));
                if (in_array($colum['formType'], ['select', 'switch', 'radio', 'checkbox'])) {
                    $formatDefine = [];
                    $explodeArray = explode(',', $define);
                    foreach ($explodeArray as $vo) {
                        $voExplodeArray = explode(':', $vo);
                        if (count($voExplodeArray) == 2) {
                            $formatDefine[trim($voExplodeArray[0])] = trim($voExplodeArray[1]);
                        }
                    }
                    !empty($formatDefine) && $colum['define'] = $formatDefine;
                }else {
                    $colum['define'] = $define;
                }
            }
        }

        $colum['comment'] = trim($colum['comment']);

        return $colum;
    }

    /**
     * 构建下拉控制器
     * @param $field
     * @return mixed
     */
    protected function buildSelectController($field): mixed
    {
        $field      = CommonTool::lineToHump(ucfirst($field));
        $name       = "get{$field}List";
        $selectCode = CommonTool::replaceTemplate(
            $this->getTemplate("controller{$this->DS}select"),
            [
                'name' => $name,
            ]);
        return $selectCode;
    }

    /**
     * 构架下拉模型
     * @param $field
     * @param $array
     * @return mixed
     */
    protected function buildSelectModel($field, $array): mixed
    {
        $field  = CommonTool::lineToHump(ucfirst($field));
        $name   = "get{$field}List";
        $values = '[';
        foreach ($array as $k => $v) {
            $values .= "'{$k}'=>'{$v}',";
        }
        $values     .= ']';
        $selectCode = CommonTool::replaceTemplate(
            $this->getTemplate("model{$this->DS}select"),
            [
                'name'   => $name,
                'values' => $values,
            ]);
        return $selectCode;
    }

    /**
     * 构架关联下拉模型
     * @param $relation
     * @param $filed
     * @return mixed
     */
    protected function buildRelationSelectModel($relation, $filed): mixed
    {
        $relationArray = explode('\\', $relation);
        $name          = end($relationArray);
        $name          = "get{$name}List";
        $selectCode    = CommonTool::replaceTemplate(
            $this->getTemplate("model{$this->DS}relationSelect"),
            [
                'name'     => $name,
                'relation' => $relation,
                'values'   => $filed,
            ]);
        return $selectCode;
    }

    /**
     * 构建下拉框视图
     * @param $field
     * @param string $select
     * @return mixed
     */
    protected function buildOptionView($field, string $select = '')
    {
        $field      = CommonTool::lineToHump(ucfirst($field));
        $name       = "get{$field}List";
        return CommonTool::replaceTemplate(
            $this->getTemplate("view{$this->DS}module{$this->DS}option"),
            [
                'name'   => $name,
                'select' => $select,
            ]);
    }

    /**
     * 构建单选框视图
     * @param $field
     * @param string $select
     * @return mixed
     */
    protected function buildRadioView($field, string $select = ''): mixed
    {
        $formatField = CommonTool::lineToHump(ucfirst($field));
        $name        = "get{$formatField}List";
        return CommonTool::replaceTemplate(
            $this->getTemplate("view{$this->DS}module{$this->DS}radioInput"),
            [
                'field'  => $field,
                'name'   => $name,
                'select' => $select,
            ]);
    }

    /**
     * 构建多选框视图
     * @param $field
     * @param string $select
     * @return mixed
     */
    protected function buildCheckboxView($field, string $select = ''): mixed
    {
        $formatField = CommonTool::lineToHump(ucfirst($field));
        $name        = "get{$formatField}List";
        return CommonTool::replaceTemplate(
            $this->getTemplate("view{$this->DS}module{$this->DS}checkboxInput"),
            [
                'field'  => $field,
                'name'   => $name,
                'select' => $select,
            ]);
    }

    /**
     * 初始化
     * @return $this
     */
    public function render(): static
    {

        // 初始化数据
        $this->renderData();

        // 控制器
        $this->renderController();

        // 模型
        $this->renderModel();

        // 视图
        $this->renderView();

        // JS
        $this->renderJs();

        return $this;
    }

    /**
     * 初始化数据
     * @return $this
     */
    protected function renderData(): static
    {

        // 主表
        foreach ($this->tableColumns as $field => $val) {

            // 过滤字段
            if (in_array($field, $this->ignoreFields)) {
                unset($this->tableColumns[$field]);
                continue;
            }

            // 判断是否已初始化
            if (isset($this->tableColumns[$field]['formType'])) {
                continue;
            }

            // 判断图片
            if ($this->checkContain($field, $this->imageFieldSuffix)) {
                $this->tableColumns[$field]['formType'] = 'image';
                continue;
            }
            if ($this->checkContain($field, $this->imagesFieldSuffix)) {
                $this->tableColumns[$field]['formType'] = 'images';
                continue;
            }

            // 判断文件
            if ($this->checkContain($field, $this->fileFieldSuffix)) {
                $this->tableColumns[$field]['formType'] = 'file';
                continue;
            }
            if ($this->checkContain($field, $this->filesFieldSuffix)) {
                $this->tableColumns[$field]['formType'] = 'files';
                continue;
            }

            // 判断时间
            if ($this->checkContain($field, $this->dateFieldSuffix)) {
                $this->tableColumns[$field]['formType'] = 'date';
                continue;
            }

            // 判断开关
            if (in_array($field, $this->switchFields)) {
                $this->tableColumns[$field]['formType'] = 'switch';
                continue;
            }

            // 判断富文本
            if (in_array($field, $this->editorFields) || in_array($val['type'], ['text', 'tinytext', 'mediumtext', 'longtext'])) {
                $this->tableColumns[$field]['formType'] = 'editor';
                continue;
            }

            // 判断排序
            if (in_array($field, $this->sortFields)) {
                $this->tableColumns[$field]['formType'] = 'sort';
                continue;
            }

            // 判断下拉选择
            if (in_array($field, $this->selectFields)) {
                $this->tableColumns[$field]['formType'] = 'select';
                continue;
            }

            $this->tableColumns[$field]['formType'] = 'text';
        }

        // 关联表
        foreach ($this->relationArray as $table => $tableVal) {
            foreach ($tableVal['tableColumns'] as $field => $val) {

                // 过滤字段
                if (in_array($field, $this->ignoreFields)) {
                    unset($this->relationArray[$table]['tableColumns'][$field]);
                    continue;
                }

                // 判断是否已初始化
                if (isset($this->relationArray[$table]['tableColumns'][$field]['formType'])) {
                    continue;
                }

                // 判断图片
                if ($this->checkContain($field, $this->imageFieldSuffix)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'image';
                    continue;
                }
                if ($this->checkContain($field, $this->imagesFieldSuffix)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'images';
                    continue;
                }

                // 判断文件
                if ($this->checkContain($field, $this->fileFieldSuffix)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'file';
                    continue;
                }
                if ($this->checkContain($field, $this->filesFieldSuffix)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'files';
                    continue;
                }

                // 判断时间
                if ($this->checkContain($field, $this->dateFieldSuffix)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'date';
                    continue;
                }

                // 判断开关
                if (in_array($field, $this->switchFields)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'switch';
                    continue;
                }

                // 判断富文本
                if (in_array($field, $this->editorFields) || in_array($val['type'], ['text', 'tinytext', 'mediumtext', 'longtext'])) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'editor';
                    continue;
                }

                // 判断排序
                if (in_array($field, $this->sortFields)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'sort';
                    continue;
                }

                // 判断下拉选择
                if (in_array($field, $this->selectFields)) {
                    $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'select';
                    continue;
                }

                $this->relationArray[$table]['tableColumns'][$field]['formType'] = 'text';
            }
        }

        return $this;

    }

    /**
     * 初始化控制器
     * @return $this
     */
    protected function renderController(): static
    {
        $controllerFile = "{$this->rootDir}app{$this->DS}admin{$this->DS}controller{$this->DS}{$this->controllerFilename}.php";
        if (empty($this->relationArray)) {
            $controllerIndexMethod = '';
        }else {
            $relationCode = '';
            foreach ($this->relationArray as $key => $val) {
                $relation     = CommonTool::lineToHump($key);
                $relationCode = "->withJoin('{$relation}', 'LEFT')\r";
            }
            $controllerIndexMethod = CommonTool::replaceTemplate(
                $this->getTemplate("controller{$this->DS}indexMethod"),
                [
                    'relationIndexMethod' => $relationCode,
                ]);
        }
        $selectList = '';
        foreach ($this->relationArray as $relation) {
            if (!empty($relation['bindSelectField'])) {
                $relationArray = explode('\\', $relation['modelFilename']);
                $selectList    .= $this->buildSelectController(end($relationArray));
            }
        }
        foreach ($this->tableColumns as $field => $val) {
            if (isset($val['formType']) && in_array($val['formType'], ['select', 'switch', 'radio', 'checkbox']) && isset($val['define'])) {
                $selectList .= $this->buildSelectController($field);
            }
        }

        $modelFilenameExtend = str_replace($this->DS, '\\', $this->modelFilename);

        $controllerValue                 = CommonTool::replaceTemplate(
            $this->getTemplate("controller{$this->DS}controller"),
            [
                'controllerName'       => $this->controllerName,
                'controllerNamespace'  => $this->controllerNamespace,
                'controllerAnnotation' => $this->tableComment,
                'modelFilename'        => "\app\admin\model\\{$modelFilenameExtend}",
                'indexMethod'          => $controllerIndexMethod,
                'selectList'           => $selectList,
            ]);
        $this->fileList[$controllerFile] = $controllerValue;
        return $this;
    }

    /**
     * 初始化模型
     * @return $this
     */
    protected function renderModel(): static
    {
        // 主表模型
        $modelFile = "{$this->rootDir}app{$this->DS}admin{$this->DS}model{$this->DS}{$this->modelFilename}.php";
        $relationList = '';
        if (!empty($this->relationArray)) {
            foreach ($this->relationArray as $key => $val) {
                $relation     = CommonTool::lineToHump($key);
                $relationCode = CommonTool::replaceTemplate(
                    $this->getTemplate("model{$this->DS}relation"),
                    [
                        'relationMethod' => $relation,
                        'relationModel'  => "\app\admin\model\\{$val['modelFilename']}",
                        'foreignKey'     => $val['foreignKey'],
                        'primaryKey'     => $val['primaryKey'],
                    ]);
                $relationList .= $relationCode;
            }
        }

        $selectList = '';
        foreach ($this->relationArray as $relation) {
            if (!empty($relation['bindSelectField'])) {
                $selectList .= $this->buildRelationSelectModel($relation['modelFilename'], $relation['bindSelectField']);
            }
        }
        foreach ($this->tableColumns as $field => $val) {
            if (isset($val['formType']) && in_array($val['formType'], ['select', 'switch', 'radio', 'checkbox']) && isset($val['define'])) {
                $selectList .= $this->buildSelectModel($field, $val['define']);
            }
        }

        $extendNamespaceArray = explode($this->DS, $this->modelFilename);
        $extendNamespace      = null;
        if (count($extendNamespaceArray) > 1) {
            array_pop($extendNamespaceArray);
            $extendNamespace = '\\' . implode('\\', $extendNamespaceArray);
        }
        $modelValue                 = CommonTool::replaceTemplate(
            $this->getTemplate("model{$this->DS}model"),
            [
                'modelName'      => $this->modelName,
                'modelNamespace' => "app\admin\model{$extendNamespace}",
                'prefix_table'   => $this->tablePrefix == config('database.connections.mysql.prefix') ? "" : $this->tablePrefix . $this->table,
                'table'          => $this->table,
                'deleteTime'     => $this->delete ? '"delete_time"' : 'false',
                'relationList'   => $relationList,
                'selectList'     => $selectList,
            ]);
        $this->fileList[$modelFile] = $modelValue;

        // 关联模型
        foreach ($this->relationArray as $key => $val) {
            $relationModelFile = "{$this->rootDir}app{$this->DS}admin{$this->DS}model{$this->DS}{$val['modelFilename']}.php";

            // todo 判断关联模型文件是否存在, 存在就不重新生成文件, 防止关联模型文件被覆盖
            $relationModelClass = "\\app\\admin\\model\\{$val['modelFilename']}";
            if (class_exists($relationModelClass) && method_exists(new $relationModelClass, 'getName')) {
                $tableName = (new $relationModelClass)->getName();
                if (CommonTool::humpToLine(lcfirst($tableName)) == CommonTool::humpToLine(lcfirst($key))) {
                    continue;
                }
            }

            $extendNamespaceArray = explode($this->DS, $val['modelFilename']);
            $extendNamespace      = null;
            if (count($extendNamespaceArray) > 1) {
                array_pop($extendNamespaceArray);
                $extendNamespace = '\\' . implode('\\', $extendNamespaceArray);
            }

            $relationModelValue                 = CommonTool::replaceTemplate(
                $this->getTemplate("model{$this->DS}model"),
                [
                    'modelName'      => $val['modelName'],
                    'modelNamespace' => "app\admin\model{$extendNamespace}",
                    'table'          => $key,
                    'deleteTime'     => $val['delete'] ? '"delete_time"' : 'false',
                    'relationList'   => '',
                    'selectList'     => '',
                ]);
            $this->fileList[$relationModelFile] = $relationModelValue;
        }
        return $this;
    }

    /**
     * 初始化视图
     * @return $this
     */
    protected function renderView(): static
    {
        // 列表页面
        $viewIndexFile                  = "{$this->rootDir}app{$this->DS}admin{$this->DS}view{$this->DS}{$this->viewFilename}{$this->DS}index.html";
        $viewIndexValue                 = CommonTool::replaceTemplate(
            $this->getTemplate("view{$this->DS}index"),
            [
                'controllerUrl' => $this->controllerUrl,
            ]);
        $this->fileList[$viewIndexFile] = $viewIndexValue;

        // 添加页面
        $viewAddFile = "{$this->rootDir}app{$this->DS}admin{$this->DS}view{$this->DS}{$this->viewFilename}{$this->DS}add.html";
        $addFormList = '';
        foreach ($this->tableColumns as $field => $val) {

            if (in_array($field, ['id', 'create_time'])) {
                continue;
            }

            $templateFile = "view{$this->DS}module{$this->DS}input";
            $define       = '';

            // 根据formType去获取具体模板
            if ($val['formType'] == 'image') {
                $templateFile = "view{$this->DS}module{$this->DS}image";
            }elseif ($val['formType'] == 'images') {
                $templateFile = "view{$this->DS}module{$this->DS}images";
                $define       = $val['define'] ?? '|';
            }elseif ($val['formType'] == 'file') {
                $templateFile = "view{$this->DS}module{$this->DS}file";
            }elseif ($val['formType'] == 'files') {
                $templateFile = "view{$this->DS}module{$this->DS}files";
                $define       = $val['define'] ?? '|';
            }elseif ($val['formType'] == 'editor') {
                $templateFile   = "view{$this->DS}module{$this->DS}editor";
                $val['default'] = '""';
            }elseif ($val['formType'] == 'date') {
                $templateFile = "view{$this->DS}module{$this->DS}date";
                if (!empty($val['define'])) {
                    $define = $val['define'];
                }else {
                    $define = 'datetime';
                }
                if (!in_array($define, ['year', 'month', 'date', 'time', 'datetime'])) {
                    $define = 'datetime';
                }
            }elseif ($val['formType'] == 'radio') {
                $templateFile = "view{$this->DS}module{$this->DS}radio";
                if (!empty($val['define'])) {
                    $define = $this->buildRadioView($field, '{in name="k" value="' . $val['default'] . '"}checked=""{/in}');
                }
            }elseif ($val['formType'] == 'checkbox') {
                $templateFile = "view{$this->DS}module{$this->DS}checkbox";
                if (!empty($val['define'])) {
                    $define = $this->buildCheckboxView($field, '{in name="k" value="' . $val['default'] . '"}checked=""{/in}');
                }
            }elseif ($val['formType'] == 'select') {
                $templateFile = "view{$this->DS}module{$this->DS}select";
                if (isset($val['bindRelation'])) {
                    $define = $this->buildOptionView($val['bindRelation']);
                }elseif (!empty($val['define'])) {
                    $define = $this->buildOptionView($field);
                }
            }elseif ($field == 'remark' || $val['formType'] == 'textarea') {
                $templateFile = "view{$this->DS}module{$this->DS}textarea";
            }

            $addFormList .= CommonTool::replaceTemplate(
                $this->getTemplate($templateFile),
                [
                    'comment'  => $val['comment'],
                    'field'    => $field,
                    'required' => $this->buildRequiredHtml($val['required']),
                    'value'    => $val['default'],
                    'define'   => $define,
                ]);
        }
        $viewAddValue                 = CommonTool::replaceTemplate(
            $this->getTemplate("view{$this->DS}form"),
            [
                'formList' => $addFormList,
            ]);
        $this->fileList[$viewAddFile] = $viewAddValue;


        // 编辑页面
        $viewEditFile = "{$this->rootDir}app{$this->DS}admin{$this->DS}view{$this->DS}{$this->viewFilename}{$this->DS}edit.html";
        $editFormList = '';
        foreach ($this->tableColumns as $field => $val) {

            if (in_array($field, ['id', 'create_time'])) {
                continue;
            }

            $templateFile = "view{$this->DS}module{$this->DS}input";

            $define = '';
            $value  = '{$row.' . $field . '|default=\'\'}';

            // 根据formType去获取具体模板
            if ($val['formType'] == 'image') {
                $templateFile = "view{$this->DS}module{$this->DS}image";
            }elseif ($val['formType'] == 'images') {
                $templateFile = "view{$this->DS}module{$this->DS}images";
            }elseif ($val['formType'] == 'file') {
                $templateFile = "view{$this->DS}module{$this->DS}file";
            }elseif ($val['formType'] == 'files') {
                $templateFile = "view{$this->DS}module{$this->DS}files";
            }elseif ($val['formType'] == 'editor') {
                $templateFile = "view{$this->DS}module{$this->DS}editor";
                $value        = '$row["' . $field . '"]';
            }elseif ($val['formType'] == 'date') {
                $templateFile = "view{$this->DS}module{$this->DS}date";
                if (!empty($val['define'])) {
                    $define = $val['define'];
                }else {
                    $define = 'datetime';
                }
                if (!in_array($define, ['year', 'month', 'date', 'time', 'datetime'])) {
                    $define = 'datetime';
                }
            }elseif ($val['formType'] == 'radio') {
                $templateFile = "view{$this->DS}module{$this->DS}radio";
                if (!empty($val['define'])) {
                    $define = $this->buildRadioView($field, '{in name="k" value="$row.' . $field . '"}checked=""{/in}');
                }
            }elseif ($val['formType'] == 'checkbox') {
                $templateFile = "view{$this->DS}module{$this->DS}checkbox";
                if (!empty($val['define'])) {
                    $define = $this->buildCheckboxView($field, '{in name="k" value="$row.' . $field . '"}checked=""{/in}');
                }
            }elseif ($val['formType'] == 'select') {
                $templateFile = "view{$this->DS}module{$this->DS}select";
                if (isset($val['bindRelation'])) {
                    $define = $this->buildOptionView($val['bindRelation'], '{in name="k" value="$row.' . $field . '"}selected=""{/in}');
                }elseif (!empty($val['define'])) {
                    $define = $this->buildOptionView($field, '{in name="k" value="$row.' . $field . '"}selected=""{/in}');
                }
            }elseif ($field == 'remark' || $val['formType'] == 'textarea') {
                $templateFile = "view{$this->DS}module{$this->DS}textarea";
                $value        = '{$row.' . $field . '|raw|default=\'\'}';
            }

            $editFormList .= CommonTool::replaceTemplate(
                $this->getTemplate($templateFile),
                [
                    'comment'  => $val['comment'],
                    'field'    => $field,
                    'required' => $this->buildRequiredHtml($val['required']),
                    'value'    => $value,
                    'define'   => $define,
                ]);
        }
        $viewEditValue                 = CommonTool::replaceTemplate(
            $this->getTemplate("view{$this->DS}form"),
            [
                'formList' => $editFormList,
            ]);
        $this->fileList[$viewEditFile] = $viewEditValue;

        return $this;
    }

    /**
     * 初始化JS
     * @return $this
     */
    protected function renderJs(): static
    {
        $jsFile = "{$this->rootDir}public{$this->DS}static{$this->DS}admin{$this->DS}js{$this->DS}{$this->jsFilename}.js";

        $indexCols = "    {type: 'checkbox'},\r";

        // 主表字段
        foreach ($this->tableColumns as $field => $val) {

            if ($val['formType'] == 'image') {
                $templateValue = "{field: '{$field}', title: '{$val['comment']}', templet: ea.table.image}";
            }elseif ($val['formType'] == 'images') {
                continue;
            }elseif ($val['formType'] == 'file') {
                $templateValue = "{field: '{$field}', title: '{$val['comment']}', templet: ea.table.url}";
            }elseif ($val['formType'] == 'files') {
                continue;
            }elseif ($val['formType'] == 'editor') {
                continue;
            }elseif (in_array($field, $this->switchFields)) {
                if (!empty($val['define'])) {
                    $values        = json_encode($val['define'], JSON_UNESCAPED_UNICODE);
                    $templateValue = "{field: '{$field}', search: 'select', selectList: {$values}, title: '{$val['comment']}', templet: ea.table.switch}";
                }else {
                    $templateValue = "{field: '{$field}', title: '{$val['comment']}', templet: ea.table.switch}";
                }
            }elseif (in_array($val['formType'], ['select', 'checkbox', 'radio', 'switch'])) {
                if (!empty($val['define'])) {
                    $values        = json_encode($val['define'], JSON_UNESCAPED_UNICODE);
                    $templateValue = "{field: '{$field}', search: 'select', selectList: {$values}, title: '{$val['comment']}'}";
                }else {
                    $templateValue = "{field: '{$field}', title: '{$val['comment']}'}";
                }
            }elseif ($field == 'remark') {
                $templateValue = "{field: '{$field}', title: '{$val['comment']}', templet: ea.table.text}";
            }elseif (in_array($field, $this->sortFields)) {
                $templateValue = "{field: '{$field}', title: '{$val['comment']}', edit: 'text'}";
            }else {
                $templateValue = "{field: '{$field}', title: '{$val['comment']}'}";
            }

            $indexCols .= $this->formatColsRow("{$templateValue},\r");
        }

        // 关联表
        foreach ($this->relationArray as $table => $tableVal) {
            $table = CommonTool::lineToHump($table);
            foreach ($tableVal['tableColumns'] as $field => $val) {
                if ($val['formType'] == 'image') {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}', templet: ea.table.image}";
                }elseif ($val['formType'] == 'images') {
                    continue;
                }elseif ($val['formType'] == 'file') {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}', templet: ea.table.url}";
                }elseif ($val['formType'] == 'files') {
                    continue;
                }elseif ($val['formType'] == 'editor') {
                    continue;
                }elseif ($val['formType'] == 'select') {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}'}";
                }elseif ($field == 'remark') {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}', templet: ea.table.text}";
                }elseif (in_array($field, $this->switchFields)) {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}', templet: ea.table.switch}";
                }elseif (in_array($field, $this->sortFields)) {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}', edit: 'text'}";
                }else {
                    $templateValue = "{field: '{$table}.{$field}', title: '{$val['comment']}'}";
                }

                $indexCols .= $this->formatColsRow("{$templateValue},\r");
            }
        }

        $indexCols .= $this->formatColsRow("{width: 250, title: '操作', templet: ea.table.tool},\r");

        $jsValue                 = CommonTool::replaceTemplate(
            $this->getTemplate("static{$this->DS}js"),
            [
                'controllerUrl' => $this->controllerUrl,
                'indexCols'     => $indexCols,
            ]);
        $this->fileList[$jsFile] = $jsValue;
        return $this;
    }

    /**
     * 检测文件
     * @return $this
     */
    protected function check(): static
    {
        // 是否强制性
        if ($this->force) {
            return $this;
        }
        foreach ($this->fileList as $key => $val) {
            if (is_file($key)) {
                throw new FileException("文件已存在：{$key}");
            }
        }
        return $this;
    }

    /**
     * 开始生成
     * @return array
     */
    public function create(): array
    {
        $this->check();
        foreach ($this->fileList as $key => $val) {

            // 判断文件夹是否存在,不存在就创建
            $fileArray = explode($this->DS, $key);
            array_pop($fileArray);
            $fileDir = implode($this->DS, $fileArray);
            if (!is_dir($fileDir)) {
                mkdir($fileDir, 0775, true);
            }

            // 写入
            file_put_contents($key, $val);
        }
        return array_keys($this->fileList);
    }

    /**
     * 开始删除
     * @return array
     */
    public function delete(): array
    {
        $deleteFile = [];
        foreach ($this->fileList as $key => $val) {
            if (is_file($key)) {
                unlink($key);
                $deleteFile[] = $key;
            }
        }
        return $deleteFile;
    }

    /**
     * 检测字段后缀
     * @param $string
     * @param $array
     * @return bool
     */
    protected function checkContain($string, $array): bool
    {
        foreach ($array as $vo) {
            if (str_starts_with($string, $vo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 格式化表单行
     * @param $value
     * @return string
     */
    protected function formatColsRow($value): string
    {
        return "                    {$value}";
    }

    /**
     * 获取对应的模板信息
     * @param $name
     * @return false|string
     */
    protected function getTemplate($name): bool|string
    {
        return file_get_contents("{$this->dir}{$this->DS}templates{$this->DS}{$name}.code");
    }

}