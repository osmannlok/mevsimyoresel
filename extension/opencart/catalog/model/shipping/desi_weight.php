<?php
namespace Opencart\Catalog\Model\Extension\Opencart\Shipping;
/**
 * Class DesiWeight
 *
 * Ships cart items grouped per supplier. Each supplier is charged
 * separately with the desi/kg rule and the per-supplier cap, and the
 * checkout shows the grand total as a single shipping fee.
 *
 * Can be called from $this->load->model('extension/opencart/shipping/desi_weight');
 *
 * @package Opencart\Catalog\Model\Extension\Opencart\Shipping
 */
class DesiWeight extends \Opencart\System\Engine\Model {
	public function getQuote(array $address): array {
		$this->load->language('extension/opencart/shipping/desi_weight');

		$quote_data = [];

		if ($this->cart->hasShipping()) {
			$result = $this->getChargeable($this->cart->getProducts());

			$cost = (float)$result['total_cost'];
			$tax_class_id = (int)$this->config->get('shipping_desi_weight_tax_class_id');

			$name = $this->language->get('text_description');

			if (!empty($result['breakdown'])) {
				$name .= ' (' . $result['breakdown'] . ')';
			}

			$quote_data['desi_weight'] = [
				'code'         => 'desi_weight.desi_weight',
				'name'         => $name,
				'cost'         => $cost,
				'tax_class_id' => $tax_class_id,
				'text'         => $this->currency->format($this->tax->calculate($cost, $tax_class_id, $this->config->get('config_tax')), $this->session->data['currency'])
			];
		}

		$method_data = [];

		if ($quote_data) {
			$method_data = [
				'code'       => 'desi_weight',
				'name'       => $this->language->get('heading_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_desi_weight_sort_order'),
				'error'      => false
			];
		}

		return $method_data;
	}

	/**
	 * Group shippable cart products by supplier and calculate a separate
	 * shipping price for each supplier. The per-supplier cap from
	 * `shipping_desi_weight_max_cost` is applied to each supplier group.
	 *
	 * Note: the cart stores the line weight already multiplied by the quantity,
	 * so the kg total is summed directly while desi is computed per unit and
	 * multiplied by the quantity.
	 *
	 * @param array<int, array<string, mixed>> $products
	 *
	 * @return array{suppliers: array<string, array<string, mixed>>, total_cost: float, breakdown: string}
	 */
	public function getChargeable(array $products): array {
		$suppliers = $this->getSupplierMap();
		$groups = [];

		foreach ($products as $product) {
			if (empty($product['shipping'])) {
				continue;
			}

			$quantity = isset($product['quantity']) ? max(1, (int)$product['quantity']) : 1;

			$key = $this->getSupplierKey($product);
			$label = $suppliers[$key] ?? $key;

			// Cart line weight is already quantity x unit weight.
			$line_kg = $this->weight->convert((float)($product['weight'] ?? 0), (int)($product['weight_class_id'] ?? 1), $this->getDefaultWeightClassId());

			$length_cm = $this->length->convert((float)($product['length'] ?? 0), (int)($product['length_class_id'] ?? 1), $this->getDefaultLengthClassId());
			$width_cm = $this->length->convert((float)($product['width'] ?? 0), (int)($product['length_class_id'] ?? 1), $this->getDefaultLengthClassId());
			$height_cm = $this->length->convert((float)($product['height'] ?? 0), (int)($product['length_class_id'] ?? 1), $this->getDefaultLengthClassId());

			$line_desi = 0.0;

			if ($length_cm > 0 && $width_cm > 0 && $height_cm > 0) {
				$divisor = (float)$this->config->get('shipping_desi_weight_divisor');

				if ($divisor <= 0) {
					$divisor = 3000.0;
				}

				$line_desi = ($length_cm * $width_cm * $height_cm / $divisor) * $quantity;
			}

			if (!isset($groups[$key])) {
				$groups[$key] = [
					'label'    => $label,
					'raw_desi' => 0.0,
					'raw_kg'   => 0.0,
					'desi'     => 0,
					'kg'       => 0,
					'desi_cost' => 0.0,
					'kg_cost'  => 0.0,
					'cost'     => 0.0,
				];
			}

			$groups[$key]['raw_kg'] += $line_kg;
			$groups[$key]['raw_desi'] += $line_desi;
		}

		$rates = $this->getRates();
		$kg_rate = (float)$this->config->get('shipping_desi_weight_kg_rate');
		$max_cost = (float)$this->config->get('shipping_desi_weight_max_cost');

		if ($max_cost <= 0) {
			$max_cost = 500.0;
		}

		$currency = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
		$total_cost = 0.0;
		$breakdown = [];

		foreach ($groups as $key => $group) {
			$desi = (int)ceil($group['raw_desi']);
			$kg = (int)ceil($group['raw_kg']);

			$desi_cost = $this->resolveRate($desi, $rates);
			$kg_cost = $kg * $kg_rate;
			$cost = max($desi_cost, $kg_cost);

			// 500 TL cap applies per supplier, not per order.
			if ($cost > $max_cost) {
				$cost = $max_cost;
			}

			$groups[$key]['desi'] = $desi;
			$groups[$key]['kg'] = $kg;
			$groups[$key]['desi_cost'] = $desi_cost;
			$groups[$key]['kg_cost'] = $kg_cost;
			$groups[$key]['cost'] = $cost;

			$total_cost += $cost;
			$breakdown[] = $group['label'] . ': ' . $this->currency->format($cost, $currency);
		}

		return [
			'suppliers'  => $groups,
			'total_cost' => $total_cost,
			'breakdown'  => implode(' + ', $breakdown),
		];
	}

	private function getSupplierKey(array $product): string {
		$model = strtoupper(trim((string)($product['model'] ?? '')));

		if ($model === '') {
			return 'DIGER';
		}

		$position = strpos($model, '-');

		return $position === false ? $model : substr($model, 0, $position);
	}

	/**
	 * Parse the admin-configurable supplier map, e.g.
	 * "TUNA:Kahramanmaraş,HATAY:Hatay,KARSMEH:Kars,YM:Özel Tedarikçi"
	 *
	 * @return array<string, string>
	 */
	private function getSupplierMap(): array {
		$map = [];
		$raw = (string)$this->config->get('shipping_desi_weight_supplier_map');

		if (trim($raw) === '') {
			$raw = 'TUNA:Kahramanmaraş,HATAY:Hatay,KARSMEH:Kars,YM:Özel Tedarikçi';
		}

		foreach (explode(',', $raw) as $part) {
			$part = trim($part);

			if ($part === '' || strpos($part, ':') === false) {
				continue;
			}

			[$prefix, $label] = explode(':', $part, 2);
			$prefix = strtoupper(trim($prefix));
			$label = trim($label);

			if ($prefix !== '' && $label !== '') {
				$map[$prefix] = $label;
			}
		}

		return $map;
	}

	private function getRates(): array {
		$rates = [];
		$raw = (string)$this->config->get('shipping_desi_weight_desi_rates');

		foreach (explode(',', $raw) as $part) {
			$part = trim($part);

			if ($part === '') {
				continue;
			}

			$data = explode(':', $part, 2);

			if (count($data) !== 2) {
				continue;
			}

			$limit = (int)trim($data[0]);
			$price = trim($data[1]);

			if ($limit >= 0 && is_numeric($price)) {
				$rates[] = ['limit' => $limit, 'cost' => (float)$price];
			}
		}

		usort($rates, function(array $a, array $b) {
			return $a['limit'] <=> $b['limit'];
		});

		return $rates;
	}

	private function resolveRate(int $desi, array $rates): float {
		foreach ($rates as $rate) {
			if ($desi <= $rate['limit']) {
				return $rate['cost'];
			}
		}

		if ($rates) {
			return (float)end($rates)['cost'];
		}

		return 0.0;
	}

	private function getDefaultWeightClassId(): int {
		$id = (int)$this->config->get('config_weight_class_id');

		return $id > 0 ? $id : 1;
	}

	private function getDefaultLengthClassId(): int {
		$id = (int)$this->config->get('config_length_class_id');

		return $id > 0 ? $id : 1;
	}
}
