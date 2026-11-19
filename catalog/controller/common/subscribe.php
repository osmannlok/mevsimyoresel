<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Subscribe
 *
 * Newsletter (e-bülten) kaydı — alt bilgideki form bu uç noktaya POST yapar.
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Subscribe extends \Opencart\System\Engine\Controller {
	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('common/subscribe');

		$json = [];

		if (!isset($this->request->get['newsletter_token']) || !isset($this->session->data['newsletter_token']) || $this->request->get['newsletter_token'] != $this->session->data['newsletter_token']) {
			$json['error']['warning'] = $this->language->get('error_token');
		}

		$email = isset($this->request->post['email']) ? trim((string)$this->request->post['email']) : '';

		$consent = !empty($this->request->post['consent']);

		if (!$email || !oc_validate_email($email)) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		if (!$consent) {
			$json['error']['consent'] = $this->language->get('error_consent');
		}

		if (!$json) {
			$store_id = (int)$this->config->get('config_store_id');

			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "newsletter_subscriber` WHERE `email` = '" . $this->db->escape($email) . "' AND `store_id` = '" . $store_id . "'");

			if ($query->num_rows) {
				$json['error']['warning'] = $this->language->get('error_duplicate');
			} else {
				$ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : '';

				$this->db->query("INSERT INTO `" . DB_PREFIX . "newsletter_subscriber` SET `store_id` = '" . $store_id . "', `email` = '" . $this->db->escape($email) . "', `consent` = '1', `ip` = '" . $this->db->escape($ip) . "', `date_added` = NOW()");

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
