<?php
namespace Opencart\Catalog\Model\Extension\Opencart\Payment;
/**
 * Class PayTr
 *
 * PayTR Sanal POS - iFrame API
 *
 * @package Opencart\Catalog\Model\Extension\Opencart\Payment
 */
class PayTr extends \Opencart\System\Engine\Model {
	/**
	 * Get Methods
	 *
	 * @param array<string, mixed> $address array of data
	 *
	 * @return array<string, mixed>
	 */
	public function getMethods(array $address = []): array {
		$this->load->language('extension/opencart/payment/paytr');

		// Order totals
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$this->load->model('checkout/cart');

		($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

		$minimum = (float)$this->config->get('payment_paytr_total');

		$method_data = [];

		if ($total >= $minimum) {
			$option_data['paytr'] = [
				'code' => 'paytr.paytr',
				'name' => $this->language->get('heading_title')
			];

			$method_data = [
				'code'       => 'paytr',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_paytr_sort_order')
			];
		}

		return $method_data;
	}
}
