define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form;

    return {
        index: function () {
            var _group = 'site'
            var element = layui.element;
            element.on('tab(docDemoTabBrief)', function (data) {
                _group = $(this).data('group')
            });

            let _upload_type = upload_type || 'local'
            $('.upload_type').addClass('layui-hide')
            $('.' + _upload_type).removeClass('layui-hide')

            form.on("radio(upload_type)", function (data) {
                _upload_type = this.value;
                $('.upload_type').addClass('layui-hide')
                $('.' + _upload_type).removeClass('layui-hide')
            });


            form.on("submit", function (data) {
                data.field['group'] = _group
            });

            ea.listen();
        }
    };
});