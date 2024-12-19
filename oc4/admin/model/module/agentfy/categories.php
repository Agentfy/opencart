<?php
namespace Opencart\Admin\Model\Extension\Agentfy\Module\Agentfy;

class Categories extends \Opencart\System\Engine\Model {

	private $codename = 'agentfy';
	private $limit = 25;


	public function index($sourceId, $last_step, $store_id)
	{
		$count = $this->db->query("SELECT COUNT(*) AS `c` FROM `" . DB_PREFIX . "category`");

        $categories = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category` LIMIT " . ($this->limit * $last_step) . ', ' . $this->limit);

        foreach ($categories->rows as $category) {
			$this->indexCategory($category['category_id'], $sourceId, $store_id);
		}

		return $count->row['c'];
	}



	public function indexCategory($categoryId, $sourceId, $store_id)
	{
		$this->load->model('catalog/category');
		$this->load->model('extension/agentfy/module/agentfy/api');
		$this->load->model('design/seo_url');
		$categoryInfo = $this->model_catalog_category->getCategory($categoryId);
		$document = $this->model_extension_agentfy_module_agentfy_api->getDocument($sourceId, $categoryId, $store_id);

        $this->load->model("setting/setting");
        $module_setting = $this->model_setting_setting->getSetting(
            "module_agentfy",
            $store_id
        );
		$setting = $module_setting["module_agentfy_setting"];

		$template = $setting['category_template'];

		$metadata = [];
		$categoryPath = ($categoryInfo['path']) ? $categoryInfo['path'] . ' > ' . $categoryInfo['name'] : $categoryInfo['name'];
		$decodedCategoryPath = html_entity_decode($categoryPath);
        $metadata["title"] = $decodedCategoryPath;

		$seo_url_data = $this->model_design_seo_url->getSeoUrlByKeyword('category_id=' . $categoryId, $store_id, $this->config->get('config_language_id'));
		$url = HTTP_CATALOG . 'index.php?route=product/category&path=' . $categoryId;

		if ($seo_url_data) {
			$url = HTTP_CATALOG . $seo_url_data[0]['keyword'];
		}

		$metadata["source"] = $url;
		$search = [
			'%title%',
			'%description%',
			'%seoUrl%',
		];

		$replace = [
			$decodedCategoryPath,
			strip_tags(html_entity_decode($categoryInfo['description'],ENT_QUOTES, 'UTF-8')),
			$url,
		];

		$pageContent = str_replace($search, $replace, $template);

        if (!empty($document)) {
			$this->model_extension_agentfy_module_agentfy_api->updateDocument(
				$sourceId,
				$document['id'],
				$document['summary'],
				$categoryId,
				$decodedCategoryPath,
				$pageContent,
				$metadata,
				$store_id
			);
			$this->model_extension_agentfy_module_agentfy_api->indexDocument($sourceId, $document['id'], $store_id);
		} else {
			$this->model_extension_agentfy_module_agentfy_api->addDocument(
				$sourceId,
				$categoryId,
				$decodedCategoryPath,
				$pageContent,
				$metadata,
				$store_id
			);
		}
	}
}
