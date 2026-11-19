<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Menu
 *
 * Can be called from $this->load->controller('common/menu');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Menu extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$data['products_href'] = $this->url->link('information/sitemap', 'language=' . $this->config->get('config_language'));
		$data['home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));
		$data['contact'] = $this->url->link('information/contact', 'language=' . $this->config->get('config_language'));

		$this->load->language('common/menu');

		// Blog (makaleler/konular) – varsa ekle ve göster
		$this->load->model('cms/article');

		$article_total = $this->model_cms_article->getTotalArticles();

		if ($article_total) {
			$data['blog'] = $this->url->link('cms/blog', 'language=' . $this->config->get('config_language'));
		} else {
			$data['blog'] = '';
		}

		// Category
		$this->load->model('catalog/category');

		// Product
		$this->load->model('catalog/product');

		$data['categories'] = [];

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			// Level 2
			$children_data = [];

			$children = $this->model_catalog_category->getCategories($category['category_id']);

			foreach ($children as $child) {
				$filter_data = [
					'filter_category_id'  => $child['category_id'],
					'filter_sub_category' => true
				];

				$children_data[] = [
					'name' => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category['category_id'] . '_' . $child['category_id'])
				];
			}

			// Level 1
			$data['categories'][] = [
				'children' => $children_data,
				'href'     => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category['category_id'])
			] + $category;
		}

		return $this->load->view('common/menu', $data);
	}
}
