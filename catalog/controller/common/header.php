<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Header
 *
 * Can be called from $this->load->controller('common/header');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Header extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(array $args = []): string {
		// Analytics
		$data['analytics'] = [];

		if (!$this->config->get('config_cookie_id') || (isset($this->request->cookie['policy']) && $this->request->cookie['policy'])) {
			// Extension
			$this->load->model('setting/extension');

			$analytics = $this->model_setting_extension->getExtensionsByType('analytics');

			foreach ($analytics as $analytic) {
				if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
					$data['analytics'][] = $this->load->controller('extension/' . $analytic['extension'] . '/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
				}
			}
		}

		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		// Blog (makaleler/konular) – varsa mobil menüde göster
		$this->load->model('cms/article');

		$article_total = $this->model_cms_article->getTotalArticles();

		if ($article_total) {
			$language_code = $this->config->get('config_language');

			$data['blog'] = $this->url->link('cms/blog', 'language=' . $language_code);
		} else {
			$data['blog'] = '';
		}

		$data['title'] = $this->document->getTitle();

		// Open Graph: sayfa türü ve görseli (varsayılan: website + logo)
		$data['og_type'] = isset($args['og_type']) && in_array($args['og_type'], ['website', 'product', 'article'], true) ? $args['og_type'] : 'website';
		$data['og_image'] = isset($args['og_image']) && is_string($args['og_image']) ? trim($args['og_image']) : '';

		// Canonical URL (sayfaya özgü; dil ve izleme parametreleri çıkarılır, yalnızca "page" korunur)
		$request_uri = $_SERVER['REQUEST_URI'] ?? '/'; // OpenCart $this->request->server '&' karakterini '&amp;' yapar; ham istek yolu kullanılır
		$config_url = (string)$this->config->get('config_url');
		$base_path = parse_url($config_url, PHP_URL_PATH) ?: '/';
		$path = (string)(parse_url($request_uri, PHP_URL_PATH) ?: '/');

		if ($base_path !== '/') {
			$base_path = rtrim($base_path, '/');

			if (strpos($path, $base_path) === 0) {
				$path = substr($path, strlen($base_path));
			}

			$path = '/' . ltrim($path, '/');
		}

		$query_parts = [];
		$query_string = parse_url($request_uri, PHP_URL_QUERY);

		if ($query_string) {
			parse_str($query_string, $query_parts);

			unset($query_parts['language']);

			foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'gbraid', 'wbraid', 'fbclid'] as $junk) {
				unset($query_parts[$junk]);
			}

			if (isset($query_parts['page']) && (int)$query_parts['page'] <= 1) {
				unset($query_parts['page']);
			}

			if (isset($query_parts['page'])) {
				$query_parts['page'] = (int)$query_parts['page'];
			}
		}

		$query = $query_parts ? '?' . str_replace('%2F', '/', http_build_query($query_parts)) : '';

		$data['canonical'] = rtrim($config_url, '/') . $path . $query;
		$data['base'] = $this->config->get('config_url');
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();

		// Hard coding css, so they can be replaced via the event's system.
		$data['bootstrap'] = 'catalog/view/stylesheet/bootstrap.css';
		$data['icons'] = 'catalog/view/stylesheet/fonts/fontawesome/css/all.min.css';
		$data['stylesheet'] = 'catalog/view/stylesheet/stylesheet.css';

		// Hard coding scripts, so they can be replaced via the event's system.
		$data['jquery'] = 'catalog/view/javascript/jquery/jquery-3.7.1.min.js';

		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');

		$data['name'] = $this->config->get('config_name');

		// Fav icon
		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$data['icon'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_icon');
		} else {
			$data['icon'] = '';
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist($this->customer->getId()));
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
		$data['wishlist'] = $this->url->link('account/wishlist', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['logged'] = $this->customer->isLogged();

		if (!$this->customer->isLogged()) {
			$data['register'] = $this->url->link('account/register', 'language=' . $this->config->get('config_language'));
			$data['login'] = $this->url->link('account/login', 'language=' . $this->config->get('config_language'));
		} else {
			$data['account'] = $this->url->link('account/account', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);
			$data['order'] = $this->url->link('account/order', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);
			$data['transaction'] = $this->url->link('account/transaction', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);
			$data['download'] = $this->url->link('account/download', 'language=' . $this->config->get('config_language') . '&customer_token=' . $this->session->data['customer_token']);
			$data['logout'] = $this->url->link('account/logout', 'language=' . $this->config->get('config_language'));
		}

		$data['shopping_cart'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'));
		$data['checkout'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
		$data['contact'] = $this->url->link('information/contact', 'language=' . $this->config->get('config_language'));
		$data['telephone'] = $this->config->get('config_telephone');

		$data['language'] = $this->load->controller('common/language');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		$data['menu'] = $this->load->controller('common/menu');

		return $this->load->view('common/header', $data);
	}
}
