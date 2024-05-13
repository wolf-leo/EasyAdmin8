define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form;

    return {
        index: function () {
            var _group = 'site'
            var element = layui.element;
            element.on('tab(docDemoTabBrief)', function (data) {
                _group = $(this).data('group')
            });

            var app = new Vue({
                el: '#app',
                data: {
                    upload_type: upload_type
                }
            });

            form.on("radio(upload_type)", function (data) {
                app.upload_type = this.value;
            });

            form.on("submit", function (data) {
                data.field['group'] = _group
            });

            ea.listen();
        }
    };
});