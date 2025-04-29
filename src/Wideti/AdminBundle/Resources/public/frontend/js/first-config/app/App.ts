import {Field} from "./models/Field";
import * as $ from "jquery";
import FieldsTemplateTableComponent from './components/FieldsTemplateTableComponent';
import FieldSelectedTableComponent from "./components/FieldSelectedTableComponent";
import LoginTableComponent from "./components/LoginTableComponent";
import SignupConfirmTableComponent from "./components/SignupConfirmTableComponent";
import SigninConfirmTableComponent from "./components/SigninConfirmTableComponent";
import SaveNavigationComponent from "./components/SaveNavigationComponent";

export default class App {

    private fieldsTemplate : Array<Field>;
    private fieldsSelected : Array<Field>;
    private loginField : Field;
    private configApp  : ConfigApp;
    private fieldsTemplateTableComponent : FieldsTemplateTableComponent;
    private fieldSelectedTableComponent  : FieldSelectedTableComponent;
    private loginFieldTable : LoginTableComponent;
    private confirmSignupTable : SignupConfirmTableComponent;
    private confirmSigninTable : SigninConfirmTableComponent;
    private saveNavBarComponent : SaveNavigationComponent;
    private temp: Array<Field>;

    constructor(config : ConfigApp)
    {
        this.configApp = config;
        this.fieldsSelected = [];
        this.fieldsTemplate = [];
        this.fieldsTemplateTableComponent = new FieldsTemplateTableComponent(this.selectField.bind(this));
        this.fieldSelectedTableComponent  = new FieldSelectedTableComponent(
            this.unSelectField.bind(this),
            this.setUniqueStatus.bind(this),
            this.setRequiredStatus.bind(this),
            this.renderFunction.bind(this)
            );
        this.loginFieldTable = new LoginTableComponent(this.selectFieldToLogin.bind(this));
        this.confirmSignupTable = new SignupConfirmTableComponent();
        this.confirmSigninTable = new SigninConfirmTableComponent();
        this.saveNavBarComponent = new SaveNavigationComponent(this.saveFields.bind(this), this.configApp.urlDashboardIndex);
    }

    public init()
    {
        let loadTemplatesPromise = this.loadTemplateFields();

        loadTemplatesPromise
            .then((fields) => {
                this.fieldsTemplate = fields;
                this.render();
            })
            .catch((data) => {
                //TODO tratar erro no servidor
            });
    }

    public render() : void
    {
        this.fieldSelectedTableComponent.refresh(this.fieldsSelected);
        this.fieldsTemplateTableComponent.refresh(this.fieldsTemplate);
        this.loginFieldTable.refresh(this.fieldsSelected);
        this.confirmSignupTable.refresh(this.fieldsSelected);
        this.confirmSigninTable.refresh(this.loginField);
        this.saveNavBarComponent.refresh(this.fieldsSelected, this.loginField);
    }

    private loadTemplateFields() : Promise<Array<Field>>
    {
        return new Promise<Array<Field>>((receive) => {
            $.ajax({
                method : "get",
                url: this.configApp.urlLoadTemplateFields,
                success : (resp) => {
                    receive(resp);
                },
                error :  (resp) => {
                    //TODO tratar os errors com uma componente na view
                    console.log(resp);
                }
            })
        });
    }

    private selectField (identifier : string) : void
    {
        this.fieldsTemplate.forEach((field) => {
            if (field.identifier === identifier) {
                this.fieldsSelected.push(field);
            }
        });

        this.fieldsTemplate = this.fieldsTemplate
            .filter((field) => field.identifier !== identifier);

        this.render();
    }

    private unSelectField (identifier : string) : void
    {
        this.fieldsSelected.forEach((field) => {
            if (field.identifier === identifier) {
                this.fieldsTemplate.push(field);
            }
        });

        this.fieldsSelected = this.fieldsSelected
            .filter((field) => field.identifier !== identifier);

        this.unSelectLoginField(identifier);

        if (this.fieldsSelected.length === 0) {
            this.loginField = null;
        }

        this.render();
    }

    private setRequiredStatus(identifier : string, status? : boolean) : void
    {
        let field = this.getFieldFrom(identifier, this.fieldsSelected);

        if (status === undefined) {
            field.required = !field.required;
        } else {
            field.required = status;
        }

         if (!field.required && field.unique) {
             this.setUniqueStatus(identifier, false);
        }

        this.render();
    }

    private setUniqueStatus(identifier : string, status? : boolean) : void
    {
        let field = this.getFieldFrom(identifier, this.fieldsSelected);

        if (status === undefined) {
            field.unique = !field.unique;
        } else {
            field.unique = status;
        }

        if (field.unique && !field.required) {
            this.setRequiredStatus(identifier, true);
        }

        if (!field.unique) {
            this.unSelectLoginField(identifier);
        }

        this.render();
    }

    private selectFieldToLogin(identifier : string)
    {
        this.fieldsSelected.forEach((field) => {
            field.login = false;
        });

        let field = this.getFieldFrom(identifier, this.fieldsSelected);
        field.login = true;

        this.loginField = field;
        this.render();
    }

    private getFieldFrom(identifier : string, fieldList : Array<Field>) : Field
    {
        for (let i = 0; i < fieldList.length; i++ ) {
            if ( fieldList[i].identifier === identifier) return fieldList[i]
        }
        return null;
    }

    private unSelectLoginField(identifier : string) : void
    {
        if (!this.loginField) {
            return;
        }

        if (this.loginField.identifier === identifier) {
            this.loginField.login = false;
            this.loginField = null;
        }
    }

    private saveFields() : Promise<any>
    {
        let postData = {
            signUpFields : this.fieldsSelected,
            signInField : this.loginField
        };

        return new Promise<any>((resolve, reject) => {
            $.ajax({
                method: 'post',
                url: this.configApp.urlSaveConfigurations,
                data: JSON.stringify(postData),
                dataType: "json",
                success : (resp) => {
                    resolve(resp)
                },
                error : (resp) => {
                    reject(resp);
                }
            })
        });
    }

    private renderFunction(identifier: Array<string>): void
    {
        var tempArray = this.fieldsSelected.slice();
        var newArray: Array<Field> = [];

        for(var i = 0; i < identifier.length; i++){
            for(var j = 0; j < tempArray.length; j++){
                if(identifier[i] === tempArray[j].identifier){
                    newArray.push(tempArray[j]);
                }
            }
        }

        this.fieldsSelected = newArray;
        this.render();
    }
    
}

interface ConfigApp {
    urlLoadTemplateFields : string;
    urlSaveConfigurations : string;
    urlDashboardIndex : string;
}