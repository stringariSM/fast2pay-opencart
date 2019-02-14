<?php
class ControllerExtensionPaymentFast2pay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/fast2pay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_fast2pay', $this->request->post);

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
			'href' => $this->url->link('extension/payment/fast2pay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/fast2pay', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        /************************************ */
        /*   SETA AS CONFIGURAÇÕES PRA VIEW   */
        /************************************ */
		if (isset($this->request->post['payment_fast2pay_taxa'])) {
			$data['payment_fast2pay_taxa'] = $this->request->post['payment_fast2pay_taxa'];
		} else {
			$data['payment_fast2pay_taxa'] = $this->config->get('payment_fast2pay_taxa');
        }

        if (isset($this->request->post['payment_fast2pay_total'])) {
			$data['payment_fast2pay_total'] = $this->request->post['payment_fast2pay_total'];
		} else {
			$data['payment_fast2pay_total'] = $this->config->get('payment_fast2pay_total');
        }

        if (isset($this->request->post['payment_fast2pay_status'])) {
			$data['payment_fast2pay_status'] = $this->request->post['payment_fast2pay_status'];
		} else {
			$data['payment_fast2pay_status'] = $this->config->get('payment_fast2pay_status');
        }

        if (isset($this->request->post['payment_fast2pay_sort_order'])) {
			$data['payment_fast2pay_sort_order'] = $this->request->post['payment_fast2pay_sort_order'];
		} else {
			$data['payment_fast2pay_sort_order'] = $this->config->get('payment_fast2pay_sort_order');
        }

        if (isset($this->request->post['payment_fast2pay_sem'])) {
			$data['payment_fast2pay_sem'] = $this->request->post['payment_fast2pay_sem'];
		} else {
			$data['payment_fast2pay_sem'] = $this->config->get('payment_fast2pay_sem');
        }

        if (isset($this->request->post['payment_fast2pay_pendente'])) {
			$data['payment_fast2pay_pendente'] = $this->request->post['payment_fast2pay_pendente'];
		} else {
			$data['payment_fast2pay_pendente'] = $this->config->get('payment_fast2pay_pendente');
        }

        if (isset($this->request->post['payment_fast2pay_order_status_id'])) {
			$data['payment_fast2pay_order_status_id'] = $this->request->post['payment_fast2pay_order_status_id'];
		} else {
			$data['payment_fast2pay_order_status_id'] = $this->config->get('payment_fast2pay_order_status_id');
        }

        if (isset($this->request->post['payment_fast2pay_numero'])) {
			$data['payment_fast2pay_numero'] = $this->request->post['payment_fast2pay_numero'];
		} else {
			$data['payment_fast2pay_numero'] = $this->config->get('payment_fast2pay_numero');
        }

        if (isset($this->request->post['payment_fast2pay_nome'])) {
			$data['payment_fast2pay_nome'] = $this->request->post['payment_fast2pay_nome'];
		} else {
			$data['payment_fast2pay_nome'] = $this->config->get('payment_fast2pay_nome');
        }

        if (isset($this->request->post['payment_fast2pay_min'])) {
			$data['payment_fast2pay_min'] = $this->request->post['payment_fast2pay_min'];
		} else {
			$data['payment_fast2pay_min'] = $this->config->get('payment_fast2pay_min');
        }

        if (isset($this->request->post['payment_fast2pay_geo_zone_id'])) {
			$data['payment_fast2pay_geo_zone_id'] = $this->request->post['payment_fast2pay_geo_zone_id'];
		} else {
			$data['payment_fast2pay_geo_zone_id'] = $this->config->get('payment_fast2pay_geo_zone_id');
        }

        if (isset($this->request->post['payment_fast2pay_div'])) {
			$data['payment_fast2pay_div'] = $this->request->post['payment_fast2pay_div'];
		} else {
			$data['payment_fast2pay_div'] = $this->config->get('payment_fast2pay_div');
        }

        if (isset($this->request->post['payment_fast2pay_cpf'])) {
			$data['payment_fast2pay_cpf'] = $this->request->post['payment_fast2pay_cpf'];
		} else {
			$data['payment_fast2pay_cpf'] = $this->config->get('payment_fast2pay_cpf');
        }

        if (isset($this->request->post['payment_fast2pay_com'])) {
			$data['payment_fast2pay_com'] = $this->request->post['payment_fast2pay_com'];
		} else {
			$data['payment_fast2pay_com'] = $this->config->get('payment_fast2pay_com');
        }

        if (isset($this->request->post['payment_fast2pay_cnpj'])) {
			$data['payment_fast2pay_cnpj'] = $this->request->post['payment_fast2pay_cnpj'];
		} else {
			$data['payment_fast2pay_cnpj'] = $this->config->get('payment_fast2pay_cnpj');
        }

        if (isset($this->request->post['payment_fast2pay_chave'])) {
			$data['payment_fast2pay_chave'] = $this->request->post['payment_fast2pay_chave'];
		} else {
			$data['payment_fast2pay_chave'] = $this->config->get('payment_fast2pay_chave');
        }

        if (isset($this->request->post['payment_fast2pay_cancelado'])) {
			$data['payment_fast2pay_cancelado'] = $this->request->post['payment_fast2pay_cancelado'];
		} else {
			$data['payment_fast2pay_cancelado'] = $this->config->get('payment_fast2pay_cancelado');
        }

        if (isset($this->request->post['payment_fast2pay_aprovado'])) {
			$data['payment_fast2pay_aprovado'] = $this->request->post['payment_fast2pay_aprovado'];
		} else {
			$data['payment_fast2pay_aprovado'] = $this->config->get('payment_fast2pay_aprovado');
        }

        if (isset($this->request->post['payment_fast2pay_afiliacao'])) {
			$data['payment_fast2pay_afiliacao'] = $this->request->post['payment_fast2pay_afiliacao'];
		} else {
			$data['payment_fast2pay_afiliacao'] = $this->config->get('payment_fast2pay_afiliacao');
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

		$this->response->setOutput($this->load->view('extension/payment/fast2pay', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/free_checkout')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}