<?php
require_once DIR_SYSTEM."/../app/fast2pay/lib/Iugu.php";
class ControllerExtensionPaymentFast2pay extends Controller {

	public function index() {
		$this->load->model('checkout/order');

		//metodos
		$data['cc'] = true;
		$data['ano'] = (int)date('Y');

		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fast2pay` (
        `idUnica` int(15) NOT NULL AUTO_INCREMENT,
          `refFast` varchar(45) NOT NULL,
          `idPedido` int(15) NOT NULL,
          PRIMARY KEY (`idUnica`)
        ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;");

		//urls
		$data['cartao'] = $this->url->link('extension/payment/fast2pay/cartao','','SSL');

		//config
		$data['afiliacao'] = $this->config->get('payment_fast2pay_afiliacao');
		$data['div'] = $this->config->get('payment_fast2pay_div');
		$data['min'] = $this->config->get('payment_fast2pay_min');
		$data['sem'] = $this->config->get('payment_fast2pay_sem');
		$data['taxa'] = $this->config->get('payment_fast2pay_taxa');



		//detalhes do pedido
		$data['pedido'] = $pedido = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data['fiscal'] = '';
		$cpf = $this->config->get('payment_fast2pay_cpf');
        $cnpj = $this->config->get('payment_fast2pay_cnpj');
        if(isset($pedido['custom_field'][$cpf]) && !empty($pedido['custom_field'][$cpf])){
            $data['fiscal'] = preg_replace('/\D/', '', $pedido['custom_field'][$cpf]);
        }elseif(isset($pedido['custom_field'][$cnpj]) && !empty($pedido['custom_field'][$cnpj])){
            $data['fiscal'] = preg_replace('/\D/', '', $pedido['custom_field'][$cnpj]);
        }

		$data['total_pedido'] = $this->formatar($data['pedido']['total']);

		//parcelas
        $parcelas = array();
		include DIR_SYSTEM."../app/fast2pay/parcelas.php";
		$data['parcelas'] = $parcelas;

		return $this->load->view('extension/payment/fast2pay', $data);

	}

    public function formatar($valor){
		if(version_compare(VERSION, '2.1.0.0', '<=')){
		return $this->currency->format($valor);
		}else{
		return $this->currency->format($valor,$this->session->data['currency']);
		}
	}

	public function qual_bandeira() {
        $number = $_POST['numero'];
        $number=preg_replace('/[^\d]/','',$number);
        if (preg_match('/^3[47][0-9]{13}$/',$number)) {
            echo 'amex';
        } elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number)) {
            echo 'diners';
        } elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number)) {
            echo 'discover';
        } elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number)) {
            echo 'jcb';
        } elseif (preg_match('/^5[1-5][0-9]{14}$/',$number)) {
            echo 'mastercard';
        } elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number)) {
            echo 'visa';
        } elseif (preg_match('/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',$number)) {
            echo 'hipercard';
        } elseif (preg_match('/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/',$number)) {
            echo 'elo';
        } elseif (preg_match('/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/',$number)) {
            echo 'elo';
        } else {
            echo '';
        }
	}

    public function juros($valorTotal, $taxa, $nParcelas){
		$taxa = $taxa/100;
		$cadaParcela = ($valorTotal*$taxa)/(1-(1/pow(1+$taxa, $nParcelas)));
		return round($cadaParcela, 2);
	}

	public function cartao(){
		$this->load->model('checkout/order');
		Iugu::setApiKey(trim($this->config->get('payment_fast2pay_chave')));
		$dados['account_id'] = trim($this->config->get('payment_fast2pay_afiliacao'));
		$dados['method'] = 'credit_card';
		$nome_completo = explode(' ',$_POST['titular'],2);
		//$validade = explode('/',$_POST['validade']);
		$dados['data']['number'] = preg_replace('/\D/', '', $_POST['numero']);
		$dados['data']['verification_value'] = preg_replace('/\D/', '', $_POST['codigo']);
		$dados['data']['first_name'] = isset($nome_completo[0])?$nome_completo[0]:'';
		$dados['data']['last_name'] = isset($nome_completo[1])?$nome_completo[1]:'';
		$dados['data']['month'] = $_POST['validade_cartao_mes'];
		$dados['data']['year'] = $_POST['validade_cartao_ano'];
		$resultado = Iugu_PaymentToken::create($dados);
		if(!isset($resultado->errors) && isset($resultado->id)){

		//regra de parcelamento
		$order = $this->model_checkout_order->getOrder($_POST['pedido']);
		$taxa = $this->config->get('payment_fast2pay_taxa');
		$parcela = (int)(isset($_POST['parcela'])?$_POST['parcela']:'1');
		$regras = array(1=>2.50,2=>2.50,3=>2.50,4=>2.50,5=>2.50,6=>2.50,7=>2.50,8=>2.50,9=>2.50,10=>2.50,11=>2.50,12=>2.50);
		$total_pedido = $order['total'];
		$fator = $regras[$parcela]*$parcela;
		$total_parcelar =  $total_pedido*((1-($taxa/100))/(1-(($taxa+$fator)/100)));

		$telefone = preg_replace('/\D/', '', $order['telephone']);
		$ddd = substr($telefone,0,2);
		$tel = (strlen($telefone)==11)?substr($telefone,-9):substr($telefone,-8);

        $numero = '*';
		$camponumero = $this->config->get('payment_fast2pay_numero');
		if(isset($order['payment_custom_field'][$camponumero]) && !empty($order['payment_custom_field'][$camponumero])){
            $numero = $order['payment_custom_field'][$camponumero];
		}

        $complemento = '';
		$camponumero = $this->config->get('payment_fast2pay_com');
		if(isset($order['payment_custom_field'][$camponumero])){
            $complemento = $order['payment_custom_field'][$camponumero];
		}

		$dadosPedido = Array(
			"token" => $resultado->id,
			"email" => $order['email'],
			"months" => (int)$parcela,
			"items" => Array(
				Array(
					"description" => "Pedido no total de ".$this->formatar(number_format($order['total'], 2, '.', ''))." relacionado ao Pedido: #".$order['order_id']." - ".date('d/m/Y')."",
					"quantity" => "1",
					"price_cents" => number_format($order['total'], 2, '', '')
				)
			) ,
			"payer" => Array(
				"name" => $order['firstname'].' '.$order['lastname'],
				"cpf_cnpj" => preg_replace('/\D/', '', $_POST['cpf']),
				"phone_prefix" => $ddd,
				"phone" => $tel,
				"email" => $order['email'],
				"address" => Array(
					"street" => $order['payment_address_1'].' '.$complemento,
					"number" => $numero,
					"city" => $order['payment_city'],
					"state" => $order['payment_zone_code'],
					"country" => "Brasil",
					"zip_code" => $order['payment_postcode']
				)
			)
		);

		$resultado = Iugu_Charge::create($dadosPedido);

        //cria um log
        if(isset($resultado->message)){
            $this->log->write('Iugu Cartão [#'.$order['order_id'].'] -'.$resultado->message.' ('.(isset($resultado->LR)?$resultado->LR:'99').')');
        }

		//se a compra foi aprovada com sucesso
		if(isset($resultado->success) && $resultado->success==true){

			$json['erro'] = false;
			$json['link'] = $this->url->link('extension/payment/fast2pay/cupom&id='.$resultado->invoice_id.'','','SSL');

			$log = 'Aprovado no Cart&atilde;o de Cr&eacute;dito em <b>'.(int)$_POST['parcela'].'x</b>';
            $log .= '<br>Fatura: '.$resultado->invoice_id;

			$this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2pay_aprovado'),$log,true);

			$this->db->query("INSERT INTO `".DB_PREFIX."fast2pay` (`idUnica`, `refFast`, `idPedido`) VALUES (NULL, '".$resultado->invoice_id."', '".$order['order_id']."');");

		}elseif(isset($resultado->success) && $resultado->success==false){

			$json['erro'] = false;
			$json['link'] = $this->url->link('extension/payment/fast2pay/cupom&id='.$resultado->invoice_id.'','','SSL');

			$log = 'Negado no Cart&atilde;o de Cr&eacute;dito em <b>'.(int)$_POST['parcela'].'x</b>';
            $log .= '<br>Fatura: '.$resultado->invoice_id;

			$this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2pay_cancelado'),$log,true);

			$this->db->query("INSERT INTO `".DB_PREFIX."fast2pay` (`idUnica`, `refFast`, `idPedido`) VALUES (NULL, '".$resultado->invoice_id."', '".$order['order_id']."');");

		}else{
			$json['erro']=true;
			$json['msg']='Erro desconhecido ao gerar fatura!';
		}

		}elseif(isset($resultado->errors)){
			$json['erro']=true;
			$json['msg']=$this->tratar_erros($resultado->errors);
		}else{
			$json['erro']=true;
			$json['msg']='erro desconhecido ao gerar token!';
		}
		echo json_encode($json);
	}

	public function tratar_erros($err){
		if(is_array($err)){
		$erros = '';
		foreach($err AS $k=>$v){
			$erros .= '- '.$v[0].' ';
		}
		return $erros;
		}else{
			return $err;
		}
	}

	public function cupom(){
        $this->load->model('checkout/order');
        Iugu::setApiKey(trim($this->config->get('payment_fast2pay_chave')));
        $data['invoice'] = Iugu_Invoice::fetch($_GET['id']);

        $query = $this->db->query("SELECT idPedido FROM `".DB_PREFIX."fast2pay` WHERE `refFast` = '".$data['invoice']->id."' LIMIT 1;");
        $log = $query->row;

        $data['pedido'] = $this->model_checkout_order->getOrder($log['idPedido']);
        $data['iframe'] = $this->url->link('checkout/success','','SSL');

        $this->document->setTitle('Resultado da Transa&ccedil;&atilde;o');
        $this->document->setDescription('');
        $this->document->setKeywords('');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->template = 'extension/payment/fast2pay_recibo';
        $this->response->setOutput($this->load->view($this->template, $data));
	}

	public function status($id){
        switch($id){
        case 'pending':
        return 'Pendente';
        break;
        case 'paid':
        return 'Paga';
        break;
        case 'canceled':
        return 'Cancelada';
        break;
        default:
        return $id;
        }
	}

	public function ipn(){
        $this->load->model('checkout/order');
        Iugu::setApiKey(trim($this->config->get('payment_fast2pay_chave')));
        if(isset($_REQUEST['event']) && $_REQUEST['event']=='invoice.status_changed'){
            $invoice = Iugu_Invoice::fetch($_REQUEST['data']['id']);
            $query = $this->db->query("SELECT * FROM `".DB_PREFIX."fast2pay` WHERE `refFast` = '".$invoice->id."';");
            if($query->row){
            $order = $this->model_checkout_order->getOrder($query->row['idPedido']);
            if($order) {
                $status_pedido_atual = $order['order_status_id'];
                if($invoice->status=='paid'){
                if ($status_pedido_atual != $this->config->get('payment_fast2pay_aprovado')){
                $this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2pay_aprovado'),'Aprovado',true);
                }
                }elseif($invoice->status=='canceled' || $invoice->status=='expired'){
                if ($status_pedido_atual != $this->config->get('payment_fast2pay_cancelado')){
                $this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2pay_cancelado'),'Cancelado',true);
                }
                }elseif($invoice->status=='pending'){
                if ($status_pedido_atual != $this->config->get('payment_fast2pay_pendente')){
                $this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2pay_pendente'),'Pendente',true);
                }
                }
            }
            }
        }
        //ativa debug
        if(isset($_REQUEST['debug'])){
            $this->log->write('Fast2Pay - Debug Cartao: '.print_r($_REQUEST,true).'');
        }
        echo 'OK';
	}
}
?>