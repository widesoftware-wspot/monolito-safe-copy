import {Component} from "./Component";
import {Field} from "../models/Field";
import * as $ from 'jquery';

export default class SignupConfirmTableComponent implements Component
{
    private wrapperElement : JQuery;

    public constructor()
    {
        this.wrapperElement = $('#confirm-signup-table-wrapper');
    }

    public refresh(fieldList : Array<Field>): void
    {
        this.render(fieldList);
    }

    public render(fieldList : Array<Field>) : void
    {
        let content = `
            <table class="table">
                <tbody>
                ${
                    fieldList.map((field) => `
                            <tr class="w-center text-muted">
                                <td class="text-success"><i class="fa fa-check" aria-hidden="true"></i></td>
                                <td>${field.label}</td>
                            </tr>`).join('')
                }
                </tbody>
            </table>
        `;

        if (fieldList.length) {
            this.wrapperElement.html(content);
        } else {
            this.wrapperElement.html(`<p class="w-center text-muted">Nenhum campo foi selecionado.</p>`);
        }
    }
}
