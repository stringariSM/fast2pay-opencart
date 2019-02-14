<?php
class ControllerExtensionPaymentFast2payboleto extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/fast2payboleto');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_fast2payboleto', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['payable'])) {
			$data['error_payable'] = $this->error['payable'];
		} else {
			$data['error_payable'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/fast2payboleto', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/fast2payboleto', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        /************************************ */
        /*   SETA AS CONFIGURAÇÕES PRA VIEW   */
        /************************************ */
		if (isset($this->request->post['payment_fast2payboleto_total'])) {
			$data['payment_fast2payboleto_total'] = $this->request->post['payment_fast2payboleto_total'];
		} else {
			$data['payment_fast2payboleto_total'] = $this->config->get('payment_fast2payboleto_total');
        }

		if (isset($this->request->post['payment_fast2payboleto_status'])) {
			$data['payment_fast2payboleto_status'] = $this->request->post['payment_fast2payboleto_status'];
		} else {
			$data['payment_fast2payboleto_status'] = $this->config->get('payment_fast2payboleto_status');
        }

		if (isset($this->request->post['payment_fast2payboleto_sort_order'])) {
			$data['payment_fast2payboleto_sort_order'] = $this->request->post['payment_fast2payboleto_sort_order'];
		} else {
			$data['payment_fast2payboleto_sort_order'] = $this->config->get('payment_fast2payboleto_sort_order');
        }

		if (isset($this->request->post['payment_fast2payboleto_pendente'])) {
			$data['payment_fast2payboleto_pendente'] = $this->request->post['payment_fast2payboleto_pendente'];
		} else {
			$data['payment_fast2payboleto_pendente'] = $this->config->get('payment_fast2payboleto_pendente');
        }

		if (isset($this->request->post['payment_fast2payboleto_order_status_id'])) {
			$data['payment_fast2payboleto_order_status_id'] = $this->request->post['payment_fast2payboleto_order_status_id'];
		} else {
			$data['payment_fast2payboleto_order_status_id'] = $this->config->get('payment_fast2payboleto_order_status_id');
        }

		if (isset($this->request->post['payment_fast2payboleto_numero'])) {
			$data['payment_fast2payboleto_numero'] = $this->request->post['payment_fast2payboleto_numero'];
		} else {
			$data['payment_fast2payboleto_numero'] = $this->config->get('payment_fast2payboleto_numero');
        }

		if (isset($this->request->post['payment_fast2payboleto_nome'])) {
			$data['payment_fast2payboleto_nome'] = $this->request->post['payment_fast2payboleto_nome'];
		} else {
			$data['payment_fast2payboleto_nome'] = $this->config->get('payment_fast2payboleto_nome');
        }

		if (isset($this->request->post['payment_fast2payboleto_geo_zone_id'])) {
			$data['payment_fast2payboleto_geo_zone_id'] = $this->request->post['payment_fast2payboleto_geo_zone_id'];
		} else {
			$data['payment_fast2payboleto_geo_zone_id'] = $this->config->get('payment_fast2payboleto_geo_zone_id');
        }

		if (isset($this->request->post['payment_fast2payboleto_desconto_bol'])) {
			$data['payment_fast2payboleto_desconto_bol'] = $this->request->post['payment_fast2payboleto_desconto_bol'];
		} else {
			$data['payment_fast2payboleto_desconto_bol'] = $this->config->get('payment_fast2payboleto_desconto_bol');
        }

		if (isset($this->request->post['payment_fast2payboleto_cpf'])) {
			$data['payment_fast2payboleto_cpf'] = $this->request->post['payment_fast2payboleto_cpf'];
		} else {
			$data['payment_fast2payboleto_cpf'] = $this->config->get('payment_fast2payboleto_cpf');
        }

		if (isset($this->request->post['payment_fast2payboleto_com'])) {
			$data['payment_fast2payboleto_com'] = $this->request->post['payment_fast2payboleto_com'];
		} else {
			$data['payment_fast2payboleto_com'] = $this->config->get('payment_fast2payboleto_com');
        }

		if (isset($this->request->post['payment_fast2payboleto_cnpj'])) {
			$data['payment_fast2payboleto_cnpj'] = $this->request->post['payment_fast2payboleto_cnpj'];
		} else {
			$data['payment_fast2payboleto_cnpj'] = $this->config->get('payment_fast2payboleto_cnpj');
        }

		if (isset($this->request->post['payment_fast2payboleto_chave'])) {
			$data['payment_fast2payboleto_chave'] = $this->request->post['payment_fast2payboleto_chave'];
		} else {
			$data['payment_fast2payboleto_chave'] = $this->config->get('payment_fast2payboleto_chave');
        }

		if (isset($this->request->post['payment_fast2payboleto_cancelado'])) {
			$data['payment_fast2payboleto_cancelado'] = $this->request->post['payment_fast2payboleto_cancelado'];
		} else {
			$data['payment_fast2payboleto_cancelado'] = $this->config->get('payment_fast2payboleto_cancelado');
        }

		if (isset($this->request->post['payment_fast2payboleto_aprovado'])) {
			$data['payment_fast2payboleto_aprovado'] = $this->request->post['payment_fast2payboleto_aprovado'];
		} else {
			$data['payment_fast2payboleto_aprovado'] = $this->config->get('payment_fast2payboleto_aprovado');
        }

		if (isset($this->request->post['payment_fast2payboleto_afiliacao'])) {
			$data['payment_fast2payboleto_afiliacao'] = $this->request->post['payment_fast2payboleto_afiliacao'];
		} else {
			$data['payment_fast2payboleto_afiliacao'] = $this->config->get('payment_fast2payboleto_afiliacao');
        }


        /************************************ */
        /*   CARREGA OS STATUS DE PEDIDO      */
        /************************************ */
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();


        /************************************ */
        /*   CARREGA AS LOCALIDADES           */
        /************************************ */
		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/fast2payboleto', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/free_checkout')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}