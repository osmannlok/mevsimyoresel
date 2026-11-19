<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Payment;
/**
 * Class PayTr
 *
 * PayTR Sanal POS - iFrame API
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Payment
 */
class PayTr extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * Rendered inside the checkout confirm page. Builds a PayTR iframe
	 * token from the order already stored in the session.
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('extension/opencart/payment/paytr');

		$data['language'] = $this->config->get('config_language');
		$data['iframe_token'] = '';
		$data['error'] = '';
		$data['paid'] = false;
		$data['text_not_configured'] = '';
		$data['text_iframe'] = $this->language->get('text_iframe');
		$data['text_order_paid'] = $this->language->get('text_order_paid');

		$order_id = (int)($this->session->data['order_id'] ?? 0);

		$merchant_id = (string)$this->config->get('payment_paytr_merchant_id');
		$merchant_key = (string)$this->config->get('payment_paytr_merchant_key');
		$merchant_salt = (string)$this->config->get('payment_paytr_merchant_salt');

		if (!$order_id) {
			$data['error'] = $this->language->get('error_order');

			return $this->load->view('extension/opencart/payment/paytr', $data);
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info && (int)$order_info['order_status_id']) {
			// Order was already processed (e.g. paid). Do not offer payment again.
			$data['paid'] = true;

			return $this->load->view('extension/opencart/payment/paytr', $data);
		}

		if (!$merchant_id || !$merchant_key || !$merchant_salt) {
			$data['text_not_configured'] = $this->language->get('text_not_configured');

			return $this->load->view('extension/opencart/payment/paytr', $data);
		}

		// Basket + amount
		$products = $this->cart->getProducts();

		$basket = [];
		$basket_total = 0;

		foreach ($products as $product) {
			$price = (float)$product['price'];
			$quantity = (int)$product['quantity'];

			$basket[] = [$product['name'], number_format($price, 2, '.', ''), $quantity];

			$basket_total += $price * $quantity;
		}

		// Shipping cost
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$this->load->model('checkout/cart');

		($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

		foreach ($totals as $entry) {
			if (isset($entry['code']) && $entry['code'] === 'shipping' && (float)$entry['value'] > 0.00) {
				$basket[] = ['Sabit Kargo Fiyatı', number_format((float)$entry['value'], 2, '.', ''), 1];
			}
		}

		if ($total <= 0.00) {
			$total = $basket_total;
		}

		$payment_amount = (int)round($total * 100);

		if ($payment_amount <= 0) {
			$data['error'] = $this->language->get('text_token_error');

			return $this->load->view('extension/opencart/payment/paytr', $data);
		}

		$user_basket = base64_encode((string)json_encode($basket));

		$user_ip = oc_get_ip();

		if (!$user_ip || $user_ip === 'unknown') {
			$user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		}

		$email = (string)$this->session->data['customer']['email'];
		$user_name = trim((string)$this->session->data['customer']['firstname'] . ' ' . (string)$this->session->data['customer']['lastname']);
		$shipping_address = $this->session->data['shipping_address'] ?? [];
		$user_address = trim((string)($shipping_address['address_1'] ?? '') . ' ' . (string)($shipping_address['city'] ?? '') . ' ' . (string)($shipping_address['country'] ?? ''));
		$user_phone = (string)$this->session->data['customer']['telephone'];

		$merchant_oid = $order_id . 'x' . bin2hex(random_bytes(6));

		$no_installment = (string)((int)$this->config->get('payment_paytr_no_installment'));
		$max_installment = (string)((int)$this->config->get('payment_paytr_max_installment'));
		$currency = 'TL';
		$test_mode = (string)((int)$this->config->get('payment_paytr_test_mode'));
		$debug_on = (string)((int)$this->config->get('payment_paytr_debug_on'));
		$timeout_limit = (string)((int)($this->config->get('payment_paytr_timeout_limit') ?: 30));

		$hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $test_mode;

		$paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

		$post_vals = [
			'merchant_id'    => $merchant_id,
			'user_ip'        => $user_ip,
			'merchant_oid'   => $merchant_oid,
			'email'          => $email,
			'payment_amount' => $payment_amount,
			'paytr_token'    => $paytr_token,
			'user_basket'    => $user_basket,
			'debug_on'       => $debug_on,
			'no_installment' => $no_installment,
			'max_installment' => $max_installment,
			'user_name'      => $user_name,
			'user_address'   => $user_address,
			'user_phone'     => $user_phone,
			'merchant_ok_url' => $this->url->link('extension/opencart/payment/paytr.result', 'language=' . $this->config->get('config_language') . '&result=success', true),
			'merchant_fail_url' => $this->url->link('extension/opencart/payment/paytr.result', 'language=' . $this->config->get('config_language') . '&result=fail', true),
			'timeout_limit'  => $timeout_limit,
			'currency'       => $currency,
			'test_mode'      => $test_mode
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://www.paytr.com/odeme/api/get-token');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			$data['error'] = $this->language->get('text_token_error');

			return $this->load->view('extension/opencart/payment/paytr', $data);
		}

		curl_close($ch);

		$result = json_decode((string)$result, true);

		if (!empty($result['status']) && $result['status'] === 'success' && !empty($result['token'])) {
			$data['iframe_token'] = $result['token'];
		} else {
			$reason = $result['reason'] ?? 'unknown';

			$data['error'] = $this->language->get('text_token_error') . ' (' . $reason . ')';
		}

		return $this->load->view('extension/opencart/payment/paytr', $data);
	}

	/**
	 * Result
	 *
	 * Landing page for merchant_ok_url / merchant_fail_url (breaks out of the iframe).
	 *
	 * @return void
	 */
	public function result(): void {
		$this->load->language('extension/opencart/payment/paytr');

		$data['result'] = $this->request->get['result'] ?? 'fail';
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_message'] = $data['result'] === 'success' ? $this->language->get('text_result_success') : $this->language->get('text_result_fail');
		$data['href'] = $data['result'] === 'success'
			? $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'))
			: $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'));

		$this->response->setOutput($this->load->view('extension/opencart/payment/result', $data));
	}

	/**
	 * Callback
	 *
	 * PayTR server-to-server notification (Bildirim URL / Callback).
	 *
	 * @return void
	 */
	public function callback(): void {
		$this->load->language('extension/opencart/payment/paytr');

		$merchant_key = (string)$this->config->get('payment_paytr_merchant_key');
		$merchant_salt = (string)$this->config->get('payment_paytr_merchant_salt');

		$post = $this->request->post;

		if (empty($post['merchant_oid']) || empty($post['status']) || !isset($post['total_amount'])) {
			echo 'MISSING FIELDS';

			return;
		}

		$hash = base64_encode(hash_hmac('sha256', (string)$post['merchant_oid'] . $merchant_salt . (string)$post['status'] . (string)$post['total_amount'], $merchant_key, true));

		if (!isset($post['hash']) || !hash_equals($hash, (string)$post['hash'])) {
			echo 'BAD HASH';

			return;
		}

		if (!preg_match('/^(\d+)x/', (string)$post['merchant_oid'], $matches)) {
			echo 'BAD OID';

			return;
		}

		$order_id = (int)$matches[1];

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			echo 'ORDER NOT FOUND';

			return;
		}

		$success_status = (int)$this->config->get('payment_paytr_order_status_id');
		$fail_status = (int)$this->config->get('payment_paytr_fail_status_id');

		if ($success_status <= 0) {
			$success_status = 1;
		}

		if ($fail_status <= 0) {
			$fail_status = 10;
		}

		// Idempotent: do not process final states twice
		if ((int)$order_info['order_status_id'] === $success_status || (int)$order_info['order_status_id'] === $fail_status) {
			echo 'OK';

			return;
		}

		if ((string)$post['status'] === 'success') {
			$this->model_checkout_order->addHistory($order_id, $success_status, 'PayTR: ödeme alındı. (Tutar: ' . (string)$post['total_amount'] . ' kuruş, İşlem No: ' . (string)($post['payment_type'] ?? '') . ')', true);
		} else {
			$this->model_checkout_order->addHistory($order_id, $fail_status, 'PayTR: ödeme başarısız. ' . (string)($post['failed_reason_msg'] ?? '') . ' (Kod: ' . (string)($post['failed_reason_code'] ?? '') . ')', false);
		}

		echo 'OK';
	}
}
