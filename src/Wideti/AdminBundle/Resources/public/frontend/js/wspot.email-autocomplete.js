function emailAutoCompleteDomain(obj){
    var domains = [
        'hotmail.com',
        'bol.com.br',
        'outlook.com',
        'gmail.com',
        'yahoo.com.br',
        'yahoo.com',
        'icloud.com',
        'uol.com.br',
        'terra.com.br',
        'msn.com.br',
        'globomail.com',
        'globo.com',
        'ig.com',
        'r7.com.br'
    ];

    $.each(obj, function(key, value) {
        new Awesomplete(value, {
            list: domains,
            data: function (text, input) {
                return input.slice(0, input.indexOf("@")) + "@" + text;
            },
            filter: Awesomplete.FILTER_STARTSWITH
        });
    });
}