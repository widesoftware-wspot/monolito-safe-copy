//////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// Access Points selection Pill Container ///////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////
var TYPE_AP = 'ap';
var TYPE_GROUP = 'group';

var pillStore = [];
var apListApi = [];

$(document).ready(function () {
    var manualChkBox = $('#manualChkBox');
    var autoChkBox = $('#autoChkBox');
    var btnAddApGroup = $('#btnAddApGroup');
    var cleanBtnApGroup = $('#cleanBtnApGroup');
    var checkBoxGroup = $('#checkBoxGroup');
    var requiredFieldLabel = '<label id="checkbox-error" class="error" for="wspot_group_form_name">Campo obrigatório, por favor selecione uma opção</label>';
    var submitBtn = $('#wspot_group_form_submit');

    //submitBtn.prop('disabled', true);

    $(manualChkBox).on('click',function () {
        $('#no-aps-selected').remove();
        $('#checkbox-error').remove();
        if (manualChkBox.attr('checked')) {
            $('#checkbox-error').remove();
            submitBtn.prop('disabled', false);
        } else {
            submitBtn.prop('disabled', true);
            checkBoxGroup.append(requiredFieldLabel);
        }
        autoChkBox.prop('checked', false);
        btnAddApGroup.css('visibility', 'hidden');
        cleanBtnApGroup.css('visibility', 'hidden');
        clearSelected();
    });

    $(autoChkBox).on('click',function () {
        $('#checkbox-error').remove();
        manualChkBox.prop('checked', false);
        if (autoChkBox.attr('checked')) {
            btnAddApGroup.css('visibility', 'visible');
            cleanBtnApGroup.css('visibility', 'visible');
            $('#checkbox-error').remove();
        } else {
            btnAddApGroup.css('visibility', 'hidden');
            cleanBtnApGroup.css('visibility', 'hidden');
            checkBoxGroup.append(requiredFieldLabel);
            clearSelected();
        }
    });

    $('body').bind("DOMSubtreeModified",function(){
        var apOrGroupSelectedLength = $("#wideti_AdminBundle_guest_group_apsAndGroups").val().length;
        var noneApOrGroupSelected = 2;
        if (apOrGroupSelectedLength == noneApOrGroupSelected) {
            if (manualChkBox.attr('checked')) {
                submitBtn.prop('disabled', false);
            } else {
                submitBtn.prop('disabled', true);
            }
        } else {
            $('#checkbox-error').remove();
            submitBtn.prop('disabled', false);
        }
    });
});

function clearSelected() {
    var itemsToRemove = $('#wideti_AdminBundle_guest_group_apsAndGroups').val();
    var items = JSON.parse(itemsToRemove);
    var i;
    if (itemsToRemove.length > 2) {
        for (i = 0; i <= items.length; i++) {
            removePillFromStore(items[i].id, items[i].type);
        }
    }

}

function createLineInSearchList(pill, hasInStore) {
    var line = $('<tr></tr>');

    if (hasInStore) {
        line.addClass('ap-selected-line');
    }

    var columnName = $('<td class="center"></td>').html(pill.name);
    var columnType = $('<td class="center"></td>').html(pill.type === TYPE_AP ? "Ponto de acesso" : "Grupo");
    var columnAction = $('<td class="center"></td>').html(
        hasInStore
            ? createRemoveButtonSearchList(pill.id, pill.type)
            : createAddButtonSearchList(pill.id, pill.type)
    );

    line.append([columnName, columnType, columnAction]);

    return line;
}

function createRemoveButtonElement(id, type) {
    var btn = $('<a></a>')
        .attr({href: "#", onclick: "removePillFromStore(" + id + ", \'" + type + "\'); return false;"})
        .addClass('ap-list-remove-btn')
        .html('x');

    if (type === TYPE_AP) {
        btn.addClass('ap-remove-btn-style')
    } else {
        btn.addClass('group-remove-btn-style')
    }
    return btn;
}

function createAddButtonElement(id, type) {
    return $('<a></a>')
        .attr({href: "#", onclick: "addPillToStore(" + id + ", \'" + type + "\'); return false;"})
        .addClass('ap-list-add-btn')
        .html('+');
}

function createRemoveButtonSearchList(id, type) {
    return $('<a></a>')
        .attr({href: "#", onclick: "removePillFromStore(" + id + ", \'" + type + "\'); return false;"})
        .addClass('ap-search-list-remove-button')
        .html('Remover');
}

function createAddButtonSearchList(id, type) {
    return $('<a></a>')
        .attr({href: "#", onclick: "addPillToStore(" + id + ", \'" + type + "\'); return false;"})
        .addClass('ap-search-list-add-button')
        .html('Adicionar');
}

function createPillBox(pill) {
    var pillBox = $('<li></li>');

    if (!pill) {
        pillBox = $('<li id="no-aps-selected"></li>');
        pillBox
            .append('<span id="noApsOrGroupsLabel">Nenhum ponto ou grupo selecionado</span> ');
        pillBox.addClass('all-ap-pill-box')
        return pillBox;
    }

    if (pill.type === TYPE_AP) {
        pillBox.addClass('ap-pill-box');
        pillBox
            .append('<span>' + pill.name + '</span> ')
            .append(createRemoveButtonElement(pill.id, pill.type))
            .append('<span class="pill-ap-type-title">Ponto de acesso</span>');
    }

    if (pill.type === TYPE_GROUP) {
        pillBox.addClass('ap-group-pill-box');
        pillBox
            .append('<span>' + pill.name + '</span> ')
            .append(createRemoveButtonElement(pill.id, pill.type))
            .append('<span class="pill-group-type-title">Grupo</span>');
    }

    return pillBox;
}

function removePillFromStore(id, type) {
    for (var i=0; i < pillStore.length; i++) {
        if (id === pillStore[i].id && type === pillStore[i].type) {
            pillStore.splice(i,1);
        }
    }
    renderAllView();
}

function addPillToStore(id, type) {
    var pill = getApOrGroupByIdIn(id, type,  apListApi);
    if (!existsInStore(pill)) {
        pillStore.push(pill);
    }

    renderAllView();
}

function renderPillContainer() {
    var pillContainer = $('#ap-pills-container');
    var apsAndGroupsField = $('#wideti_AdminBundle_guest_group_apsAndGroups');

    pillContainer.html("");
    apsAndGroupsField.val('');

    if (pillStore.length === 0) {
        pillContainer.append(createPillBox(null));
    }

    for (var i = 0; i < pillStore.length; i++) {
        var pill = pillStore[i];
        var pillBox = createPillBox(pill);
        pillContainer.append(pillBox);
    }

    apsAndGroupsField.val(JSON.stringify(pillStore));
}

function renderSearchList() {
    var searchListContainer = $('#ap-search-container');
    searchListContainer.html('');

    if (apListApi.length === 0) {
        searchListContainer.append('<tr><td colspan="3"><p style="text-align: center; color: #74726f;margin-top: 10px">"0" resultados, use o campo de pesquisa acima, para buscar.<p></td></tr>')
    }

    for (var i=0 ; i < apListApi.length ; i++) {
        var ap = apListApi[i];
        var hasInStore = existsInStore(ap);
        var item = createLineInSearchList(ap, hasInStore);
        searchListContainer.append(item);
    }
}

function renderAllView() {
    renderPillContainer();
    renderSearchList();
}

function existsInStore(pill) {
    for (var i = 0; i < pillStore.length; i++) {
        if (pill.id === pillStore[i].id && pill.type === pillStore[i].type) {
            return true;
        }
    }
    return false;
}

function getApOrGroupByIdIn(id, type, list) {
    for (var i = 0; i < list.length; i++) {
        if (id === list[i].id && type === list[i].type) {
            return list[i];
        }
    }
    return null;
}


function clearStore() {
    pillStore = [];
    renderAllView();
}
function clearStore2() {
    pillStore = [];
}

function selectAllInApiList() {
    for (var i = 0; i < apListApi.length; i++) {
        addPillToStore(apListApi[i].id, apListApi[i].type);
    }
    renderAllView();
}

function getApiAccessPointAndGroups(searchText, cb, apIdsThatAreBeingUsed, groupsThatAreBeingUsed) {
    var route = Routing.generate('access_points_get_aps_and_groups_select_box', {name: searchText, apIds: apIdsThatAreBeingUsed, groupIds: groupsThatAreBeingUsed});
    $.ajax({
        type: "GET",
        url: route,
        dataType : "json",
        success: function(response)
        {
            cb(response, null);
        },
        error: function (error) {
            cb(null, error);
        }
    });
}

function loadApListApi(searchText, apIdsThatAreBeingUsed, groupsThatAreBeingUsed) {
    getApiAccessPointAndGroups(searchText, function(result, error){
        if (error) {
            console.log(error)
        }
        apListApi = result;
        renderAllView()
    }, apIdsThatAreBeingUsed, groupsThatAreBeingUsed);
}

function loadApAndGroupListOnUpdade(guestGroupId, cb) {
    var route = Routing.generate('access_points_and_groups_get_by_guest_group_id', {id: guestGroupId});
    $.ajax({
        type: "GET",
        url: route,
        dataType : "json",
        success: function(response)
        {
            cb(response, null);
        },
        error: function (error) {
            cb(null, error);
        }
    });
}

function onKeyPressSearchInput(event, apIdsThatAreBeingUsed, groupsThatAreBeingUsed) {
    var searchText = event.value;
    loadApListApi(searchText, apIdsThatAreBeingUsed, groupsThatAreBeingUsed);
}

