import {Component} from "./Component";
import {Field} from "../models/Field";
import * as $ from 'jquery';

export default class SigninConfirmTableComponent implements Component
{
    private elementWraper : JQuery;

    public constructor()
    {
        this.elementWraper = $('#confirm-signin-table-wrapper');
    }

    public render(loginField : Field) : void
    {
        this.refresh(loginField);
    }

    public refresh(loginField : Field) : void
    {
        let content = `<p class="w-center text-muted">Nenhum campo foi selecionado.</p>`;
        if (loginField) {
            content = `
                <table class="table">
                    <tbody>
                        <tr class="w-center text-muted">
                            <td class="text-success"><i class="fa fa-check" aria-hidden="true"></i></td>
                            <td>${loginField.label}</td>
                        </tr>
                    </tbody>
                </table> 
            `;
        }

        this.elementWraper.html(content);
    }
}
