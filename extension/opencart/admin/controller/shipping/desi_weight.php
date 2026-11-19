<?php
namespace Opencart\Admin\Controller\Extension\Opencart\Shipping;
/**
 * Class DesiWeight
 *
 * @package Opencart\Admin\Controller\Extension\Opencart\Shipping
 */
class DesiWeight extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/opencart/shipping/desi_weight');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/opencart/shipping/desi_weight', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/opencart/shipping/desi_weight.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');

		$this->load->model('localisation/tax_class');

		$data['shipping_desi_weight_tax_class_id'] = (int)$this->config->get('shipping_desi_weight_tax_class_id');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['shipping_desi_weight_divisor'] = (float)($this->config->get('shipping_desi_weight_divisor') ?: 3000);
		$data['shipping_desi_weight_desi_rates'] = (string)($this->config->get('shipping_desi_weight_desi_rates') ?: '2:100,3:150,4:155,5:165,10:195,15:270,20:360,9999:400');
		$data['shipping_desi_weight_kg_rate'] = (string)($this->config->get('shipping_desi_weight_kg_rate') ?: '20');
		$data['shipping_desi_weight_max_cost'] = (string)($this->config->get('shipping_desi_weight_max_cost') !== null ? $this->config->get('shipping_desi_weight_max_cost') : '500');
		$data['shipping_desi_weight_supplier_map'] = (string)($this->config->get('shipping_desi_weight_supplier_map') ?: 'TUNA:Kahramanmaraş,HATAY:Hatay,KARSMEH:Kars,YM:Özel Tedarikçi');
		$data['shipping_desi_weight_status'] = $this->config->get('shipping_desi_weight_status');
		$data['shipping_desi_weight_sort_order'] = $this->config->get('shipping_desi_weight_sort_order');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/opencart/shipping/desi_weight', $data));
	}

	public function save(): void {
		$this->load->language('extension/opencart/shipping/desi_weight');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/opencart/shipping/desi_weight')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$divisor = isset($this->request->post['shipping_desi_weight_divisor']) ? (float)$this->request->post['shipping_desi_weight_divisor'] : 0;
		$kg_rate = isset($this->request->post['shipping_desi_weight_kg_rate']) ? trim((string)$this->request->post['shipping_desi_weight_kg_rate']) : '';
		$max_cost = isset($this->request->post['shipping_desi_weight_max_cost']) ? trim((string)$this->request->post['shipping_desi_weight_max_cost']) : '';
		$supplier_map = isset($this->request->post['shipping_desi_weight_supplier_map']) ? trim((string)$this->request->post['shipping_desi_weight_supplier_map']) : '';
		$rates = isset($this->request->post['shipping_desi_weight_desi_rates']) ? trim((string)$this->request->post['shipping_desi_weight_desi_rates']) : '';

		if ($divisor <= 0) {
			$json['error']['divisor'] = $this->language->get('error_divisor');
		}

		if (!is_numeric($max_cost) || (float)$max_cost < 0) {
			$json['error']['max_cost'] = $this->language->get('error_max_cost');
		}

		if ($supplier_map === '' || !$this->validateSupplierMap($supplier_map)) {
			$json['error']['supplier_map'] = $this->language->get('error_supplier_map');
		}

		if (!is_numeric($kg_rate) || (float)$kg_rate < 0) {
			$json['error']['kg_rate'] = $this->language->get('error_kg_rate');
		} elseif (!$this->validateRates($rates)) {
			$json['error']['desi_rates'] = $this->language->get('error_rates');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('shipping_desi_weight', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function validateRates(string $rates): bool {
		$count = 0;

		foreach (explode(',', $rates) as $part) {
			$part = trim($part);

			if ($part === '') {
				return false;
			}

			$data = explode(':', $part, 2);

			if (count($data) !== 2) {
				return false;
			}

			if (!is_numeric(trim($data[0])) || !is_numeric(trim($data[1]))) {
				return false;
			}

			$count++;
		}

		return $count > 0;
	}

	private function validateSupplierMap(string $map): bool {
		$count = 0;

		foreach (explode(',', $map) as $part) {
			$part = trim($part);

			if ($part === '') {
				return false;
			}

			$data = explode(':', $part, 2);

			if (count($data) !== 2) {
				return false;
			}

			if (trim($data[0]) === '' || trim($data[1]) === '') {
				return false;
			}

			$count++;
		}

		return $count > 0;
	}
}
