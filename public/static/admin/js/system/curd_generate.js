define(["jquery", "easy-admin", "miniTab"], function ($, ea, miniTab) {

    var form = layui.form;
    var table = layui.table;

    var init = {
        save_url: 'system.curd_generate/save',
    };

    return {
        index: function () {

            let element = layui.element;
            element.on('tab(curd-hash)', function (obj) {
                let id = obj.id
                let _html = `
<div style="padding: 50px 25px;">
<fieldset class="layui-elem-field">
  <legend>提示</legend>
  <div class="layui-field-box">
  <p><a class="layui-font-blue" target="_blank" rel="nofollow" href="https://edocs.easyadmin8.top/curd/command.html">命令可查询文档</a></p>
  </div>
</fieldset>
<form class="layui-form layui-form-pane" action="">
  <div class="layui-form-item">
    <div class="layui-input-group" style="width: 100%;">
      <div class="layui-input-split layui-input-prefix" style="width: 100px;">
          php think curd
      </div>
        <input type="text" class="layui-input" name="command" placeholder="在这里输入命令参数" lay-verify="required"/>
    </div>
  </div>
  <div class="layui-form-item">
        <button class="layui-btn layui-btn-fluid layui-bg-cyan" type="button" lay-submit="system.CurdGenerate/save?type=console" lay-filter="curd-console-submit">一键执行</button>
  </div>
</form>
</div>
`
                if (id == '2') {
                    layer.open({
                        title: '命令行一键生成 CRUD/CRUD',
                        type: 1,
                        shade: 0.3,
                        shadeClose: false,
                        area: ['42%', 'auto'],
                        content: _html,
                        success: function () {
                            form.on('submit(curd-console-submit)', function (data) {
                                let field = data.field
                                let url = $(this).attr('lay-submit')
                                let options = {url: ea.url(url), data: field}
                                ea.msg.confirm('确认执行该操作？<br>如果命令行中存在强制覆盖或者删除将会马上执行！', function () {
                                    ea.request.post(options, function (rs) {
                                        let msg = rs.msg || '未知~'
                                        layer.msg(msg.replace(/\n/g, '<br>'), {shade: 0.3, shadeClose: true})
                                        let code = rs?.code || '-1'
                                        if (code != '1') return
                                    })
                                })
                            })
                        },
                        end: function () {
                            element.tabChange('curd-hash', '1');
                        }
                    })
                }
            });

            miniTab.listen();
            let createStatus = false
            let tb_prefix
            let tb_name
            form.on('submit(search)', function (data) {
                let field = data.field
                tb_prefix = field.tb_prefix
                tb_name = field.tb_name
                ea.request.get({url: $(this).attr('lay-submit'), prefix: true, data: field}, function (res) {
                    createStatus = true
                    $('.tableShow').removeClass('layui-hide')
                    $('.table-text').text(field.tb_prefix + field.tb_name)
                    let _data = res.data

                    let fieldsHtml = ``
                    $.each(_data.list, function (i, v) {
                        if (v.Key != 'PRI') fieldsHtml += `
<div class="input_tag">
<input lay-skin="tag" class="checkbox_${v.Field}" type="checkbox" 
title="${v.Field} (${v.Type})" value="${v.Field}" lay-filter="checkbox-filter" />
</div>
`
                    })
                    $('.table_fields').html(fieldsHtml)
                    form.render('checkbox')

                    form.on('checkbox(checkbox-filter)', function (data) {
                        let _checked = data.elem.checked
                        $.each($(`.checkbox_${data.value}`), function (i, v) {
                            if (i > 0) $(this).prop('checked', false);
                        })
                        $(data.elem).prop('checked', _checked);
                    });

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
                        ], data: _data.data, page: false,
                    });
                }, function (error) {
                    createStatus = false
                    ea.msg.error(error.msg)
                    $('.tableShow').addClass('layui-hide')
                    return
                })
                form.on('submit(add)', function (data) {
                    let table = $('.table-text').text()
                    if (!table || !createStatus) {
                        ea.msg.error('请先查询数据')
                        return
                    }
                    let url = $(this).attr('lay-submit')
                    let fields = {}
                    $.each($('.table_fields'), function (i, v) {
                        let _name = $(this).data('name')
                        let _inputs = {}
                        $.each($(v).find('.input_tag'), function (i, v) {
                            let checkedVal = $(this).find('input:checked').val()
                            if (checkedVal) {
                                _inputs[i] = checkedVal
                            }
                        })
                        fields[_name] = _inputs
                    })
                    let options = {url: url, prefix: true, data: {tb_prefix: tb_prefix, tb_name: tb_name, tb_fields: fields}}
                    layer.confirm('确定要自动生成【' + table + '】对应的CURD?', function (index) {
                        ea.request.post(options, function (res) {
                            createStatus = true
                            ea.msg.success(res.msg)
                            appendHtml(res['data']['result'], res['data']['link'])
                        }, function (error) {
                            createStatus = false
                            let code = error.code
                            if (code != '1') {
                                if (code < 0) {
                                    createStatus = true
                                    layer.confirm(error.msg, {
                                        btn: ['确定强制覆盖生成', '取消'], title: '提示', icon: 0,
                                        yes: function () {
                                            options.prefix = false
                                            options.data.force = 1
                                            ea.request.post(options, function (rs) {
                                                createStatus = true
                                                ea.msg.success(rs.msg)
                                                appendHtml(rs['data']['result'], rs['data']['link'])
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
                    if (!table || !createStatus) {
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
                            $('.table_fields').empty()
                            $('.tableShow').addClass('layui-hide')
                            createStatus = false
                        })
                    })
                })
                return
            })

            function appendHtml(array, link) {
                $('.file-list').empty()
                let html = ''
                $.each(array, function (idx, item) {
                    html += '<li class="layui-form-item">' + item + '</li>'
                })
                html += '<a layuimini-content-href="' + link + '" data-title="页面预览">' +
                    '<button class="layui-btn"><i class="layui-icon layui-icon-link"></i> 自动生成页面预览</button>' +
                    '</a>'
                $('.file-list').html(html)
            }
        }
    };
});