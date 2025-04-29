import {Component} from "./Component";
import * as $ from 'jquery';
import {Field} from "../models/Field";

export default class SaveNavigationComponent implements Component
{
    private wrapperElement : JQuery;
    private wrapperAlert : JQuery;
    private saveCallback : Function;
    private redirectUrl : string;

    public constructor(saveCallback : Function, redirectUrl : string)
    {
        this.wrapperElement = $('#finish-navigation-wrapper');
        this.wrapperAlert = $('#alert-wrapper');
        this.saveCallback = saveCallback;
        this.redirectUrl = redirectUrl;
    }

    public refresh(selectedFields? : Array<Field>, loginField? : Field): void {
        this.render(selectedFields, loginField);
    }

    private render(selectedFields? : Array<Field>, loginField? : Field)
    {
        let hasError : boolean = (selectedFields.length == 0 || !loginField);

        let content  = `
            <div class="text-center" role="group">
                <a href="#configs/2" type="button" class="btn btn-warning"><i class="fa fa-arrow-left" aria-hidden="true"></i> Voltar</a>
                <a href="#configs/3" type="button" class="btn btn-default"><span class="text-muted">3/3</span></a>
                <a href="#" type="button" class="btn btn-success ${hasError ? "disabled" : ""}" id="save-btn">Finalizar e Salvar <i class="fa fa-floppy-o" aria-hidden="true"></i></a>
            </div>
        `;

        this.wrapperElement.html(content);
        this.wrapperAlert.html('');

        if (hasError) {
            this.wrapperAlert.html(`
            <p class="alert alert-danger w-center">
                <i class="fa fa-exclamation" aria-hidden="true"></i> Antes de salvar suas configurações você deve selecionar ao menos um campo para o formulário de cadastro e o campo para login.
            </p>
            `);
        }

        let saveCallback = this.saveCallback;
        let wrapperAlert = this.wrapperAlert;
        let elementWrapper = this.wrapperElement;
        let redirectUrl = this.redirectUrl;

        $('#save-btn').bind('click', function(event) {
            event.preventDefault();

            if (hasError) {
                wrapperAlert.html(`
                    <p class="alert alert-danger w-center">
                        <i class="fa fa-exclamation" aria-hidden="true"></i> Existem erros nas informações selecionadas, confira e tente novamente.
                    </p>
                    `);
                return;
            }

            elementWrapper.html(`
                <p class="w-center text-muted"><i class="fa fa-cog fa-spin fa-2x fa-fw" aria-hidden="true"></i> Salvando aguarde...</p>`
            );
            $(this).addClass('disabled');

            saveCallback()
                .then((resp : any) => {
                    elementWrapper.html(`
                        <h4 class="w-center text-success">
                            <i class="fa fa-thumbs-o-up" aria-hidden="true"></i> Configuração salva com sucesso! 
                        </h4>
                        <p class="w-center w-row-space">
                            <a href="${redirectUrl}" class="btn btn-success">Ir para meu painel <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                        </p>
                    `);
                })
                .catch((resp : any) => {
                    wrapperAlert.html(`
                    <p class="alert alert-danger w-center">
                        <i class="fa fa-exclamation" aria-hidden="true"></i> ${resp.responseJSON.message}
                    </p>
                    `);
                });
        });
    }
}
