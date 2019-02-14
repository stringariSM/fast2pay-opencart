<?php
require_once DIR_SYSTEM."/../app/fast2pay/lib/Iugu.php";

class ControllerExtensionPaymentFast2payboleto extends Controller {

	public function index() {
		$this->load->model('checkout/order');

		//metodos
		$data['bo'] = true;

		$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."fast2pay` (
            `idUnica` int(15) NOT NULL AUTO_INCREMENT,
            `refFast` varchar(45) NOT NULL,
            `idPedido` int(15) NOT NULL,
            PRIMARY KEY (`idUnica`)
        ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;");

		//urls
		$data['boleto'] = $this->url->link('extension/payment/fast2payboleto/boleto','','SSL');

		//descontos
		$data['descbo'] = $this->config->get('payment_fast2payboleto_desconto_bol');

		//config
		$data['afiliacao'] = $this->config->get('payment_fast2payboleto_afiliacao');

		//detalhes do pedido
		$data['pedido'] = $pedido = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$total = $data['pedido']['total'];

        $data['fiscal'] = '';
		$cpf = $this->config->get('payment_fast2payboleto_cpf');
        $cnpj = $this->config->get('payment_fast2payboleto_cnpj');
        if(isset($pedido['custom_field'][$cpf]) && !empty($pedido['custom_field'][$cpf])){
            $data['fiscal'] = preg_replace('/\D/', '', $pedido['custom_field'][$cpf]);
        }elseif(isset($pedido['custom_field'][$cnpj]) && !empty($pedido['custom_field'][$cnpj])){
            $data['fiscal'] = preg_replace('/\D/', '', $pedido['custom_field'][$cnpj]);
        }

		$data['total_pedido'] = $this->formatar($total);

		return $this->load->view('extension/payment/fast2payboleto', $data);
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

    public function formatar($valor){
		if(version_compare(VERSION, '2.1.0.0', '<=')){
		return $this->currency->format($valor);
		}else{
		return $this->currency->format($valor,$this->session->data['currency']);
		}
	}

	public function boleto(){
		$this->load->model('checkout/order');
		Iugu::setApiKey(trim($this->config->get('payment_fast2payboleto_chave')));

		$order = $this->model_checkout_order->getOrder($_POST['pedido']);
		$telefone = preg_replace('/\D/', '', $order['telephone']);
		$ddd = substr($telefone,0,2);
		$tel = (strlen($telefone)==11)?substr($telefone,-9):substr($telefone,-8);

		$data['descbo'] = $this->config->get('payment_fast2payboleto_desconto_bol');
		$total = ($data['descbo']>0)?($order['total']-(($order['total']/100)*$data['descbo'])):$order['total'];

		$numero = '*';
		$camponumero = $this->config->get('payment_fast2payboleto_numero');
		if(isset($order['payment_custom_field'][$camponumero]) && !empty($order['payment_custom_field'][$camponumero])){
            $numero = $order['payment_custom_field'][$camponumero];
		}

        $complemento = '';
		$camponumero = $this->config->get('payment_fast2payboleto_com');
		if(isset($order['payment_custom_field'][$camponumero])){
            $complemento = $order['payment_custom_field'][$camponumero];
		}

		$dadosPedido = Array(
			"method" => 'bank_slip',
            "bank_slip_extra_days" => "3",
			"email" => $order['email'],
			"items" => Array(
				Array(
					"description" => "Pedido no total de ".$this->formatar(number_format($total, 2, '.', ''))." relacionado ao Pedido: #".$order['order_id']." - ".date('d/m/Y')."",
					"quantity" => "1",
					"price_cents" => number_format($total, 2, '', '')
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
					"zip_code" => preg_replace('/\D/', '', $order['payment_postcode'])
				)
			)
		);

		$resultado = Iugu_Charge::create($dadosPedido);

        //cria um log
        if(isset($resultado->message)){
            $this->log->write('Fast2Pay - Boleto [#'.$order['order_id'].'] -'.$resultado->message.' ('.(isset($resultado->LR)?$resultado->LR:'99').')');
        }

		if(isset($resultado->success) && $resultado->success==true){

			$json['erro'] = false;
			$json['link'] = $this->url->link('extension/payment/fast2payboleto/cupom&id='.$resultado->invoice_id.'','','SSL');

			$log = 'Aguardando Pagamento por Boleto<br>';
            $log .='Fatura: '.$resultado->invoice_id.'<br>';
			$log .= '<a class="button btn btn-success btn-xs" href="'.$resultado->url.'" target="blank"><i class="fa fa-print"></i> Imprimir Boleto</a>';
			$this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2payboleto_order_status_id'),$log,true);

			$this->db->query("INSERT INTO `".DB_PREFIX."fast2pay` (`idUnica`, `refFast`, `idPedido`) VALUES (NULL, '".$resultado->invoice_id."', '".$order['order_id']."');");

		}elseif(isset($resultado->errors)){
			$json['erro']=true;
			$json['msg']=$this->tratar_erros($resultado->errors);
		}else{
			$json['erro']=true;
			$json['msg']='Erro desconhecido ao gera fatura!';
		}
		echo json_encode($json);
	}

	public function cupom(){
        $this->load->model('checkout/order');
        Iugu::setApiKey(trim($this->config->get('payment_fast2payboleto_chave')));
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
        Iugu::setApiKey(trim($this->config->get('payment_fast2payboleto_chave')));
        if(isset($_REQUEST['event']) && $_REQUEST['event']=='invoice.status_changed'){
            $invoice = Iugu_Invoice::fetch($_REQUEST['data']['id']);
            $query = $this->db->query("SELECT * FROM `".DB_PREFIX."fast2pay` WHERE `refFast` = '".$invoice->id."';");
            if($query->row){
            $order = $this->model_checkout_order->getOrder($query->row['idPedido']);
            if($order) {
                $status_pedido_atual = $order['order_status_id'];
                if($invoice->status=='paid'){
                    if ($status_pedido_atual != $this->config->get('payment_fast2payboleto_aprovado')){
                        $this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2payboleto_aprovado'),'Aprovado',true);
                    }
                }elseif($invoice->status=='canceled' || $invoice->status=='expired'){
                    if ($status_pedido_atual != $this->config->get('payment_fast2payboleto_cancelado')){
                        $this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2payboleto_cancelado'),'Cancelado',true);
                    }
                }elseif($invoice->status=='pending'){
                    if ($status_pedido_atual != $this->config->get('payment_fast2payboleto_pendente')){
                        $this->model_checkout_order->addOrderHistory($order['order_id'], $this->config->get('payment_fast2payboleto_pendente'),'Pendente',true);
                    }
                }
            }
            }
        }
        //ativa debug
        if(isset($_REQUEST['debug'])){
            $this->log->write('Fast2Pay - Debug Boleto: '.print_r($_REQUEST,true).'');
        }

        echo 'OK';
	}
}
?>