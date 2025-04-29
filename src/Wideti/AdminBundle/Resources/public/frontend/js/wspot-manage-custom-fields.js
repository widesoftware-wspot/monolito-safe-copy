document.addEventListener('DOMContentLoaded', function() {
    $('.toggle_button_guest_password_recovery_security').toggleButtons({
        onChange: function ($element, active, event) {
            const name = $element.context.name;
            const value = active;

            if (!value && !guest_password_recovery_email.checked) {
                manageConfirmation(name);
            } else{
                sendPostRequestGuestPasswordRecovery(name, value);
            }
        }
    });
    $('.toggle_button_ask_guest_retroactive_fields').toggleButtons({
        onChange: function ($element, active, event) {
            const value = active;
            sendPostAskGuestRetroactiveFields(value);
        }
    });

    $('.toggle_button_guest_password_recovery_email').toggleButtons({
        onChange: function ($element, active, event) {

            const name = $element.context.name;
            const value = active;
            if (!value && !guest_password_recovery_security.checked) {
                manageConfirmation(name);
            } else {
                sendPostRequestGuestPasswordRecovery(name, value);
            }
        }
    });

    const cancelButton = document.getElementById('cancelButton');
    const confirmButton = document.getElementById('confirmDisableButton');
    
    cancelButton.addEventListener('click', function (e) {
        cancelDisabledHandler(this.getAttribute('option'))
    });
    confirmButton.addEventListener('click', function (e) {
        confirmDisabledHandler(this.getAttribute('option'))
    });
});

$(document).click(function(){
    $('#selected-list').sortable({
        stop: function(event, ui){
            sortFields();
        }
    });
});

var avaibleTemplates = [];
var allTemplates = [];
var selectedFields = [];
var fieldsToLogin = [];
var messageSaveAlert = "*Você possui alterações não salvas!";

/**
 * Startup page
 */
renderTablesFromServer();

/**
 * Buttom actions
 */
$('.add-btn').live('click', function(event){
    event.preventDefault();
    var identifier = $(this).data('id');
    var selectedField = getField(avaibleTemplates, identifier);

    var isRemoved = removeField(avaibleTemplates, identifier);

    if(isRemoved) {
        selectedFields.push(selectedField);
    }
    renderSelectedList(selectedFields);
    renderTemplateList(avaibleTemplates);
    renderSaveAlert(messageSaveAlert);
});

$('.delete-field').live('click', function(event){
    event.preventDefault();
    let fieldId = $(this).data('id');
    let identifier = $(this).next('[data-id]').data('id');
    let isSelectedField = getField(avaibleTemplates, identifier);
    if (isSelectedField["saved"] == false) {
        alertMessage("Você só pode deletar o campo se salvar as alterações nos campos selecionados", "error")
        return false;
    }

    bootbox.confirm("Deseja realmente excluir?", function(result) {
        if (result) {
            var route = Routing.generate('custom_field_template_delete', { id: fieldId });
            $.ajax({
                type: "POST",
                url: route,
                dataType : "json",
                success: function(response)
                {
                    renderTablesFromServer();
                    alertMessage("Campo removido com sucesso", "success")
                },
                error: function(response)
                {
                    alertMessage("Houve um erro ao deletar registro, tente novamente mais tarde ou contate o suporte", "error")
                }
            });
        }
    });
});

$('.remove-btn').live('click', function(event){
    event.preventDefault();
    var identifier = $(this).data('id');
    var selField = getField(allTemplates, identifier);
    selField.saved = false;
    if (identifier === 'age_restriction') {
        return;
    }
    var isRemoved = removeField(selectedFields, identifier);

    if(isRemoved){
        avaibleTemplates.push(selField);
    } else {
        alertMessage("Não pode remover campo que é o login da aplicação.");
    }
    renderTemplateList(avaibleTemplates);
    renderSelectedList(selectedFields);
    renderSaveAlert(messageSaveAlert);
});

$('.unique-btn').live('change', function () {
    var identifier = $(this).data('id');

    if($(this).is(":checked")) {
        setUnique(identifier, true);
        setRequired(identifier, true);
    } else {
        if(isLoginField(identifier, selectedFields)){
            alertMessage("Este campo deve ser único pois ele é usado para o login do sistema.");
            renderSelectedList(selectedFields);
        } else {
            setUnique(identifier, false);
        }
    }
    renderSaveAlert(messageSaveAlert);
});

$('.on-access-btn').live('change', function () {
    var identifier = $(this).data('id');
    let value = $(this).val();
    setOnAccess(identifier, value);
    renderSaveAlert(messageSaveAlert);
});

$('.required-btn').live('change', function () {
    var identifier = $(this).data('id');

    if($(this).is(":checked")) {
        setRequired(identifier, true);
    } else {
        if(isLoginField(identifier, selectedFields)){
            alertMessage("Este campo deve ser obrigatório pois ele é usado para o login do sistema.");
            renderSelectedList(selectedFields);
            renderSaveAlert(messageSaveAlert)
        } else {
            setRequired(identifier, false);
            setUnique(identifier, false);
        }
    }
    renderSaveAlert(messageSaveAlert);
});

function saveFieldsAction() {
    saveSelected(selectedFields);
}

$('.btn-salvar').on('click', function (event) {
    event.preventDefault();
    saveFieldsAction();
});

function loginSelectAction(event) {
    var elementId = event.target.id;
    var inProgress = $("#" + elementId).data("process");
    if(!inProgress){
        var identifier = $("#" + elementId).data('id');
        saveLoginField(identifier, event);
    }

}

$('body').on('click', '.btn-login-select', function (event) {
    event.preventDefault();
    loginSelectAction(event);
});


/**
 *  Functions with model logics
 */
function getField(fields, identifier) {
    for (var i = 0 ; i < fields.length ; i++) {
        if (fields[i].identifier === identifier) {
            return fields[i];
        }
    }
    return null;
}

function removeField(fields, identifier) {
    for(var i = 0; i < fields.length; i++) {
        if(fields[i].identifier === identifier) {
            if (isLoginField(fields[i].identifier, fields)) {
                return false;
            }
            fields.splice(i, 1);
        }
    }
    return true;
}


function isRequired(field) {
    if (!field.validations) {
        return false;
    }
    for(var i = 0 ; i < field.validations.length ; i++) {
        var current = field.validations[i];
        if(current.type === 'required' && current.value === true) {
            return true
        }
    }
    return false;
}

function isLoginField(identifier, fields) {
    var field = getField(fields, identifier);
    return field.isLogin;
}

function hasRequiredValidation(field) {
    for(var i = 0 ; i < field.validations.length ; i++) {
        var current = field.validations[i];
        if(current.type === 'required') {
            return true
        }
    }
    return false;
}

function setUnique(identifier, value) {
    var field = getField(selectedFields, identifier);
    removeField(selectedFields,identifier);
    field.isUnique = value;
    selectedFields.push(field);
    renderSelectedList(selectedFields);
}

function setOnAccess(identifier, value) {
    var field = getField(selectedFields, identifier);
    removeField(selectedFields,identifier);
    field.onAccess = value;
    selectedFields.push(field);
    renderSelectedList(selectedFields);
}

function setRequired(identifier, value) {
    var field = getField(selectedFields, identifier);
    removeField(selectedFields, identifier);
    if (hasRequiredValidation(field)) {
        for(var i = 0 ; i < field.validations.length ; i++) {
            if(field.validations[i].type === 'required') {
                field.validations[i].value = value;
            }
        }
    } else {
        field.validations.push({
            type : "required",
            value : value,
            message : "wspot.signup_page.field_required",
            locale : [
                "pt_br",
                "en",
                "es"
            ]
        });
    }

    selectedFields.push(field);
    renderSelectedList(selectedFields);
}
function isAgeRestriction(identifier) {
    return identifier === "age_restriction";
}

function isDisable_password_authentication(){
    if (window.captiveType ==='disable_password_authentication')
        return  true;
}

function hasAgeRestrictionField(avaibleTemplates) {
    return selectedFields.some(function(field) {
        return field.identifier === 'age_restriction';
    });
}

function renderTablesFromServer() {
    $.ajax({
        url: Routing.generate('custom_fields_ajax_templates'),
        type:"GET",
        success: function (resp) {
            avaibleTemplates = resp.templates;
            selectedFields = resp.selecteds;
            allTemplates = resp.allTemplates;
            if (hasAgeRestrictionField(selectedFields)){
               avaibleTemplates = avaibleTemplates.filter(function(template) {
                    return template.identifier !== 'data_nascimento';
                });
            };

            renderTemplateList(avaibleTemplates);
            renderSelectedList(selectedFields);
        },
        error: function (resp) {

        }
    });

    $.ajax({
        url: Routing.generate('custom_fields_ajax_to_login'),
        type:"GET",
        success: function (resp) {
            fieldsToLogin = resp;
            renderLoginList(fieldsToLogin);
        },
        error: function (resp) {

        }
    });
}

function renderTemplateList(fields) {
    var bodyTemplate = $('#template-list');

    bodyTemplate.empty();
    let suportedTypes = ["date", "text", "choice", "multiple_choice"];

    fields.forEach(function(field) {
        if (field.visibleForClients.length == 1 && field.identifier && !field.identifier.includes('oldmambo') && suportedTypes.includes(field.type)) {
            var element = '<tr id="' + field.id + '">' +
            '<td>' + field.name.pt_br + '</td>' +
            '<td class="custom-field-list"><a href="#" data-id="' + field.id + '" class="btn delete-field"><i class="icon-remove"></i></a>' +
            '<a href="' + field.id + '/edit/" class="btn" data-id="' + field.identifier + '"><i class="icon-pencil"></i></a>' +
            '<a href="#" class="btn btn-success add-btn" data-id="' + field.identifier + '"><i class="icon-arrow-right icon-white"></i></a></td>' +
            '</tr>';
        } else {
            var element = '<tr id="' + field.id + '">' +
            '<td>' + field.name.pt_br + '</td>' +
            '<td class="custom-field-list"><a href="#" class="btn btn-success add-btn" data-id="' + field.identifier + '"><i class="icon-arrow-right icon-white"></i></a></td>' +
            '</tr>';
        }
        bodyTemplate.append(element);
    });
}

function renderSelectedList(fields) {
    fields.sort(function(a, b) {
        return a.position - b.position
    })
    var bodyTemplate = $("#selected-list");
    bodyTemplate.empty();

    fields.forEach(function(field) {
        var uniqueChecked = field.isUnique ? "checked" : "";
        var requiredChecked = isRequired(field) ? "checked" : "";
        var removeBtnDisabled = ""
        if (isAgeRestriction(field.identifier)) {
            requiredChecked = "checked";
            uniqueChecked = "disabled";
            var clickHandler = function(event) {
                event.preventDefault();  // Impede a ação padrão do clique
            };
            requiredChecked += ' onclick="return false;"';
            removeBtnDisabled = "disabled";
        }
        if (isDisable_password_authentication()){
            uniqueChecked = "disabled";
        }

        var element = "";
        if(isLoginField(field.identifier, selectedFields)) {
            element = '<tr style="background-color: #ecffcc; cursor: grabbing;">' +
                '<td class="left">&nbsp;<i class="fa fa-sort" aria-hidden="true"></i>&ensp;' + field.name.pt_br + '</td>' +
                '<td class="center" colspan="4">' +
                '   <span style="color: #507f46">Campo usado para login, não pode ser alterado.</span>' +
                '<input type="hidden" class="identification" value="' + field.identifier + '"/>' +
                '</td>' +
                '</tr>';
        } else {
            element = '<tr style="cursor: grabbing;">' +
                '<td class="left">&nbsp;<i class="fa fa-sort" aria-hidden="true"></i>&ensp;' + field.name.pt_br + '</td>' +
                '<td class="center"><input class="unique-btn" type="checkbox" name="unique-'+field.identifier+'" id="unique-'+field.identifier+'" ' + uniqueChecked + ' data-id= '+field.identifier+' /></td>' +
                '<td class="center"><input class="required-btn" type="checkbox" name="required-'+field.identifier+'" id="required-'+field.identifier+'" ' + requiredChecked + ' data-id= '+field.identifier+' /></td>' +
                '<td class="center">' +
                '<select class="on-access-btn span10" name="access-' + field.identifier + '" id="access-' + field.identifier + '" data-id="' + field.identifier + '">' +
                    '<option value="1" ' + (field.onAccess === null || field.onAccess == 1 ? 'selected' : '') + '>1ª visita</option>' +
                    '<option value="2" ' + (field.onAccess == 2 ? 'selected' : '') + '>2ª visita</option>' +
                    '<option value="3" ' + (field.onAccess == 3 ? 'selected' : '') + '>3ª visita</option>' +
                '</select>' +
                '</td>' +
                '<td class="center"><a href="#" class="btn btn-primary remove-btn" data-id="' + field.identifier + '" ' + removeBtnDisabled + '><i class="icon-trash icon-white"></i></a>' +
                '<input type="hidden" class="identification" value="' + field.identifier + '" /></td></td>' +
                '</tr>';
        }

        bodyTemplate.append(element);
    });
}

function renderLoginList(fieldsToLogin) {
    var bodyTemplate = $('#login-list');
    bodyTemplate.empty();

    var fields = [];
    fields = fieldsToLogin.fields;

    fields.forEach(function(field) {
        var element = "";
        if (field.isLogin) {
            element = '<tr id="' + field.id + '" style="background-color: #ecffcc">' +
                '<td class="center">' + field.name.pt_br + '</td>' +
                '<td class="center"><span style="color: #507f46; font-weight: bolder">Selecionado</span></td>' +
                '</tr>';
        } else {
            element = '<tr id="' + field.id + '">' +
                '<td class="center">' + field.name.pt_br + '</td>' +
                '<td class="center"><a href="#" class="btn btn-primary btn-login-select" id="btn-login-select-' + field.identifier + '" data-id="' + field.identifier + '">Selecionar</a></td>' +
                '</tr>';
        }
        bodyTemplate.append(element);
    });
}

function renderSaveAlert(message) {
    $(".save-alert").html(message);
}

function alertMessage(message, type) {
    var color = "green";
    switch (type){
        case "error":
            color = "red";
            message = "[ERRO] " + message;
            break;
        case "success":
            color = "green";
            break;
        default:
    }

    var dialog = bootbox.dialog("" +
        "<div class='modal-footer center'>" +
        "<p style='text-align:center; color: " + color + ";font-size: 11pt;'>" + message + "</p>" +
        "<a href='#' class='btn btn-primary modal-close'>Fechar</a>" +
        "</div>");
    return dialog.modal.bind(dialog);
}

function saveSelected(fields) {
    lockSaveButtom('.btn-salvar', "Salvando, aguarde por favor.", "click");
    $.ajax({
        url: Routing.generate('custom_fields_ajax_save'),
        type: 'POST',
        contentType: "application/json",
        dataType:'json',
        data: JSON.stringify(fields),
        success: function (resp) {
            alertMessage(resp.message);
            renderTablesFromServer();
            renderSaveAlert("");
            unlockSaveButtom('.btn-salvar', saveFieldsAction, "Salvar", "click");
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);
            alertMessage(response.message, "error")
            renderTablesFromServer();
            unlockSaveButtom('.btn-salvar', saveFieldsAction, "Salvar", "click");
        }
    });
}

function sortFields() {
    var aux = [], fieldsToSave = [];

    $(".identification").each(function() {
        aux.push($(this).val());
    });

    aux.forEach(function(identifier) {
        setData(identifier, selectedFields, fieldsToSave);
    });

    selectedFields = fieldsToSave;
}

function setData(identifier, fields, fieldsToSave) {
    fields.forEach(function(field) {
        if (field.identifier == identifier) {
            fieldsToSave.push(field);
            return false;
        }
    });
}

function lockSaveButtom(btnSelector, label, event, softBlock) {

    if(softBlock) {
        $("body").off(event, softBlock);
        $(softBlock).off(event);
        $(softBlock).data('process', true);
    }

    $(btnSelector).html(label);
    $(btnSelector).addClass('disabled');
    $("body").off(event, btnSelector);
    $(btnSelector).off(event);
}

function unlockSaveButtom(btnSelector, actionFunction, label, event, softUnblock) {
    $(btnSelector).html(label);
    $(btnSelector).removeClass('disabled');
    $('body').on(event, btnSelector,actionFunction);

    if(softUnblock) {
        $('body').on(event, softUnblock, actionFunction);
        $(softUnblock).data('process', false);
    }
}

function saveLoginField(identifier, event) {

    var idButtom = "#" + event.target.id;
    $.ajax({
        url: Routing.generate('custom_fields_ajax_save_to_login'),
        type: 'POST',
        contentType: "application/json",
        dataType:'json',
        data: JSON.stringify({identifier:identifier}),
        success: function (resp) {
            renderTablesFromServer();
            unlockSaveButtom(idButtom, loginSelectAction, "Selecionar", "click", '.btn-login-select');
        },
        error: function (data) {
            var response = JSON.parse(data.responseText);
            alertMessage(response.message, "error")
            renderTablesFromServer();
            unlockSaveButtom('.btn-login-select', loginSelectAction, "Selecionar", "click", '.btn-login-select');
        },
        beforeSend: function () {
            lockSaveButtom(idButtom, "Salvando aguarde...", "click", '.btn-login-select');
        }
    });
}


/* Início da lógica Guest Password Recovery */


function cancelDisabledHandler(name) {
    $(`.toggle_button_${name}`).toggleButtons('setState', true, true);
    document.getElementById('popup-desativada').style.display = 'none';
}

function confirmDisabledHandler(name) {
    document.getElementById('popup-desativada').style.display = 'none';
    sendPostRequestGuestPasswordRecovery(name, false);
}

function manageConfirmation(name){
    document.getElementById('popup-desativada').style.display = 'block';
    const confirmButton = document.getElementById('confirmDisableButton')
    confirmButton.setAttribute('option', name);
    const cancelButton = document.getElementById('cancelButton')
    cancelButton.setAttribute('option', name);
    return false

}

function sendPostAskGuestRetroactiveFields(value) {
    const endpoint = Routing.generate('ask_retroactive_fields');
    const data = { value };
    const options = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    };

    fetch(endpoint, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao enviar POST para o endpoint:', endpoint);
            }

            return response.json();
        })
        .then(data => {
            $.gritter.add({
                title: 'Aviso!',
                text: `Solicitação retroativa de campos ${value ? 'ativada' : 'desativada'}`,
                time: 2000,
            });
        })
        .catch(error => {
            console.error('Erro ao processar requisição:', error);
        });
}

function sendPostRequestGuestPasswordRecovery(name, value) {

    const endpoint = Routing.generate('guest_password_recovery');
    const data = { name, value };
    const options = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    };

    fetch(endpoint, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao enviar POST para o endpoint:', endpoint);
            }
            return response.json();
        })
        .then(data => {
            const recoveryType = name == 'guest_password_recovery_email' ? 'email' : name == 'guest_password_recovery_security' ? 'pergunta secreta' : ''; 
            $.gritter.add({
                title: 'Aviso!',
                text: `Redefinição de senha via ${recoveryType} ${value ? 'ativada' : 'desativada'}`,
                time: 2000,
            });
        })
        .catch(error => {
            console.error('Erro ao processar requisição:', error);
        });
}


function openPopup(popupId) {
    document.getElementById(popupId).style.display = 'block';
}

document.querySelector('.saiba-mais-pergunta').addEventListener('click', function(event) {
    if (!this.classList.contains('disabled-link')) {
        openPopup('popup-pergunta');
    }
    event.preventDefault();
});

document.querySelector('.close-pergunta').addEventListener('click', function() {
    document.getElementById('popup-pergunta').style.display = 'none';
});

document.querySelector('.saiba-mais-email').addEventListener('click', function(event) {
    if (!this.classList.contains('disabled-link')) {
        openPopup('popup-email');
    }
    event.preventDefault();
});

document.querySelector('.close-email').addEventListener('click', function() {
    document.getElementById('popup-email').style.display = 'none';
});

/* Fim da lógica Guest Password Recovery */