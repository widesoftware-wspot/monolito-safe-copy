"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var FieldSelectedTableComponent = (function () {
    function FieldSelectedTableComponent(removeCallback, changeUniqueCallback, changeRequiredCallback) {
        this.wraperElement = $('#selected-fields');
        this.removeCallback = removeCallback;
        this.changeUniqueCallback = changeUniqueCallback;
        this.changeRequiredCallback = changeRequiredCallback;
    }
    FieldSelectedTableComponent.prototype.refresh = function (list) {
        list.sort(function (a, b) {
            if (a.label < b.label)
                return -1;
            if (a.label > b.label)
                return 1;
            return 0;
        });
        this.render(list);
    };
    FieldSelectedTableComponent.prototype.render = function (fieldList) {
        var uniqueDescription = "Com esta opção ativa, siginifica que somente um visitante poderá se cadastrar com um valor, não será aceito cadastros com valores duplicados em sua base de dados.";
        var requiredDescription = "Com esta validação ativa o campo selecionado será obrigatório no ato do cadastro.";
        var table = "\n            <table class=\"table\">\n                <thead>\n                    <tr>\n                        <th class=\"w-center text-muted\">Campo</th>\n                        <th class=\"w-center text-muted\">\n                            \u00DAnico <i class=\"fa fa-question-circle\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"" + uniqueDescription + "\"></i>\n                        </th>\n                        <th class=\"w-center text-muted\">\n                            Obrigat\u00F3rio <i class=\"fa fa-question-circle\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"" + requiredDescription + "\"></i>\n                        </th>\n                        <th></th>\n                    </tr>\n                </thead>\n                <tbody>\n                    " + fieldList.map(function (field) { return "\n                            <tr>\n                                <td class=\"w-center text-muted\">" + field.label + "</td>\n                                <td class=\"w-center\">\n                                    <a href=\"#\" class=\"unique-field-btn\" data-identifier=\"" + field.identifier + "\"><i data-identifier=\"" + field.identifier + "\" class=\"" + (field.unique ? "fa fa-check-square-o fa-2x text-success" : "fa fa-square-o fa-2x text-muted") + "\" aria-hidden=\"true\"></i></a>\n                                </td>\n                                <td class=\"w-center\">\n                                    <a href=\"#\" class=\"required-field-btn\" data-identifier=\"" + field.identifier + "\"><i data-identifier=\"" + field.identifier + "\" class=\"" + (field.required ? "fa fa-check-square-o fa-2x text-success" : "fa fa-square-o fa-2x text-muted") + "\" aria-hidden=\"true\"></i></a>\n                                </td>\n                                <td class=\"w-center\">\n                                    <a href=\"#\" class=\"btn btn-xs btn-danger remove-field-btn\" data-identifier=\"" + field.identifier + "\"><i data-identifier=\"" + field.identifier + "\" class=\"fa fa-times\" aria-hidden=\"true\"></i></a>\n                                </td>\n                            </tr>\n                        "; }).join('') + "\n                </tbody>\n            </table>\n        ";
        if (fieldList.length) {
            this.wraperElement.html(table);
        }
        else {
            this.wraperElement.html("\n                <p class=\"w-center text-muted w-row-space\"><i class=\"fa fa-frown-o fa-5x\" aria-hidden=\"true\"></i></p>\n                <p class=\"w-center text-muted\">Nenhum campo selecionado</p>\n            ");
        }
        var removeCallback = this.removeCallback;
        var changeUniqueCallback = this.changeUniqueCallback;
        var changeRequiredCallback = this.changeRequiredCallback;
        $('.remove-field-btn').bind('click', function (event) {
            var identifier = $(this).data('identifier');
            removeCallback(identifier);
        });
        $('.required-field-btn').bind('click', function (event) {
            var identifier = $(this).data('identifier');
            changeRequiredCallback(identifier);
        });
        $('.unique-field-btn').bind('click', function (event) {
            var identifier = $(this).data('identifier');
            changeUniqueCallback(identifier);
        });
    };
    return FieldSelectedTableComponent;
}());
exports.default = FieldSelectedTableComponent;
//# sourceMappingURL=FieldSelectedTableComponent.js.map