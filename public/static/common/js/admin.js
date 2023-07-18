// +----------------------------------------------------------------------
// | 后台公共JS
// +----------------------------------------------------------------------

/**
 * @desc    快速时间范围选择
 * @returns {[{text: string, value: *[]}, {text: string, value: *[]}, {text: string, value: *[]}, {text: string, value: *[]}, {text: string, value: *[]}, null, null]}
 */
function getRangeShortcuts() {
    return [
        {
            text: "今天",
            value: function () {
                let value = [];
                let date1 = new Date();
                date1.setDate(date1.getDate());
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setHours(23, 59, 59, 59);
                value.push(new Date(date2));
                return value;
            }()
        },
        {
            text: "昨天",
            value: function () {
                let value = [];
                let date1 = new Date();
                date1.setDate(date1.getDate() - 1);
                date1.setHours(0, 0, 0, 0);
                value.push(date1);
                let date2 = new Date();
                date2.setDate(date2.getDate() - 1);
                date2.setHours(23, 59, 59, 59);
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
                date2.setDate(date2.getDate() - 1);
                date2.setHours(23, 59, 59, 59);
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
                date2.setHours(23, 59, 59, 59);
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
                date2.setHours(23, 59, 59, 59);
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
                date2.setHours(23, 59, 59, 59);
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
                date2.setHours(23, 59, 59, 59);
                value.push(new Date(date2));
                return value;
            }()
        },
    ];
}

/**
 * @desc    格式化json
 * @param   str
 * @returns {string}
 */
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