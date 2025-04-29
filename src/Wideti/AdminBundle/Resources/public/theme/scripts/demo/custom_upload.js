var customScripts = function () {

    var uploadDesktopImage = function () {
        jQuery('#imageDesktopSubmit').on('click', function (e) {
            e.preventDefault();

            var campaignId  = $('#campaignId').val();
            var step        = $('#step').val();
            var data        = new FormData();
            var url         = Routing.generate('campaign_media_upload', { id: campaignId, step: step, mediaType: 'image' });
            var timer       = jQuery('#campaign_step_media_image_exhibitionTime').val();

            if (timer == 0) {
                timer = 5;
                jQuery('#campaign_step_media_image_exhibitionTime').val(timer);
            } else {
                $('#exhibitionTime').hide();
            }

            var inputImageDesktop = document.getElementById('imageDesktop');
            var inputImageDesktop2 = document.getElementById('imageDesktop2');
            var inputImageDesktop3 = document.getElementById('imageDesktop3');

                    
            // Adicionar arquivo do input com ID 'campaign_step_media_image_imageMobile'
            if (inputImageDesktop.files.length > 0) {
                var file = inputImageDesktop.files[0]; // Acessa o primeiro arquivo
                data.append('image1', file, file.name); // Adiciona o arquivo ao FormData
            }

            // Adicionar arquivo do input com ID 'campaign_step_media_image_imageMobile2'
            if (inputImageDesktop2.files.length > 0) {
                var file = inputImageDesktop2.files[0]; // Acessa o primeiro arquivo
                data.append('image2', file, file.name); // Adiciona o arquivo ao FormData
            }

            // Adicionar arquivo do input com ID 'campaign_step_media_image_imageMobile3'
            if (inputImageDesktop3.files.length > 0) {
                var file = inputImageDesktop3.files[0]; // Acessa o primeiro arquivo
                data.append('image3', file, file.name); // Adiciona o arquivo ao FormData
            }






            // var originalFileNameD = fileInputD.files[0].name;

            // cropperDesktop.getCroppedCanvas().toBlob(function(blob) {
                // data.append('file', blob, originalFileNameD); // Adiciona o blob ao FormData
                data.append('timer', timer);
                data.append('type', 'desktop');
                // Exibir o conteúdo do FormData para depuração
                let formDataObject = {};
                data.forEach(function (value, key) {
                    if (!formDataObject[key]) {
                        formDataObject[key] = [];
                    }
                    formDataObject[key].push(value);
                });

                $.ajax({
                    type: "POST",
                    url: url,
                    data: data,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        $(".loader-post.desktop").show();
                        $('#imageDesktopError').hide();
                        $('#imageModalDesktop').hide();
                        $('#imageDesktopWait').show();
                        $('#imageDesktopSubmit').hide();
                        $('#campaign_step_media_image_submit').hide();
                    },
                    success: function (response) {
                        if (response.message) {
                            $('#imageModalDesktop').hide();
                            $('#imageDesktopError').show();
                            $('#imageDesktopError span.label-warning').text(response.message);
                            $('#imageDesktopWait').hide();
                        } else {
                            $('#imageDesktopError').hide();
                        }

                        if (response.message == 'Upload realizado com sucesso') {
                            $('#imageModalDesktop').hide();
                            $('.imageDesktop.error').hide();
                            $('#imageDesktopName').css('display', 'block');
                            // $('#imageDesktopNameText').text(response.originalFileName); // Atualiza o texto do span


                            // Limpa a lista existente de nomes de arquivos
                            let ul = $('#uploadedImageNamesDesktop');
                            ul.empty(); // Limpa a lista existente

                            // Adiciona cada nome de arquivo se estiver definido
                            if (response.originalFileName) {
                                ul.append(
                                    '<li>' +
                                    response.originalFileName +
                                    '<span style="font-size: 30px; color: #4CAF50; line-height: 1;">&#10003;</span>' +
                                    '</li>'
                                );
                            }

                            if (response.originalFileName2) {
                                ul.append(
                                    '<li>' +
                                    response.originalFileName2 +
                                    '<span style="font-size: 30px; color: #4CAF50; line-height: 1;">&#10003;</span>' +
                                    '</li>'
                                );
                            }

                            if (response.originalFileName3) {
                                ul.append(
                                    '<li>' +
                                    response.originalFileName3 +
                                    '<span style="font-size: 30px; color: #4CAF50; line-height: 1;">&#10003;</span>' +
                                    '</li>'
                                );
                            }


                           $('#previewButtonFinalDesktop').show(); // Mostra o botão de Preview

                            $.gritter.add({
                                title: 'Aviso!',
                                text: response.message
                            });

                            $('#imageDesktopError span.label-warning').hide()
                            $('#campaign_step_media_image_submit').show();
                        }

                        $(".fileinput-exists").trigger('click');
                        $('.loader-post.desktop').hide();
                        $('a.btn.circle_ok').show();
                        $('.imageBox, #removeFile').show();
                        $('#campaign_step_media_image_imageDesktop').val(response.fileName);
                        $('#campaign_step_media_image_imageDesktop2').val(response.fileName2);
                        $('#campaign_step_media_image_imageDesktop3').val(response.fileName3);

                        $('#landscape-image').attr("src", ($("#imagePath").val() + response.fileName));
                        $('#enviar-carrosel-desktop').text('Alterar Imagens');
                        $('#imageDesktopSubmit').show();

                        // Fecha o modal após o sucesso
                        $('#modal-enviar-imagem-desktop').modal('hide');
                    }
                });
            // });
        });
    };

var uploadMobileImages = function () {
    jQuery('#imageMobileSubmit').on('click', function (e) {
        e.preventDefault();
        var campaignId = $('#campaignId').val();
        var step = $('#step').val();
        var timer = jQuery('#campaign_step_media_image_exhibitionTime').val();
        var url = Routing.generate('campaign_media_upload', { id: campaignId, step: step, mediaType: 'image' });

        if (timer == 0) {
            timer = 5;
            jQuery('#campaign_step_media_image_exhibitionTime').val(timer);
        } else {
            $('#exhibitionTime').hide();
        }

        var data = new FormData();

        // Adicionar arquivo do input com ID 'campaign_step_media_image_imageMobile'
        var inputImageMobile = document.getElementById('imageMobile');
        if (inputImageMobile.files.length > 0) {
            var file = inputImageMobile.files[0]; // Acessa o primeiro arquivo
            data.append('image1', file, file.name); // Adiciona o arquivo ao FormData
        }

        // Adicionar arquivo do input com ID 'campaign_step_media_image_imageMobile2'
        var inputImageMobile2 = document.getElementById('imageMobile2');
        if (inputImageMobile2.files.length > 0) {
            var file = inputImageMobile2.files[0]; // Acessa o primeiro arquivo
            data.append('image2', file, file.name); // Adiciona o arquivo ao FormData
        }

        // Adicionar arquivo do input com ID 'campaign_step_media_image_imageMobile3'
        var inputImageMobile3 = document.getElementById('imageMobile3');
        if (inputImageMobile3.files.length > 0) {
            var file = inputImageMobile3.files[0]; // Acessa o primeiro arquivo
            data.append('image3', file, file.name); // Adiciona o arquivo ao FormData
        }
        data.append('timer', timer);
        data.append('type', 'mobile');

        // Exibir o conteúdo do FormData para depuração
        let formDataObject = {};
        data.forEach(function (value, key) {
            if (!formDataObject[key]) {
                formDataObject[key] = [];
            }
            formDataObject[key].push(value);
        });

        $.ajax({
            type: "POST",
            url: url,
            data: data,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $(".loader-post.mobile").show();
                $('#imageMobileError').hide();
                $('#imageModal').hide();
                $('#imageMobileWait').show();
                $('#imageMobileSubmit').hide();
                $('#campaign_step_media_image_submit').hide();
            },
            success: function (response) {
                if (response.message) {
                    $('#imageModal').hide();
                    $('#imageMobileError').show();
                    $('#imageMobileError span.label-warning').text(response.message);
                    $('#imageMobileWait').hide();
                } else {
                    $('#imageMobileError').hide();
                }

                if (response.message === 'Upload realizado com sucesso') {
                    $('#imageModal').hide();
                    $('#imageMobileError').hide();
                    $('#imageMobileName').css('display', 'block');
                    // $('#imageMobileNameText').text(response.originalFileName); // Atualiza o texto do span

                    // Limpa a lista existente de nomes de arquivos
                    let ul = $('#uploadedImageNames');
                    ul.empty(); // Limpa a lista existente

                // Adiciona cada nome de arquivo se estiver definido
                if (response.originalFileName) {
                    ul.append(
                        '<li>' +
                        response.originalFileName +
                        '<span style="font-size: 30px; color: #4CAF50; line-height: 1;">&#10003;</span>' +
                        '</li>'
                    );
                }

                if (response.originalFileName2) {
                    ul.append(
                        '<li>' +
                        response.originalFileName2 +
                        '<span style="font-size: 30px; color: #4CAF50; line-height: 1;">&#10003;</span>' +
                        '</li>'
                    );
                }

                if (response.originalFileName3) {
                    ul.append(
                        '<li>' +
                        response.originalFileName3 +
                        '<span style="font-size: 30px; color: #4CAF50; line-height: 1;">&#10003;</span>' +
                        '</li>'
                    );
                }


                    $('#previewButtonFinal').show(); // Mostra o botão de Preview

                    $.gritter.add({
                        title: 'Aviso!',
                        text: response.message
                    });

                    $('.imageMobile.error').hide();
                    $('#campaign_step_media_image_submit').show();
                }

                $(".fileinput-exists").trigger('click');
                $('.loader-post.mobile').hide();
                $('a.btn.circle_ok').show();
                $('.imageBox, #removeFile').show();

                // Atualiza os caminhos das imagens
                    $('#campaign_step_media_image_imageMobile').val(response.fileName);
                    $('#campaign_step_media_image_imageMobile2').val(response.fileName2);
                    $('#campaign_step_media_image_imageMobile3').val(response.fileName3);
                 $('#imageMobileSubmit').show();

                    $('#enviar-carrosel').text('Alterar Imagens');

                    $('#landscape-image').attr("src", $("#imagePath").val() + response.fileNames);

                // Fecha o modal após o sucesso
                $('#modal-enviar-imagem').modal('hide');
            }
        });
    });
};


    var uploadVideo = function () {
        jQuery('#uploadFileSubmit').on('click', function (e) {
            e.preventDefault();

            var campaignId = $('#campaignId').val();
            var step = $('#step').val();
            var orientation = $('#campaign_step_media_video_orientation').val();

            var data = new FormData();
            var url = Routing.generate(
                'campaign_media_upload',
                {
                    id: campaignId,
                    step: step,
                    mediaType: 'video',
                    orientation: orientation
                }
            );

            data.append('file', document.getElementById('videoFile').files[0]);

            $.ajax({
                type: "POST",
                url: url,
                data: data,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $(".loader-post").show();
                    $('#uploadFileError').hide();
                    $('#uploadFileWait').show();
                    $('#uploadFileSubmit').hide();
                    $('#campaign_step_media_video_submit').hide();
                },
                success: function (response) {
                    if (response.message) {
                        $('#uploadFileError').show();
                        $('#uploadFileError span.label-warning').text(response.message);
                        $('#uploadFileWait').hide();
                    } else {
                        $('#uploadFileError').hide();
                    }

                    if (response.message == 'Upload realizado com sucesso') {
                        $.gritter.add({
                            title: 'Aviso!',
                            text: response.message
                        });

                        $('.uploadError.error').hide();
                        $('#campaign_step_media_video_submit').show();
                    }

                    $(".fileinput-exists").trigger('click');
                    $('.loader-post').hide();
                    $('a.btn.circle_ok').show();
                }
            });
        });
    };

    return {
        init: function () {
            uploadDesktopImage();
            uploadMobileImages();
            uploadVideo();
        }
    }
}();
