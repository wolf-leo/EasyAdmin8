define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'mall.goods/index',
        add_url: 'mall.goods/add',
        edit_url: 'mall.goods/edit',
        delete_url: 'mall.goods/delete',
        export_url: 'mall.goods/export',
        modify_url: 'mall.goods/modify',
        stock_url: 'mall.goods/stock',
    };

    return {

        index: function () {
            ea.table.render({
                init: init,
                toolbar: ['refresh',
                    [{
                        text: '添加',
                        url: init.add_url,
                        method: 'open',
                        auth: 'add',
                        class: 'layui-btn layui-btn-normal layui-btn-sm',
                        icon: 'fa fa-plus ',
                        extend: 'data-width="90%" data-height="95%"',
                    }],
                    'delete', 'export'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID', searchOp: '='},
                    {field: 'sort', width: 80, title: '排序', edit: 'text'},
                    {field: 'cate_id', minWidth: 80, title: '商品分类', search: 'select', selectList: cateSelects, laySearch: true},
                    {field: 'title', minWidth: 80, title: '商品名称'},
                    {field: 'logo', minWidth: 80, title: '分类图片', search: false, templet: ea.table.image},
                    {field: 'market_price', width: 100, title: '市场价', templet: ea.table.price},
                    {field: 'discount_price', width: 100, title: '折扣价', templet: ea.table.price},
                    {field: 'total_stock', width: 100, title: '库存统计'},
                    {field: 'stock', width: 100, title: '剩余库存'},
                    {field: 'virtual_sales', width: 100, title: '虚拟销量'},
                    {field: 'sales', width: 80, title: '销量'},
                    {field: 'status', title: '状态', width: 85, selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    // 演示多选，实际数据库并无 status2 字段，搜索后会报错
                    {
                        field: 'status2', title: '演示多选', width: 105, search: 'xmSelect', selectList: {1: '模拟选项1', 2: '模拟选项2', 3: '模拟选项3', 4: '模拟选项4', 5: '模拟选项5'},
                        searchOp: 'in', templet: function (res) {
                            // 根据自己实际项目进行输出
                            return res?.status2 || '模拟数据'
                        }
                    },
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                templet: function (d) {
                                    return `<button type="button" class="layui-btn layui-btn-xs">自定义 ${d.id}</button>`
                                }
                            }, {
                                text: '编辑',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs layui-btn-success',
                                extend: 'data-width="90%" data-height="95%"',
                            }, {
                                text: '入库',
                                url: init.stock_url,
                                method: 'open',
                                auth: 'stock',
                                class: 'layui-btn layui-btn-xs layui-btn-normal',
                                visible: function (row) {
                                    return row.status === 1;
                                },
                            }],
                            'delete']
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
        stock: function () {
            ea.listen();
        },
    };
});