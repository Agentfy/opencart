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
            $response = $this->model_extension_agentfy_module_agentfy_api->getKnowledge($module_setting["module_agentfy_knowledge"], $store_id);
            if (!empty($response)) {
                return $module_setting["module_agentfy_knowledge"];
            }
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

    public function indexing($mode, $type, $store_id)
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
        if ($mode == 'reset') {
            $steps = ["deleteIndexes", "deleteDocuments"];
        } else {
            $steps = [$type];
        }
        if ($source["status"] != "indexed" && $mode != 'reset') {
            
            array_push($steps, "indexing");
        }

        if (file_exists($cache)) {
            $this->session->data[
                "agentfy_indexing_progress_".$type."_".$store_id
            ] = $this->cache->get($cache);
        }

        if (!isset($this->session->data[ "agentfy_indexing_progress_".$type."_".$store_id])) {
            $this->session->data[ "agentfy_indexing_progress_".$type."_".$store_id] = [
                "step" => 0,
                "last_step" => 0,
            ];
        }
        $limit = 10;
        $step = $this->session->data[ "agentfy_indexing_progress_".$type."_".$store_id]["step"];
        $last_step =
            $this->session->data[ "agentfy_indexing_progress_".$type."_".$store_id]["last_step"];
        $countItems = 0;

        if ($steps[$step] === "deleteIndexes") {
            $this->model_extension_agentfy_module_agentfy_api->deleteAllIndexes($sourceId, $store_id);
            $source = $this->model_extension_agentfy_module_agentfy_api->getSource($sourceId, $store_id);
        }
        if ($steps[$step] === "deleteDocuments") {
            $this->model_extension_agentfy_module_agentfy_api->deleteAllDocuments($sourceId, $store_id);
            $source = $this->model_extension_agentfy_module_agentfy_api->getSource($sourceId, $store_id);
        }
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
            ? round((($last_step * $limit) / $countItems) * 100, 3)
            : 100;

        if ($progress >= 100) {
            $step++;
            $last_step = 0;
            $progress = 0;
        }

        $return = [
            "steps" => count($steps),
            "current" =>  ($last_step * $limit),
            "count" => $countItems,
            "documentCount" => $source["documentCount"],
            "progress" => $progress > 100 ? 100 : $progress,
            "last_step" => $last_step,
            "step" => $step + 1,
        ];

        if ($step >= count($steps)) {
            unset($this->session->data["agentfy_indexing_progress_".$type."_".$store_id]);

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
            $this->session->data["agentfy_indexing_progress_".$type."_".$store_id][
                "last_step"
            ] = $last_step;
            $this->session->data["agentfy_indexing_progress_".$type."_".$store_id]["step"] = $step;

            $this->cache->set(
                $cache,
                $this->session->data["agentfy_indexing_progress_".$type."_".$store_id]
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

    

    public function getAllStores()
    {
        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        $result = array();
        $result[] = array(
            'store_id' => 0,
            'name'     => $this->config->get('config_name')
        );
        if ($stores) {
            foreach ($stores as $store) {
                $result[] = array(
                    'store_id' => $store['store_id'],
                    'name'     => $store['name']
                );
            }
        }
        return $result;
    }

    public function getPrompt($store_id) {
        $prompt = "Role: You are a friendly and knowledgeable virtual assistant for [Store Name], an eCommerce platform specializing in [Product Category]. Your primary goal is to assist customers with any questions they may have, provide tailored recommendations, guide them through purchases, and ensure a smooth shopping experience.

Tone: Your tone is professional yet conversational, ensuring customers feel valued and understood. Use simple and clear language, with a touch of warmth and enthusiasm to enhance the user experience.

Key Features & Behavior
Product Discovery:

Help customers explore products based on their preferences.
Provide comparisons, key features, and unique selling points.
Example: \"Are you looking for something specific, like [Product Type], or would you like me to suggest our bestsellers?\"
Customer Support:

Answer questions about product availability, specifications, shipping, and returns.
Provide clear steps for resolving issues (e.g., how to track an order or initiate a return).
Example: \"If you\'d like to track your order, you can do so by visiting [Tracking Page URL] or providing me your order number.\"
Personalized Recommendations:

Use context to suggest products or offers tailored to the customer’s needs.
Example: \"Since you’re interested in [Product Category], you might love our new [Product Line/Collection]. Would you like to know more?\"
Upselling & Promotions:

Inform customers about ongoing deals, bundles, or complementary items.
Example: \"Pairing [Product] with [Accessory] could be a great choice! We’re also offering 20% off this combo.\"
Engaging FAQs:

Handle frequently asked questions about policies, payment methods, and support hours.
Example: \"Yes, we ship internationally! Shipping times and costs vary based on your location. Let me know where you’re based, and I’ll provide details.\"
Polished Closing:

Always close interactions positively, encouraging further assistance.
Example: \"Is there anything else I can assist you with? I’m here to help!\"";

        $stores = $this->getAllStores();

        $store = array_filter($stores, function ($store) use ($store_id) {
            return $store["store_id"] == $store_id;
        });
        $store = $store[0];

        $this->load->model("catalog/category");
        $categories = $this->model_catalog_category->getCategories(["start" => 0, "limit" => 2]);
        $category = $this->model_catalog_category->getCategory($categories[0]["category_id"]);
        $categorySecond = $this->model_catalog_category->getCategory($categories[1]["category_id"]);

        $this->load->model("catalog/product");
        $products = $this->model_catalog_product->getProducts(["start" => 0, "limit" => 2]);
        $product = $products[0];
        $productSecond = $products[1];

        return str_replace(
            ["[Store Name]", "[Product Category]", "[Product]", "[Tracking Page URL]", "[Product Line/Collection]", "[Accessory]", "[Product Type]"], 
            [$store["name"], $category["name"], $product["name"], HTTP_CATALOG, $categorySecond["name"], $productSecond["name"], $categorySecond["name"]], 
            $prompt
        );
    }
}
