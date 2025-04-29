import {Component} from "./Component";
import {Field} from "../models/Field";

export default class LoginTableComponent implements Component
{
    private loginSelectCallback : Function;
    private wrapperElement : JQuery;

    public constructor(loginSelectCallback : Function)
    {
        this.loginSelectCallback = loginSelectCallback;
        this.wrapperElement = $("#login-select-wrapper");
    }

    public refresh(fieldList : Array<Field>) : void
    {
        let list = fieldList.filter((filter) => (filter.required && filter.unique));
        this.render(list);
    }

    public render(fieldList : Array<Field>) : void
    {
        let content : string = `
            <table class="table">
                <tbody>
                    ${
                        fieldList.map((field) => {
                           return `
                            <tr class="w-center">
                                <td class="w-center text-muted">${field.label}</td>
                                <td class="w-center"><a href="#" class="btn-select-login" data-identifier="${field.identifier}"><i data-identifier="${field.identifier}" class="${field.login ? "fa fa-check-square-o fa-2x text-success" : "fa fa-square-o fa-2x text-muted"}" aria-hidden="true"></i></a></td>
                            </tr>
                            `    
                        }).join('')
                    }                    
                </tbody>
            </table>
        `;

        this.wrapperElement.html(content);

        if (fieldList.length) {

        } else {
            this.wrapperElement.html(`
                <p class="w-center text-muted w-row-space"><i class="fa fa-frown-o fa-5x" aria-hidden="true"></i></p>
                <p class="w-center text-muted">Campo de login não disponível!</p>
                <p class="w-center text-muted">Adicione algum campo com ambas validações, "único" e "obrigatório"</p>
            `);
        }

        let loginSelectCallback = this.loginSelectCallback;

        $(".btn-select-login").bind('click',function (event) {
            let identifier : string = $(this).data('identifier');
            loginSelectCallback(identifier);
        });
    }
}
