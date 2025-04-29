import {Component} from "./Component";
import {Field} from "../models/Field";

export default class FieldSelectedTableComponent implements Component
{
    private wraperElement : JQuery;
    private removeCallback : Function;
    private changeUniqueCallback : Function;
    private changeRequiredCallback : Function;
    private renderFunction: Function;

    constructor(removeCallback : Function, changeUniqueCallback : Function, changeRequiredCallback : Function, renderFunction: Function)
    {
        this.wraperElement = $('#selected-fields');
        this.removeCallback = removeCallback;
        this.changeUniqueCallback = changeUniqueCallback;
        this.changeRequiredCallback = changeRequiredCallback;
        this.renderFunction = renderFunction
    }

    public refresh(list : Array<Field>): void
    {
        this.render(list);
    }

    private render(fieldList : Array<Field>)
    {
        let uniqueDescription = "Com esta opção ativa, siginifica que somente um visitante poderá se cadastrar com um valor, não será aceito cadastros com valores duplicados em sua base de dados.";
        let requiredDescription = "Com esta validação ativa o campo selecionado será obrigatório no ato do cadastro."

        let table = `
            <table class="table" id="table-fields">
                <thead>
                    <tr>
                        <th class="w-center text-muted"></th>
                        <th class="w-center text-muted">Campo</th>
                        <th class="w-center text-muted">
                            Único <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" data-placement="left" title="${uniqueDescription}"></i>
                        </th>
                        <th class="w-center text-muted">
                            Obrigatório <i class="fa fa-question-circle" aria-hidden="true" data-toggle="tooltip" data-placement="left" title="${requiredDescription}"></i>
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="fields-cells">
                    ${
                        fieldList.map((field) => `
                            <tr class="field-row">
                                <td><i class="fa fa-sort" aria-hidden="true"></i></td>
                                <td class="w-center text-muted">${field.label}</td>
                                <td class="w-center">
                                    <a href="#" class="unique-field-btn" data-identifier="${field.identifier}"><i data-identifier="${field.identifier}" class="${field.unique ? "fa fa-check-square-o fa-2x text-success" : "fa fa-square-o fa-2x text-muted"}" aria-hidden="true"></i></a>
                                </td>
                                <td class="w-center">
                                    <a href="#" class="required-field-btn" data-identifier="${field.identifier}"><i data-identifier="${field.identifier}" class="${field.required ? "fa fa-check-square-o fa-2x text-success" : "fa fa-square-o fa-2x text-muted"}" aria-hidden="true"></i></a>
                                </td>
                                <td class="w-center">
                                    <a href="#" class="btn btn-xs btn-danger remove-field-btn" data-identifier="${field.identifier}"><i data-identifier="${field.identifier}" class="fa fa-times" aria-hidden="true"></i></a>
                                </td>
                            </tr>
                        `).join('')
                    }
                </tbody>
            </table>
        `;

        if (fieldList.length) {
            this.wraperElement.html(table);
        } else {
            this.wraperElement.html(`
                <p class="w-center text-muted w-row-space"><i class="fa fa-frown-o fa-5x" aria-hidden="true"></i></p>
                <p class="w-center text-muted">Nenhum campo selecionado</p>
            `);
        }

        let removeCallback = this.removeCallback;
        let changeUniqueCallback = this.changeUniqueCallback;
        let changeRequiredCallback = this.changeRequiredCallback;
        let renderTable = this.renderFunction;

        $('.remove-field-btn').bind('click',function(event) {
            let identifier = $(this).data('identifier');
            removeCallback(identifier);
        });

        $('.required-field-btn').bind('click', function (event) {
            let identifier = $(this).data('identifier');
            changeRequiredCallback(identifier);
        });

        $('.unique-field-btn').bind('click', function (event) {
            let identifier = $(this).data('identifier');
            changeUniqueCallback(identifier);
        });

        $('.field-row').bind('click', function(event){
            var elementsOrder = document.querySelectorAll('.field-row .unique-field-btn');
            var elementsIdentifier = [];
            for(let i = 0; i < elementsOrder.length; i++){
                var identifier = elementsOrder[i].getAttribute('data-identifier');
                elementsIdentifier.push(identifier);
            }
            renderTable(elementsIdentifier);
        })

    }
}