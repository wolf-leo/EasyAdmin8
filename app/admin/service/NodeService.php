<?php

namespace app\admin\service;


use app\admin\service\auth\Node;

class NodeService
{

    /**
     * 获取节点服务
     * @return array
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function getNodeList()
    {
        $basePath      = base_path() . 'admin' . DIRECTORY_SEPARATOR . 'controller';
        $baseNamespace = "app\admin\controller";
        $nodeList      = (new Node($basePath, $baseNamespace))->getNodeList();
        return $nodeList;
    }
}