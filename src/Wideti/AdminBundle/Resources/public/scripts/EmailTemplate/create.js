/**
 * Page Events
 */
Pace.on('?start', function () {
    $(".loader").removeClass("hide");
});

Pace.on("done", function () {
    $(".loader").addClass("hide");
});

window.onload = function () {
    EmailTemplate.init();
    EmailTemplate.Editor.init();
};

var EmailTemplate = {

    init: function () {
        if ($('.container-fluid:first').is('.menu-hidden') === false) {
            toggleMenuHidden();
        }
    }

    /**
     * Take cares only of the editor creation
     */
    ,Editor: {

        editor_id: ""
        ,variables : []

        ,init: function () {
            this.preLoadVariables();
        }

        ,preLoadVariables: function () {
            this.variables = this.getData();
        }

        ,create: function () {
            $(".ck-editor").each(function(i, item) {
                EmailTemplate.Editor.editor_id = item.id;
                EmailTemplate.Editor.loadEditor();
            });
        }

        ,getData: function () {
            $.ajax({
                async: false,
                type: "POST",
                url: Routing.generate('email_template_load_variables'),
                dataType : "json",
                success: function(response)
                {
                    EmailTemplate.Editor.variables = response;
                    EmailTemplate.Editor.create();
                }
            });
        }

        ,loadEditor: function() {
            CKEDITOR.replace(this.editor_id, {
                extraPlugins: 'justify,colorbutton',
                availableTokens: this.variables,
                tokenStart: '*|',
                tokenEnd: '|*',
                filebrowserUploadUrl: Routing.generate('email_template_upload_image')
            });

            this.configureEditor();
            this.livePreview();
        }

        ,configureEditor: function () {

            var buttons = [
                "Source",
                "Undo",
                "Redo",
                "Cut",
                "Copy",
                "Paste",
                "PasteText",
                "PasteFromWord",
                "Scayt",
                "Strike",
                "Subscript",
                "Superscript",
                "NumberedList",
                "BulletedList",
                "Outdent",
                "Indent",
                "Blockquote",
                "Anchor",
                "Table",
                "HorizontalRule",
                "SpecialChar",
                "Maximize",
                "About"
            ];

            CKEDITOR.config.removeButtons = buttons.join();

            CKEDITOR.on( 'dialogDefinition', function( ev )
            {
                var dialogName                  = ev.data.name;
                var dialogDefinition            = ev.data.definition;
                ev.data.definition.resizable    = CKEDITOR.DIALOG_RESIZE_NONE;

                if ( dialogName == 'link' ) {
                    var infoTab = dialogDefinition.getContents( 'info' );
                    infoTab.remove( 'protocol' );
                    dialogDefinition.removeContents( 'target' );
                    dialogDefinition.removeContents( 'advanced' );
                    return;
                }

                if ( dialogName == 'image' ) {
                    dialogDefinition.removeContents( 'Link' );
                    dialogDefinition.removeContents( 'advanced' );
                    dialogDefinition.minHeight = 100;

                    var infoTab = dialogDefinition.getContents( 'info' );
                    infoTab.remove( 'txtBorder' );
                    infoTab.remove( 'txtHSpace' );
                    infoTab.remove( 'txtVSpace' );
                    infoTab.remove( 'cmbAlign' );
                    infoTab.remove( 'htmlPreview' );
                }

            });
        }

        ,livePreview: function () {
            CKEDITOR.on('instanceReady', function (e) {
                document.getElementById(e.editor.name + '_preview').innerHTML = e.editor.getData();

                e.editor.on('change', function (ev) {
                    document.getElementById(ev.editor.name + '_preview').innerHTML = ev.editor.getData();
                });
            });
        }

    }
};
