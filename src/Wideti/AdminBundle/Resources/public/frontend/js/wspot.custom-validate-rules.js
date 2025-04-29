$.validator.addMethod("customDate", function(value, element, options) {
    if (!options.required && !value) return true;
    return value.match(/^(0?[1-9]|[12][0-9]|3[0-1])[/., -](0?[1-9]|1[0-2])[/., -](19|20)?\d{2}$/);
    }
);


$.validator.addMethod("AgeRestriction", function(value, element, options) {
    // Seleciona e apaga mensagem de erro exibida pela validação via server
    // antes de exibir o erro via front para evitar duplicidade.
    let elementoLabel = document.querySelector('.label');
    if (elementoLabel !== null){
        elementoLabel.textContent = '';
        elementoLabel.style.display = 'none'; 
    }

    if (!options.required && !value) return true;
    let birthDate = new Date(value.replace(/(\d{2})\/(\d{2})\/(\d{4})/, "$2/$1/$3"));
    let currentDate = new Date();
    let ageYears = currentDate.getFullYear() - birthDate.getFullYear();
    if (ageYears < 18) {
        return false
    } else if (ageYears === 18) {
        let ageMonths = currentDate.getMonth() - birthDate.getMonth();
        let ageDays = currentDate.getDate() - birthDate.getDate();
        if (ageMonths < 0 || (ageMonths === 0 && ageDays < 0)) {
            ageYears--;
        }
    }
    return ageYears >= 18;
});

$.validator.addMethod("customBirthDay", function(value, element, options) {

    if (!options.required && !value) return true;

    return value.match(/^(0?[1-9]|[12][0-9]|3[0-1])[/., -](0?[1-9]|1[0-2])$/);

});

$.validator.addMethod("cpfValidator", function(value, element, options) {

    if (!options.required && !value) return true;

    value = value.replace('.','');
    value = value.replace('.','');
    cpf = value.replace('-','');
    while(cpf.length < 11) cpf = "0"+ cpf;
    var expReg = /^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/;
    var a = [];
    var b = new Number;
    var c = 11;
    for (i=0; i<11; i++){
        a[i] = cpf.charAt(i);
        if (i < 9) b += (a[i] * --c);
    }
    if ((x = b % 11) < 2) { a[9] = 0 } else { a[9] = 11-x }
    b = 0;
    c = 11;
    for (y=0; y<10; y++) b += (a[y] * c--);
    if ((x = b % 11) < 2) { a[10] = 0; } else { a[10] = 11-x; }
    if ((cpf.charAt(9) != a[9]) || (cpf.charAt(10) != a[10]) || cpf.match(expReg)) return false;
    return true;
});


$.validator.addMethod("customUnimedNumber", function(value, element, options) {

    if (!options.required && !value) return true;

    //Transforma a string em um array de digitos
    var onlyDigits = value.replace(/\./g,"").split("");

    var digitsMultiResult = [];
    var index = 0;

    //Fato de multiplicação que vai de 9 até 2 de forma regresssiva
    var multiFactor = 9;

    //Dígito verificador será sempre 11
    var dv = 11;

    // pega o ultimo dígito do número qual deve ser igual o resultado da operação final.
    var lastDigit = onlyDigits[onlyDigits.length - 1];

    //Remove o ultimo digito do array, pois o calculo deve ser feito somente sobre os 16 primeiros
    onlyDigits.splice(onlyDigits.length - 1, 1);

    while (digitsMultiResult.length != onlyDigits.length) {
        var digit = parseInt(onlyDigits[index]);
        digitsMultiResult.push(digit * multiFactor);
        multiFactor--;
        if (multiFactor < 2) {
            multiFactor = 9;
        }
        index++;
    }

    var sumOfDigits = digitsMultiResult.reduce(function (total, num){
        return total += num;
    });

    var resultDivision = parseInt(sumOfDigits / dv);
    var restDivision  = parseInt(sumOfDigits % dv);

    var result = (dv - restDivision) > 9 ?  0 :  (dv - restDivision);

    return result == lastDigit;
});

jQuery('.numbers').attr('type', 'tel');