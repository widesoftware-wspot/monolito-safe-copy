import {Component} from "./Component";
import * as $ from 'jquery';
import {Field} from '../models/Field';

export default class FieldsTemplateTableComponent implements Component {

    private wraperElement : JQuery;
    private selectAction : Function;

    constructor(selectBtnAction : Function)
    {
        this.wraperElement = $('#template-fields');
        this.selectAction = selectBtnAction;
    }

    public refresh(list : Array<Field>): void
    {
        list.sort(function (a, b) {
            if(a.label < b.label) return -1;
            if(a.label > b.label) return 1;
            return 0;
        });

        this.render(list);
    }

    private render(fieldList : Array<Field>): void
    {
        let content : string = `
        <table class="table">
           <tbody>
           ${
            fieldList.map((field) => `
                        <tr>
                            <td class="w-center text-muted">${field.label}</td>
                            <td class="w-center"><a href="#" class="btn btn-xs btn-primary select-btn" data-identifier="${field.identifier}"><i data-identifier="${field.identifier}" class="fa fa-plus" aria-hidden="true"></i></a></td>
                        </tr>
                   `).join('')
            }
           </tbody>
        </table>
        `;
        this.wraperElement.html(content);

        let action = this.selectAction;
        $('.select-btn').bind('click',function(event) {
            let identifier = $(this).data('identifier');
            action(identifier);
        });
    }
}
