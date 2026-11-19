<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Home
 *
 * Can be called from $this->load->controller('common/home');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Home extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$description = $this->config->get('config_description');
		$language_id = $this->config->get('config_language_id');

		if (isset($description[$language_id])) {
			$this->document->setTitle($description[$language_id]['meta_title']);
			$this->document->setDescription($description[$language_id]['meta_description']);
			$this->document->setKeywords($description[$language_id]['meta_keyword']);
		}

		// Hero slides come from the "Home Page Slideshow" banner set (Admin → Design → Banners)
		$this->load->model('design/banner');

		$banner_id = $this->model_design_banner->getBannerIdByName('Home Page Slideshow');

		$data['slides'] = [];

		if ($banner_id) {
			foreach ($this->model_design_banner->getBanner($banner_id) as $result) {
				if (is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
					$data['slides'][] = [
						'title' => $result['title'],
						'subtext' => $result['subtext'] ?? '',
						'link'  => $result['link'],
						'image' => $this->config->get('config_url') . 'image/' . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')
					];
				}
			}
		}

		$data['hero_subtext'] = $this->config->get('config_hero_subtext');

		// Category cards (square image + name) for the homepage
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		// Hikayemiz bandı ve güven bölümü bağlantıları
		$language = $this->config->get('config_language');

		$data['story_image'] = $this->model_tool_image->resize('catalog/yuklediklerim/yellow-1.jpg', 1600, 500);
		$data['link_about'] = $this->url->link('information/information', 'language=' . $language . '&information_id=1');
		$data['link_delivery'] = $this->url->link('information/information', 'language=' . $language . '&information_id=4');
		$data['link_terms'] = $this->url->link('information/information', 'language=' . $language . '&information_id=2');
		$data['link_contact'] = $this->url->link('information/contact', 'language=' . $language);
		$data['link_order'] = $this->url->link('account/order', 'language=' . $language . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));

		$data['categories_with_images'] = [];

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$image = $category['image'] ?? '';

			if (!$image) {
				$products = $this->model_catalog_product->getProducts(['filter_category_id' => (int)$category['category_id'], 'start' => 0, 'limit' => 1]);

				$image = $products[0]['image'] ?? '';
			}

			$image_url = '';

			if ($image && is_file(DIR_IMAGE . html_entity_decode($image, ENT_QUOTES, 'UTF-8'))) {
				$image_url = $this->model_tool_image->resize(html_entity_decode($image, ENT_QUOTES, 'UTF-8'), 200, 200);
			}

			$data['categories_with_images'][] = [
				'name' => $category['name'],
				'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category['category_id']),
				'image' => $image_url
			];
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
