/**
 * Este script percorre todos os forms da pagina e inicia a validação que é baseada nos atributos globais
 * "data-rules-*" e "data-msg-*", após isso percorre os fields do form e adiciona mascara em todos os campos
 * com o atributo "data-field-mask"
 *
 * @dependancy Jquery , jquery.validate.min.js , jquery.maskedinput.js , wspot.custom-validate-rules.js
 */

var maskWildcards = {
    'translation':
    {
        A:{
            pattern:/[A-Za-z]/
        },
        F:{
            pattern:/[A-Za-z0-9]/
        }
    }
};

$('form').each(function () {
    var formId  = "#" + $(this).attr('id');
    var formObj = $(this);

    formObj.validate({
        submitHandler: function(form){
            form.submit();
            startBtnLoaderGif(formId);
        }
    });

    formObj.find(':input').each(function () {
        var mask = $(this).attr('data-field-mask');
        if(mask){
            $(this).mask(mask,maskWildcards);
        }
    });
});

function startBtnLoaderGif(parentFormId){
    var submitButton = $(parentFormId + " :submit");
    var loaderImage  = $(parentFormId + " > .btnLoader");

    if(loaderImage.length > 0){
        submitButton.hide();
        loaderImage.show();
    }
}