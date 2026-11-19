<?php
namespace Opencart\Admin\Controller\Extension\Opencart\Payment;
/**
 * Class PayTr
 *
 * PayTR Sanal POS - iFrame API
 *
 * @package Opencart\Admin\Controller\Extension\Opencart\Payment
 */
class PayTr extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/opencart/payment/paytr');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/opencart/payment/paytr', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/opencart/payment/paytr.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$data['payment_paytr_merchant_id'] = $this->config->get('payment_paytr_merchant_id');
		$data['payment_paytr_merchant_key'] = $this->config->get('payment_paytr_merchant_key');
		$data['payment_paytr_merchant_salt'] = $this->config->get('payment_paytr_merchant_salt');
		$data['payment_paytr_test_mode'] = (int)$this->config->get('payment_paytr_test_mode');
		$data['payment_paytr_debug_on'] = (int)$this->config->get('payment_paytr_debug_on');
		$data['payment_paytr_no_installment'] = (int)$this->config->get('payment_paytr_no_installment');
		$data['payment_paytr_max_installment'] = (int)$this->config->get('payment_paytr_max_installment');
		$data['payment_paytr_timeout_limit'] = (string)($this->config->get('payment_paytr_timeout_limit') ?: 30);
		$data['payment_paytr_total'] = $this->config->get('payment_paytr_total');
		$data['payment_paytr_sort_order'] = $this->config->get('payment_paytr_sort_order');

		// Order Statuses
		$data['payment_paytr_order_status_id'] = (int)$this->config->get('payment_paytr_order_status_id');
		$data['payment_paytr_fail_status_id'] = (int)$this->config->get('payment_paytr_fail_status_id');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_paytr_status'] = $this->config->get('payment_paytr_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/opencart/payment/paytr', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/opencart/payment/paytr');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/opencart/payment/paytr')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_paytr', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
