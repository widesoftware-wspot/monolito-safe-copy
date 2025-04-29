function loadReportFiles(folder) {
    $('.files').empty();
    var route = Routing.generate('admin_available_reports_s3');
    var data  = {
        'folder': folder
    };

    $.ajax({
        type: "POST",
        url: route,
        data: data,
        dataType : "json",
        success: function(response)
        {
            $("#listExport").css('display','none');
                $.each(response, function () {
                    if(this.filename != ''){
                        $("#listExport").css('display','block');
                        $('.files').append('<tr id="'+this.filename+'"><td>'+this.filename+'</td><td class="url"><a style="cursor: pointer"  onclick="loadReportSignedUrl(\''+data.folder+'\', \''+this.filename+'\')" target="_blank">Download do arquivo</a></td></tr>')
                    }
                });
            },
        error: function (request, status, error) {
            console.log('erro');
            $('.files').append('<tr><td colspan="2">Nenhum relatório disponível para download.</td></tr>')
        }
    });
}

function loadReportSignedUrl(folder, filename) {
    var route = Routing.generate('admin_signed_url_reports_s3');
    var data  = {
        'folder': folder,
        'filename': filename
    };

    $.ajax({
        type: "POST",
        url: route,
        data: data,
        dataType : "json",
        success: function(response)
        {
            window.open(response.url)
            $("#listExport").css('display','block');
        },
        error: function (request, status, error) {
            console.log('erro');
            $('.files').append('<tr><td colspan="2">Nenhum relatório disponível para download.</td></tr>')
        }
    });
}
