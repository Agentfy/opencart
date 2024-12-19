<?php
class ModelExtensionAgentfyManufacturers extends Model {

	private $limit = 25;

	public function index($sourceId, $last_step, $store_id) {
		$count = $this->db->query("SELECT COUNT(*) AS `c` FROM `" . DB_PREFIX . "manufacturer`");
		$manufacturers = $this->db->query( "SELECT * FROM `" . DB_PREFIX . "manufacturer` LIMIT " . ( $this->limit * $last_step ) . ', ' . $this->limit );
		foreach ($manufacturers->rows as $manufacturer) {
			$this->indexManufacturer($manufacturer['manufacturer_id'], $sourceId, $store_id);

		}
		return $count->row['c'];;
	}


	public function indexManufacturer($manufacturerId, $sourceId, $store_id)
	{
		$this->load->model('extension/agentfy/api');
		$this->load->model('catalog/manufacturer');
		$this->load->model('design/seo_url');
		$manufacturerInfo = $this->model_catalog_manufacturer->getManufacturer($manufacturerId);
		$document = $this->model_extension_agentfy_api->getDocument($sourceId, $manufacturerId, $store_id);
	
        $this->load->model("setting/setting");
        $module_setting = $this->model_setting_setting->getSetting(
            "module_agentfy",
            $store_id
        );
		$setting = $module_setting["module_agentfy_setting"];

		$template = $setting['manufacturer_template'];

		$metadata = [];
        $metadata["title"] = html_entity_decode($manufacturerInfo['name'],ENT_QUOTES, 'UTF-8');

		$seo_url_data = $this->model_design_seo_url->getSeoUrlsByQuery('manufacturer_id=' . $manufacturerId);
		$url = HTTP_CATALOG . 'index.php?route=product/manufacturer&manufacturer_id=' . $manufacturerId;

		if ($seo_url_data) {
			$url = HTTP_CATALOG . $seo_url_data[0]['keyword'];
		}

		$metadata["source"] = $url;
		$search = [
			'%title%',
			'%seoUrl%',
		];

		$replace = [
			html_entity_decode($manufacturerInfo['name'],ENT_QUOTES, 'UTF-8'),
			$url,
		];

		$pageContent = str_replace($search, $replace, $template);

		if (!empty($document)) {
			$this->model_extension_agentfy_api->updateDocument(
				$sourceId,
				$document['id'],
				$document['summary'],
				$manufacturerId,
				html_entity_decode($manufacturerInfo['name'],ENT_QUOTES, 'UTF-8'), 
				$pageContent,
				$metadata,
				$store_id
			);
			$this->model_extension_agentfy_api->indexDocument($sourceId, $document['id'], $store_id);
		} else {
			$this->model_extension_agentfy_api->addDocument(
				$sourceId,
				$manufacturerId,
				$manufacturerInfo['name'],
				$pageContent,
				$metadata,
				$store_id
			);
		}
	}

}