<?php
class ModelExtensionModuleAgentfy extends Model
{
    private $codename = "agentfy";

    public function installEvents()
    {
        $this->load->model("setting/event");
        $this->model_setting_event->addEvent(
            $this->codename,
            "admin/model/catalog/product/addProduct/after",
            "extension/module/agentfy/addProduct",
            1,
            0
        );
        $this->model_setting_event->addEvent(
            $this->codename,
            "admin/model/catalog/product/editProduct/after",
            "extension/module/agentfy/editProduct",
            1,
            0
        );

        $this->model_setting_event->addEvent(
            $this->codename,
            "admin/model/catalog/category/addCategory/after",
            "extension/module/agentfy/addCategory",
            1,
            0
        );
        $this->model_setting_event->addEvent(
            $this->codename,
            "admin/model/catalog/category/editCategory/after",
            "extension/module/agentfy/editCategory",
            1,
            0
        );

        $this->model_setting_event->addEvent(
            $this->codename,
            "admin/model/catalog/manufacturer/addManufacturer/after",
            "extension/module/agentfy/addManufacturer",
            1,
            0
        );
        $this->model_setting_event->addEvent(
            $this->codename,
            "admin/model/catalog/manufacturer/editManufacturer/after",
            "extension/module/agentfy/editManufacturer",
            1,
            0
        );

        $this->model_setting_event->addEvent(
            $this->codename . "_content_top",
            "catalog/controller/common/content_top/before",
            "extension/module/agentfy/content_top_before"
        );
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
        $this->load->model("extension/agentfy/api");
        $types = ["products", "categories", "manufacturers"];
        foreach ($types as $type) {
            $this->model_extension_agentfy_api->addSource($type, $store_id);
        }
    }

    public function removeSources($store_id)
    {
        $this->load->model("extension/agentfy/api");
        $types = ["products", "categories", "manufacturers"];
        foreach ($types as $type) {
            $sourceId = $this->getSourceId($type);
            if (!empty($sourceId)) {
                $this->model_extension_agentfy_api->removeSource($sourceId, $store_id);
            }
        }
    }

    public function indexing($type, $store_id)
    {
        $cache = "agentfy_indexing";

        $this->load->model("setting/setting");
        $this->load->model("extension/agentfy/api");
        $this->load->model("extension/agentfy/categories");
        $this->load->model("extension/agentfy/products");
        $this->load->model("extension/agentfy/manufacturers");

        $sourceId = $this->getSourceId($type, $store_id);

        $source = $this->model_extension_agentfy_api->getSource($sourceId, $store_id);
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
            $this->model_extension_agentfy_api->indexSource($sourceId, $store_id);
        }

        if ($steps[$step] === "products") {
            $countItems = $this->model_extension_agentfy_products->index(
                $sourceId,
                $last_step,
                $store_id
            );

            $last_step++;
        }
        if ($steps[$step] === "manufacturers") {
            $countItems = $this->model_extension_agentfy_manufacturers->index(
                $sourceId,
                $last_step,
                $store_id
            );

            $last_step++;
        }

        if ($steps[$step] === "categories") {
            $countItems = $this->model_extension_agentfy_categories->index(
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
