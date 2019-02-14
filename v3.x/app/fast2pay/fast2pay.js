function jcv_luhnCheckFast2pay(cardNumber) {
        var cardNumber = (cardNumber).replace(/\D/g,'');
        if (jcv_isLuhnNumFast2pay(cardNumber)) {
            var no_digit = cardNumber.length;
            var oddoeven = no_digit & 1;
            var sum = 0;
            for (var count = 0; count < no_digit; count++) {
                var digit = parseInt(cardNumber.charAt(count));
                if (!((count & 1) ^ oddoeven)) {
                    digit *= 2;
                    if (digit > 9) digit -= 9;
                };
                sum += digit;
            };
            if (sum == 0) return false;
            if (sum % 10 == 0) return true;
        };
        return false;
}

function jcv_isLuhnNumFast2pay(argvalue) {
        argvalue = argvalue.toString();
        if (argvalue.length == 0) {
            return false;
        }
        for (var n = 0; n < argvalue.length; n++) {
            if ((argvalue.substring(n, n+1) < "0") ||
                (argvalue.substring(n,n+1) > "9")) {
                return false;
            }
        }
        return true;
}

function upFast2pay(lstr){ // converte minusculas em maiusculas
    var str=lstr.value; //obtem o valor
    lstr.value=str.toUpperCase(); //converte as strings e retorna ao campo
}
function downFast2pay(lstr){ // converte maiusculas em minusculas
    var str=lstr.value; //obtem o valor
    lstr.value=str.toLowerCase(); //converte as strings e retorna ao campo
}

function isNumberKeyfast2pay(evt) {
         var charCode = (evt.which) ? evt.which : evt.keyCode
         if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;

         return true;
}

function validaCPFFast2pay(s) {
	var c = s.substr(0,9);
	var dv = s.substr(9,2);
	var d1 = 0;
	for (var i=0; i<9; i++) {
		d1 += c.charAt(i)*(10-i);
 	}
	if (d1 == 0) return false;
	d1 = 11 - (d1 % 11);
	if (d1 > 9) d1 = 0;
	if (dv.charAt(0) != d1){
		return false;
	}
	d1 *= 2;
	for (var i = 0; i < 9; i++)	{
 		d1 += c.charAt(i)*(11-i);
	}
	d1 = 11 - (d1 % 11);
	if (d1 > 9) d1 = 0;
	if (dv.charAt(1) != d1){
		return false;
	}
    return true;
}
function validaCNPJFast2pay(CNPJ) {
	var a = new Array();
	var b = new Number;
	var c = [6,5,4,3,2,9,8,7,6,5,4,3,2];
	for (i=0; i<12; i++){
		a[i] = CNPJ.charAt(i);
		b += a[i] * c[i+1];
	}
	if ((x = b % 11) < 2) { a[12] = 0 } else { a[12] = 11-x }
	b = 0;
	for (y=0; y<13; y++) {
		b += (a[y] * c[y]);
	}
	if ((x = b % 11) < 2) { a[13] = 0; } else { a[13] = 11-x; }
	if ((CNPJ.charAt(12) != a[12]) || (CNPJ.charAt(13) != a[13])){
		return false;
	}
	return true;
}

function validarCpfCnpjFast2pay(valor) {
	var s = (valor).replace(/\D/g,'');
	var tam=(s).length;
	if (!(tam==11 || tam==14)){
		return false;
	}
	if (tam==11 ){
		if (!validaCPFFast2pay(s)){
			return false;
		}
		return true;
	}
	if (tam==14){
		if(!validaCNPJFast2pay(s)){
			return false;
		}
		return true;
	}
}

function qualTipoCartaoFast2pay(numero) {
	console.log(numero);
	$.post('index.php?route=extension/payment/fast2pay/qual_bandeira', {numero:numero}).done(function( data ) {
    console.log(data);
	if(data!=''){
		$(".ccs_fast2pay").css({ opacity: 0.2 });
		$("#"+ data ).css({ opacity: 1 });
	}
	});
}

//meios custom validator
$.validator.addMethod("validacpfcnpj", function(value, element) {
  return this.optional(element) || validarCpfCnpjFast2pay(value);
}, "Digite CPF do titular valido");

$.validator.addMethod("validacartao", function(value, element) {
  return this.optional(element) || jcv_luhnCheckFast2pay(value);
}, "Digite um cart\u00e3o valido");

//valida o form cartao
$(".form_pagamento_cartao_fast2pay").validate({
  submitHandler: function(form) {

	//tela de bloqueio
	$('#tela-full-fast2pay').block({
		message: '<b>Aguarde...</b>',
		css: { border: '1px solid #000', 'border-radius': '10px' }
    });

	var form = $(".form_pagamento_cartao_fast2pay").serialize();
	console.log(form);

	$.post(url_pagar_cartao, form).done(function( data ) {
    var obj = jQuery.parseJSON(data);
	console.log(obj);
	if(obj.erro==true){
		//alert(obj.msg);
		bootbox.dialog({
			message: obj.msg,
			title: "Ops, ocorreu um erro!"
		});
		$('#tela-full-fast2pay').unblock();
		return false;
	}else{
		location.href=obj.link;
		return false;
	}
	});

	return false;

  },
  errorClass: "ops_campo_erro",
  rules: {
    titular: {
		required: true,
		minlength: 5
	},
    cpf: {
		required: true,
		validacpfcnpj:true
	},
	numero: {
		required: true,
		validacartao:true
	},
	validade_cartao_mes: "required",
    validade_cartao_ano: "required",
	codigo: {
		required: true,
		minlength: 3,
		maxlength: 4
	},
  },
  messages: {
    titular: "Informe nome do titular",
    cpf: "Informe o CPF do titular",
	numero: "Informe o n\u00famero do cart\u00e3o valido",
	validade_cartao_mes: "Selecione o mes de validade!",
    validade_cartao_ano: "Selecione o ano de validade",
	codigo: "Informe o CVV do cart\u00e3o",
  }
});

//form boleto
$(".form_pagamento_boleto_fast2pay").validate({
	submitHandler: function(form) {

	//tela de bloqueio
	$('#tela-full-fast2pay').block({
		message: '<b>Aguarde...</b>',
		css: { border: '1px solid #000', 'border-radius': '10px' }
    });

	var form = $(".form_pagamento_boleto_fast2pay").serialize();
	console.log(form);

	$.post(url_pagar_boleto, form).done(function( data ) {
    var obj = jQuery.parseJSON(data);
	console.log(obj);
	if(obj.erro==true){
		//alert(obj.msg);
		bootbox.dialog({
			message: obj.msg,
			title: "Ops, ocorreu um erro!"
		});
		$('#tela-full-fast2pay').unblock();
		return false;
	}else{
		location.href=obj.link;
		return false;
	}
	});

	return false;

  },
  errorClass: "ops_campo_erro",
  rules: {
    cpf: {
		required: true,
		validacpfcnpj:true
	},
  },
  messages: {
    cpf: "Informe o CPF de cobranca",
  }
});

//mascaras
jQuery(function($){
    $('.class_input_cartao').payment('formatCardNumber');
    $(".validade").mask("99/9999");
});