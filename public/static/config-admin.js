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
        "layuiall": ["plugs/layui-v2.x/layui.all"],
        "layui": ["plugs/layui-v2.x/layui"],
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
        "swiper": ["plugs/swiper/swiper-bundle.min"],
    }
});

// 路径配置信息
var PATH_CONFIG = {
    iconLess: BASE_URL + "plugs/font-awesome-4.7.0/less/variables.less",
};
window.PATH_CONFIG = PATH_CONFIG;

// 初始化控制器对应的JS自动加载
window.addEventListener('load', function () {
    if ("undefined" != typeof CONFIG.AUTOLOAD_JS && CONFIG.AUTOLOAD_JS) {
        require([BASE_URL + CONFIG.CONTROLLER_JS_PATH], function (Controller) {
            if (typeof Controller[CONFIG.ACTION] == "function") {
                Controller[CONFIG.ACTION]()
            } else {
                console.error(`\r\n控制器对应的JS ${CONFIG.CONTROLLER_JS_PATH} 监测异常\r\n当前Js文件中不存在监听 ${CONFIG.ACTION} 方法`)
            }
        }, function (e) {
            console.error(e);
        });
    }
})

