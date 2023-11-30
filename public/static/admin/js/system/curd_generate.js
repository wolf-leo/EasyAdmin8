define(["jquery", "easy-admin"], function ($, ea, Vue) {

    var form = layui.form;
    var table = layui.table;

    var init = {
        save_url: 'system.curd_generate/save',
    };

    var Controller = {
        index: function () {

            let tb_prefix
            let tb_name
            form.on('submit(search)', function (data) {
                let field = data.field
                tb_prefix = field.tb_prefix
                tb_name = field.tb_name
                ea.request.post({url: $(this).attr('lay-submit'), prefix: true, data: field}, function (res) {
                    let code = res.code || '0'
                    if (code != '1') {
                        ea.msg.error(res.msg)
                        return
                    }
                    $('.tableShow').removeClass('layui-hide')
                    $('.table-text').text(field.tb_prefix + field.tb_name)
                    let _data = res.data
                    table.render({
                        elem: '#currentTable', cols: [
                            [
                                {field: 'name', title: '字段', minWidth: 80},
                                {field: 'type', title: '类型', minWidth: 80},
                                {field: 'key', title: '键', minWidth: 80},
                                {field: 'extra', title: '是否自增', minWidth: 80},
                                {field: 'null', title: '是否为空', minWidth: 80},
                                {field: 'desc', title: '描述', minWidth: 80},
                            ]
                        ],
                        data: _data.data,
                        page: false,
                    });

                })
                form.on('submit(add)', function (data) {
                    let table = $('.table-text').text()
                    if (!table) {
                        ea.msg.error('请先查询数据')
                        return
                    }
                    let url = $(this).attr('lay-submit')
                    let options = {url: url, prefix: true, data: {tb_prefix: tb_prefix, tb_name: tb_name}}
                    layer.confirm('确定要自动生成【' + table + '】对应的CURD?', function (index) {
                        ea.request.post(options, function (res) {
                            ea.msg.success(res.msg)
                            let html = ''
                            $.each(res['data']['result'], function (idx, item) {
                                html += '<li class="layui-form-item">' + item + '</li>'
                            })
                            $('.file-list').html(html)
                        }, function (error) {
                            let code = error.code
                            if (code != '1') {
                                if (code < 0) {
                                    layer.confirm(error.msg, {
                                        btn: ['确定强制覆盖生成'], title: '提示', icon: 0,
                                        yes: function () {
                                            options.prefix = false
                                            options.data.force = 1
                                            ea.request.post(options, function (rs) {
                                                ea.msg.success(rs.msg)
                                                $('.file-list').empty()
                                                let html = ''
                                                $.each(rs['data']['result'], function (idx, item) {
                                                    html += '<li class="layui-form-item">' + item + '</li>'
                                                })
                                                $('.file-list').html(html)
                                            })
                                        }
                                    });
                                    return
                                }
                                ea.msg.error(error.msg)
                                return
                            }
                        })
                    })
                })

                form.on('submit(delete)', function (data) {
                    let table = $('.table-text').text()
                    if (!table) {
                        ea.msg.error('请先查询数据')
                        return
                    }
                    let url = $(this).attr('lay-submit')
                    let options = {url: url, prefix: true, data: {tb_prefix: tb_prefix, tb_name: tb_name}}
                    layer.confirm('确定要删除【' + table + '】对应CURD的文件?<br>确定清楚自己在做什么！', function (index) {
                        ea.request.post(options, function (res) {
                            ea.msg.success(res.msg)
                            $('.table-text').text('')
                            $('.file-list').empty()
                        })
                    })
                })
                return
            })
        }
    };
    return Controller;
});