var BASE_URL = document.scripts[document.scripts.length - 1].src.substring(0, document.scripts[document.scripts.length - 1].src.lastIndexOf("/") + 1);
window.BASE_URL = BASE_URL;
require.config({
    urlArgs: "v=" + CONFIG.VERSION,
    baseUrl: BASE_URL,
    paths: {
        "jquery": ["plugs/jquery-3.4.1/jquery-3.4.1.min"],
        "jquery-particleground": ["plugs/jq-module/jquery.particleground.min"],
        "echarts": ["plugs/echarts/echarts.min"],
        "echarts-theme": ["plugs/echarts/echarts-theme"],
        "easy-admin": ["plugs/easy-admin/easy-admin"],
        "layuiall": ["plugs/layui-v2.8.x/layui.all"],
        "layui": ["plugs/layui-v2.8.x/layui"],
        "miniAdmin": ["plugs/lay-module/layuimini/miniAdmin"],
        "miniMenu": ["plugs/lay-module/layuimini/miniMenu"],
        "miniTab": ["plugs/lay-module/layuimini/miniTab"],
        "miniTheme": ["plugs/lay-module/layuimini/miniTheme"],
        "miniTongji": ["plugs/lay-module/layuimini/miniTongji"],
        "treetable": ["plugs/lay-module/treetable-lay/treetable"],
        "tableSelect": ["plugs/lay-module/tableSelect/tableSelect"],
        "iconPickerFa": ["plugs/lay-module/iconPicker/iconPickerFa"],
        "autocomplete": ["plugs/lay-module/autocomplete/autocomplete"],
        "vue": ["plugs/vue-2.6.10/vue.min"],
        "ckeditor": ["plugs/ckeditor4/ckeditor"],
    }
});

// 路径配置信息
var PATH_CONFIG = {
    iconLess: BASE_URL + "plugs/font-awesome-4.7.0/less/variables.less",
};
window.PATH_CONFIG = PATH_CONFIG;

// 初始化控制器对应的JS自动加载
if ("undefined" != typeof CONFIG.AUTOLOAD_JS && CONFIG.AUTOLOAD_JS) {
    require([BASE_URL + CONFIG.CONTROLLER_JS_PATH], function (Controller) {
        if (eval('Controller.' + CONFIG.ACTION)) {
            eval('Controller.' + CONFIG.ACTION + '()');
        }
    });
}

// 快速时间范围选择
function getRangeShortcuts() {
    return [
        {
            text: "昨天",
            value: function () {
                let value = [];
                let date1 = new Date();
                date1.setDate(date1.getDate() - 1);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setHours(0, 0, 0, 0);
                value.push(new Date(date2));
                return value;
            }()
        },
        {
            text: "前天",
            value: function () {
                let value = [];
                let date1 = new Date();
                date1.setDate(date1.getDate() - 2);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setDate(date2.getDate() - 1);
                date2.setHours(0, 0, 0, 0);
                value.push(new Date(date2));
                return value;
            }()
        },
        {
            text: "7天内",
            value: function () {
                let value = [];
                let date1 = new Date();
                // date1.setMonth(date1.getMonth() - 1);
                date1.setDate(date1.getDate() - 7);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setDate(date2.getDate());
                date2.setHours(0, 0, 0, 0);
                value.push(new Date(date2));
                return value;
            }()
        },
        {
            text: "这个月",
            value: function () {
                let value = [];
                let date1 = new Date();
                // date1.setMonth(date1.getMonth() - 1);
                date1.setDate(1);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setDate(date2.getDate());
                date2.setHours(0, 0, 0, 0);
                value.push(new Date(date2));
                return value;
            }()
        },
        {
            text: "上个月",
            value: function () {
                let value = [];
                let date1 = new Date();
                date1.setMonth(date1.getMonth() - 1);
                date1.setDate(1);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setDate(1);
                date2.setDate(date2.getDate() - 1);
                date2.setHours(0, 0, 0, 0);
                value.push(new Date(date2));
                return value;
            }()
        },
        {
            text: "今年",
            value: function () {
                let value = [];
                let date1 = new Date();
                date1.setMonth(0);
                date1.setDate(1);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setDate(date2.getDate());
                date2.setHours(0, 0, 0, 0);
                value.push(new Date(date2));
                return value;
            }()
        },
    ];
}

function prettyFormat(str) {
    let result = ''
    try {
        // 设置缩进为2个空格
        str = JSON.stringify(JSON.parse(str), null, 2);
        str = str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        result += str.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    } catch (e) {
        return ''
    }
    return "<pre>" + result + "</pre>"
}