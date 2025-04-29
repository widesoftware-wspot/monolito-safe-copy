"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var $ = require("jquery");
var FieldsTemplateTableComponent = (function () {
    function FieldsTemplateTableComponent(selectBtnAction) {
        this.wraperElement = $('#template-fields');
        this.selectAction = selectBtnAction;
    }
    FieldsTemplateTableComponent.prototype.refresh = function (list) {
        list.sort(function (a, b) {
            if (a.label < b.label)
                return -1;
            if (a.label > b.label)
                return 1;
            return 0;
        });
        this.render(list);
    };
    FieldsTemplateTableComponent.prototype.render = function (fieldList) {
        var content = "\n        <table class=\"table\">\n           <tbody>\n           " + fieldList.map(function (field) { return "\n                        <tr>\n                            <td class=\"w-center text-muted\">" + field.label + "</td>\n                            <td class=\"w-center\"><a href=\"#\" class=\"btn btn-xs btn-primary select-btn\" data-identifier=\"" + field.identifier + "\"><i data-identifier=\"" + field.identifier + "\" class=\"fa fa-plus\" aria-hidden=\"true\"></i></a></td>\n                        </tr>\n                   "; }).join('') + "\n           </tbody>\n        </table>\n        ";
        this.wraperElement.html(content);
        var action = this.selectAction;
        $('.select-btn').bind('click', function (event) {
            var identifier = $(this).data('identifier');
            action(identifier);
        });
    };
    return FieldsTemplateTableComponent;
}());
exports.default = FieldsTemplateTableComponent;
//# sourceMappingURL=FieldsTemplateTableComponent.js.map