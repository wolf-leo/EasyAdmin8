define(["jquery", "easy-admin"], function ($, ea) {

        var init = {
            table_elem: '#currentTable',
            table_render_id: 'currentTableRenderId',
            index_url: 'system.auth/index',
            add_url: 'system.auth/add',
            edit_url: 'system.auth/edit',
            delete_url: 'system.auth/delete',
            export_url: 'system.auth/export',
            modify_url: 'system.auth/modify',
            authorize_url: 'system.auth/authorize',
        };

        return {

            index: function () {
                ea.table.render({
                    init: init,
                    cols: [[
                        {type: "checkbox"},
                        {field: 'id', width: 80, title: 'ID', searchOp: '='},
                        {field: 'sort', width: 80, title: '排序', edit: 'text'},
                        {field: 'title', minWidth: 80, title: '权限名称'},
                        {field: 'remark', minWidth: 80, title: '备注信息'},
                        {field: 'status', title: '状态', width: 85, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                        {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                        {
                            width: 250,
                            title: '操作',
                            templet: ea.table.tool,
                            operat: [
                                'edit',
                                [{
                                    text: '授权',
                                    url: init.authorize_url,
                                    method: 'open',
                                    auth: 'authorize',
                                    class: 'layui-btn layui-btn-normal layui-btn-xs',
                                }],
                                'delete'
                            ]
                        }
                    ]],
                });

                ea.listen();
            },
            add: function () {
                ea.listen();
            },
            edit: function () {
                ea.listen();
            },
            authorize: function () {

                let setting = {
                    check: {
                        enable: true,
                        chkStyle: "checkbox",
                    },
                    view: {
                        showIcon: true,
                        showLine: true,
                        selectedMulti: false,
                        dblClickExpand: false
                    }, callback: {
                        onClick: function (e, treeId, treeNode, clickFlag) {
                            treeObj.checkNode(treeNode, !treeNode.checked, true);
                        }
                    }
                };
                let treeObj
                let treeData = []
                ea.request.get({url: window.location.href}, function (res) {
                        res.data = res.data || [];
                        $.each(res.data, function (index, value) {
                            treeData[index] = []
                            treeData[index].id = value.id
                            treeData[index].name = value.title
                            treeData[index].pId = 0
                            treeData[index].open = true
                            let children = value.children
                            treeData[index]['children'] = []
                            $.each(children, function (idx, val) {
                                treeData[index]['children'].push({id: val.id, name: val.title, checked: val.checked, pId: value.id})
                            })
                        })
                        $.fn.zTree.init($("#tree"), setting, treeData);
                        treeObj = $.fn.zTree.getZTreeObj("tree");
                    }
                );

                ea.listen(function (data) {
                    let checkedData = treeObj.getCheckedNodes();
                    let ids = []
                    for (var i = 0; i < checkedData.length; i++) {
                        ids.push(checkedData[i].id)
                    }
                    data.node = JSON.stringify(ids);
                    return data;
                });

            }
        }
    }
)
