<?php
namespace Opencart\Admin\Model\Extension\Agentfy\Module;

class Agentfy extends \Opencart\System\Engine\Model {
    private $codename = "agentfy";

    private function addEvent($args) {
        $this->load->model("setting/event");
        if (VERSION >= '4.0.2.0') {
			$this->model_setting_event->addEvent([
                'code' => $args['code'],
                'description' => $args['description'],
                'trigger' => $args['trigger'],
                'action' => $args['action'],
                'status' => $args['status'],
                'sort_order' => $args['sort_order']
            ]);
        } elseif (VERSION >= '4.0.1.0') {
			$this->model_setting_event->addEvent([
                'code' => $args['code'],
                'description' => $args['description'],
                'trigger' => $args['trigger'],
                'action' => $args['action'],
                'status' => $args['status'],
                'sort_order' => $args['sort_order']
            ]);
        } else {
			$this->model_setting_event->addEvent(
                $args['code'],
                $args['description'],
                $args['trigger'],
                $args['action'],
                $args['status'],
                $args['sort_order']
            );
        }
    }

    public function installEvents()
    {
        $separator = '|';
        if (VERSION >= '4.0.2.0') {
			$separator = '.';
		}
        $this->load->model("setting/event");
        $this->addEvent([
            "code"=> $this->codename,
            "description" => "",
            "trigger" => "admin/model/catalog/product/addProduct/after",
            "action" => "extension/agentfy/module/agentfy".$separator."addProduct",
            "status" => true,
            "sort_order" => 0
        ]);
        $this->addEvent([
            "code"=> $this->codename,
            "description" => "",
            "trigger" => "admin/model/catalog/product/editProduct/after",
            "action" => "extension/agentfy/module/agentfy".$separator."editProduct",
            "status" => true,
            "sort_order" => 0
        ]);
        $this->addEvent([
            "code"=> $this->codename,
            "description" => "",
            "trigger" => "admin/model/catalog/category/addCategory/after",
            "action" => "extension/agentfy/module/agentfy".$separator."addCategory",
            "status" => true,
            "sort_order" => 0
        ]);
        $this->addEvent([
            "code"=> $this->codename,
            "description" => "",
            "trigger" => "admin/model/catalog/category/editCategory/after",
            "action" => "extension/agentfy/module/agentfy".$separator."editCategory",
            "status" => true,
            "sort_order" => 0
        ]);
        $this->addEvent([
            "code"=> $this->codename,
            "description" => "",
            "trigger" => "admin/model/catalog/manufacturer/addManufacturer/after",
            "action" => "extension/agentfy/module/agentfy".$separator."addManufacturer",
            "status" => true,
            "sort_order" => 0
        ]);
        $this->addEvent([
            "code"=> $this->codename,
            "description" => "",
            "trigger" => "admin/model/catalog/manufacturer/editManufacturer/after",
            "action" => "extension/agentfy/module/agentfy".$separator."editManufacturer",
            "status" => true,
            "sort_order" => 0
        ]);
        $this->addEvent([
            "code"=> $this->codename . "_content_top",
            "description" => "",
            "trigger" => "catalog/controller/common/content_top/before",
            "action" => "extension/agentfy/module/agentfy".$separator."content_top_before",
            "status" => true,
            "sort_order" => 0
        ]);
    }
    public function uninstallEvents()
    {
        $this->load->model("setting/event");
        $this->model_setting_event->deleteEventByCode("agentfy_content_top");
        $this->model_setting_event->deleteEventByCode("agentfy");
    }

    public function getSourceId($type, $store_id = 0)
    {
        $this->load->model("setting/setting");

        $module_setting = $this->model_setting_setting->getSetting(
            "module_agentfy",
            $store_id
        );

        if (!isset($module_setting["module_agentfy_sources"])) {
            $module_setting["module_agentfy_sources"] = [];
        }
        if (!empty($module_setting["module_agentfy_sources"][$type])) {
            return $module_setting["module_agentfy_sources"][$type];
        }
    }

    public function getKnowledgeId($store_id = 0)
    {
        $this->load->model("setting/setting");

        $module_setting = $this->model_setting_setting->getSetting(
            "module_agentfy",
            $store_id
        );
        if (!empty($module_setting["module_agentfy_knowledge"])) {
            return $module_setting["module_agentfy_knowledge"];
        }
    }

    public function createSources($store_id)
    {
        $this->load->model("extension/agentfy/module/agentfy/api");
        $types = ["products", "categories", "manufacturers"];
        foreach ($types as $type) {
            $this->model_extension_agentfy_module_agentfy_api->addSource($type, $store_id);
        }
    }

    public function removeSources($store_id)
    {
        $this->load->model("extension/agentfy/module/agentfy/api");
        $types = ["products", "categories", "manufacturers"];
        foreach ($types as $type) {
            $sourceId = $this->getSourceId($type);
            if (!empty($sourceId)) {
                $this->model_extension_agentfy_module_agentfy_api->removeSource($sourceId, $store_id);
            }
        }
    }

    public function indexing($type, $store_id)
    {
        $cache = "agentfy_indexing";

        $this->load->model("setting/setting");
        $this->load->model("extension/agentfy/module/agentfy/api");
        $this->load->model("extension/agentfy/module/agentfy/categories");
        $this->load->model("extension/agentfy/module/agentfy/products");
        $this->load->model("extension/agentfy/module/agentfy/manufacturers");

        $sourceId = $this->getSourceId($type, $store_id);

        $source = $this->model_extension_agentfy_module_agentfy_api->getSource($sourceId, $store_id);
        if (!$source) {
            throw new Exception("not found source");
            return;
        }
        $steps = [$type];
        if ($source["status"] != "indexed") {
            array_push($steps, "indexing");
        }

        if (file_exists($cache)) {
            $this->session->data[
                "agentfy_indexing_progress_".$store_id
            ] = $this->cache->get($cache);
        }

        if (!isset($this->session->data["agentfy_indexing_progress_".$store_id])) {
            $this->session->data["agentfy_indexing_progress_".$store_id] = [
                "step" => 0,
                "last_step" => 0,
            ];
        }

        $limit = 10;
        $step = $this->session->data["agentfy_indexing_progress_".$store_id]["step"];
        $last_step =
            $this->session->data["agentfy_indexing_progress_".$store_id]["last_step"];
        $countItems = 0;

        if ($steps[$step] === "indexing") {
            $this->model_extension_agentfy_module_agentfy_api->indexSource($sourceId, $store_id);
        }

        if ($steps[$step] === "products") {
            $countItems = $this->model_extension_agentfy_module_agentfy_products->index(
                $sourceId,
                $last_step,
                $store_id
            );

            $last_step++;
        }
        if ($steps[$step] === "manufacturers") {
            $countItems = $this->model_extension_agentfy_module_agentfy_manufacturers->index(
                $sourceId,
                $last_step,
                $store_id
            );

            $last_step++;
        }

        if ($steps[$step] === "categories") {
            $countItems = $this->model_extension_agentfy_module_agentfy_categories->index(
                $sourceId,
                $last_step,
                $store_id
            );

            $last_step++;
        }

        $progress = $countItems
            ? round(($source["documentCount"] / $countItems) * 100, 3)
            : 100;

        if ($progress >= 100) {
            $step++;
            $last_step = 0;
            $progress = 0;
        }

        $return = [
            "steps" => count($steps),
            "current" => $source["documentCount"],
            "count" => $countItems,
            "progress" => $progress > 100 ? 100 : $progress,
            "last_step" => $last_step,
            "step" => $step + 1,
        ];

        if ($step >= count($steps)) {
            unset($this->session->data["agentfy_indexing_progress_".$store_id]);

            if (file_exists($cache)) {
                unlink($cache);
            }

            $this->load->model("setting/setting");

            $this->model_setting_setting->editSetting(
                $this->codename . "_cache",
                [
                    $this->codename . "_cache" => ["status" => true],
                ],
                $store_id
            );
            $return["step"] = $return["steps"];
            $return["success"] = true;
        } else {
            $this->session->data["agentfy_indexing_progress_".$store_id][
                "last_step"
            ] = $last_step;
            $this->session->data["agentfy_indexing_progress_".$store_id]["step"] = $step;

            $this->cache->set(
                $cache,
                $this->session->data["agentfy_indexing_progress_".$store_id]
            );
        }

        return $return;
    }

    public function getStores()
    {
        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        $result = array();
        if ($stores) {
            $result[] = array(
                'store_id' => 0,
                'name'     => $this->config->get('config_name')
            );
            foreach ($stores as $store) {
                $result[] = array(
                    'store_id' => $store['store_id'],
                    'name'     => $store['name']
                );
            }
        }
        return $result;
    }
}
