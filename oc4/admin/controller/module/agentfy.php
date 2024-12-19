<?php
namespace Opencart\Admin\Controller\Extension\Agentfy\Module;
/**
 * Class Agentfy
 *
 * @package Opencart\Admin\Controller\Module\Agentfy
 */
class Agentfy extends \Opencart\System\Engine\Controller 
{
	private $error = [];
	private $separator = '';
    private $store_id = 0;
    private $store;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->store_id = (isset($this->request->get['store_id'])) ? $this->request->get['store_id'] : 0;

		if (VERSION >= '4.0.2.0') {
			$this->separator = '.';
		} else {
			$this->separator = '|';
		}

        $this->load->model('setting/store');
        $this->store = $this->model_setting_store->getStore($this->store_id);
    }

    public function index()
    {
        $_config = new \Opencart\System\Engine\Config();
        $_config->addPath(DIR_EXTENSION . 'agentfy/system/config/');
        $_config->load('agentfy');
        
        $this->load->language("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/api");


        $this->document->addScript(HTTP_CATALOG.'extension/agentfy/admin/view/javascript/agentfy/ai_autocomplete.js');
        $this->document->setTitle($this->language->get("heading_title"));

        $this->load->model("setting/setting");

        if (isset($this->error["warning"])) {
            $data["error_warning"] = $this->error["warning"];
        } else {
            $data["error_warning"] = "";
        }

        if (isset($this->error["api_key"])) {
            $data["error_api_key"] = $this->error["api_key"];
        } else {
            $data["error_api_key"] = "";
        }

        if (isset($this->error["agent_id"])) {
            $data["error_agent_id"] = $this->error["agent_id"];
        } else {
            $data["error_agent_id"] = "";
        }
        $data['separator'] = $this->separator;

        $data["breadcrumbs"] = [];

        $data["breadcrumbs"][] = [
            "text" => $this->language->get("text_home"),
            "href" => $this->url->link(
                "common/dashboard",
                "user_token=" . $this->session->data["user_token"],
                true
            ),
        ];

        $data["breadcrumbs"][] = [
            "text" => $this->language->get("text_extension"),
            "href" => $this->url->link(
                "marketplace/extension",
                "user_token=" .
                    $this->session->data["user_token"] .
                    "&type=module",
                true
            ),
        ];

        $data["breadcrumbs"][] = [
            "text" => $this->language->get("heading_title"),
            "href" => $this->url->link(
                "extension/agentfy/module/agentfy",
                "user_token=" . $this->session->data["user_token"],
                true
            ),
        ];

        $data["action"] = $this->url->link(
            "extension/agentfy/module/agentfy".$this->separator."save",
            "user_token=" . $this->session->data["user_token"]."&store_id=".$this->store_id,
            true
        );

        $data['module_link'] = $this->url->link(
            "extension/agentfy/module/agentfy",
            "user_token=" . $this->session->data["user_token"],
            true
        );

        $data["createAgent"] = $this->url->link(
            "extension/agentfy/module/agentfy".$this->separator."createAgent",
            "user_token=" . $this->session->data["user_token"]."&store_id=".$this->store_id,
            true
        );

        $data['store_id'] = $this->store_id;

        $data["types"] = [
            [
                "title" => $this->language->get("text_products"),
                "code" => "products",
                "source" => [],
            ],
            [
                "title" => $this->language->get("text_categories"),
                "code" => "categories",
                "source" => [],
            ],
            [
                "title" => $this->language->get("text_manufacturers"),
                "code" => "manufacturers",
                "source" => [],
            ],
        ];

        $data['store_name'] = $this->store_id == 0 ? $this->config->get('config_name') : $this->store['name'];

        $data["sourceAction"] = str_replace(
            "&amp;",
            "&",
            $this->url->link(
                "extension/agentfy/module/agentfy".$this->separator."createSource",
                "user_token=" . $this->session->data["user_token"]."&store_id=".$this->store_id,
                true
            )
        );

        $data["cancel"] = $this->url->link(
            "marketplace/extension",
            "user_token=" . $this->session->data["user_token"] . "&type=module",
            true
        );

        $data['stores'] = $this->model_extension_agentfy_module_agentfy->getStores();

        $data["user_token"] = $this->session->data["user_token"];

        if (isset($this->request->post["module_agentfy_setting"])) {
            $data["module_agentfy_setting"] =
                $this->request->post["module_agentfy_setting"];
        } else {
            $setting = $this->model_setting_setting->getValue("module_agentfy_setting", $this->store_id);

            if (!empty($setting)){
                $data["module_agentfy_setting"] = json_decode($setting, true);
            } else {

                $data["module_agentfy_setting"] = $_config->get(
                    "module_agentfy_setting"
                );

            }
        }

        if (isset($this->request->post["module_agentfy_display"])) {
            $data["module_agentfy_display"] =
                $this->request->post["module_agentfy_display"];
        } else {
            $setting = $this->model_setting_setting->getValue("module_agentfy_display", $this->store_id);
            if (!empty($setting)){
                $data["module_agentfy_display"] = json_decode($setting, true);
            } else {
                $this->load->config('agentfy');
                $data["module_agentfy_display"] = $_config->get(
                    "module_agentfy_display"
                );
            }
        }
        $data["agent"] = "";
        if (!empty($data["module_agentfy_setting"]["agent_id"])) {
            try {
                $result = $this->model_extension_agentfy_module_agentfy_api->getAgent(
                    $data["module_agentfy_setting"]["agent_id"],
                    $this->store_id
                );
                if ($result) {
                    $data["agent"] = $result["name"];
                }
            } catch (\Exception $e) {
                $this->error["warning"] = $e->getMessage();
            }
        }

        foreach ($data["types"] as $key => $value) {
            $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
                $value["code"],
                $this->store_id
            );
            if (!empty($sourceId)) {
                $response = $this->model_extension_agentfy_module_agentfy_api->getSource(
                    $sourceId,
                    $this->store_id
                );
                if (!empty($response)) {
                    $data["types"][$key]["source"] = $response;
                }
            }
        }

        $this->load->model("localisation/language");

        $data["languages"] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post["module_agentfy_status"])) {
            $data["module_agentfy_status"] =
                $this->request->post["module_agentfy_status"];
        } else {
            $setting = $this->model_setting_setting->getValue("module_agentfy_status", $this->store_id);
            $data["module_agentfy_status"] = $setting;
        }

        $data["header"] = $this->load->controller("common/header");
        $data["column_left"] = $this->load->controller("common/column_left");
        $data["footer"] = $this->load->controller("common/footer");

        $this->response->setOutput(
            $this->load->view("extension/agentfy/module/agentfy", $data)
        );
    }

    public function save()
    {
        $this->load->language("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy");

        $this->load->model("setting/setting");

        if (
            $this->request->server["REQUEST_METHOD"] == "POST" &&
            $this->validate()
        ) {
            $setting = $this->model_setting_setting->getSetting(
                "module_agentfy", $this->store_id
            );

            $setting = array_replace_recursive($setting, $this->request->post);

            $this->model_setting_setting->editSetting(
                "module_agentfy",
                $setting,
                $this->store_id
            );

            $this->model_extension_agentfy_module_agentfy->uninstallEvents();
            if (!empty($setting["module_agentfy_status"])) {
                $this->model_extension_agentfy_module_agentfy->installEvents();
            }

            $data["success"] = $this->language->get("success_save");
        }

        $data["error"] = $this->error;

        $this->response->addHeader("Content-Type: application/json");
        $this->response->setOutput(json_encode($data));
    }

    public function addProduct(string &$route, array &$args, mixed &$output)
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/products");
        $productId = $output;
        $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
            "products",
            $this->store_id
        );
        $this->model_extension_agentfy_module_agentfy_products->indexProduct(
            $productId,
            $sourceId,
            $this->store_id
        );
    }

    public function editProduct(&$route, &$args, &$output)
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/products");
        $productId = $args[0];
        $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
            "products",
            $this->store_id
        );
        $this->model_extension_agentfy_module_agentfy_products->indexProduct(
            $productId,
            $sourceId,
            $this->store_id
        );
    }

    public function addCategory(&$route, &$args, &$output)
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/categories");
        $productId = $output;
        $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
            "categories",
            $this->store_id
        );
        $this->model_extension_agentfy_module_agentfy_categories->indexCategory(
            $output,
            $sourceId,
            $this->store_id
        );
    }

    public function editCategory(&$route, &$args, &$output)
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/categories");
        $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
            "categories",
            $this->store_id
        );
        $this->model_extension_agentfy_module_agentfy_categories->indexCategory(
            $args[0],
            $sourceId,
            $this->store_id
        );
    }

    public function addManufacturer(&$route, &$args, &$output)
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/manufacturers");
        $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
            "manufacturers",
            $this->store_id
        );
        $this->model_extension_agentfy_module_agentfy_manufacturers->indexManufacturer(
            $output,
            $sourceId,
            $this->store_id
        );
    }

    public function editManufacturer(&$route, &$args, &$output)
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/manufacturers");
        $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
            "manufacturers",
            $this->store_id
        );
        $this->model_extension_agentfy_module_agentfy_manufacturers->indexManufacturer(
            $args[0],
            $sourceId,
            $this->store_id
        );
    }

    public function createAgent()
    {
        $this->load->language("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/api");

        $name = $this->request->post["name"];
        $prompt = $this->request->post["prompt"];

        $knowledgeId = $this->model_extension_agentfy_module_agentfy->getKnowledgeId($this->store_id);
        if (!empty($knowledgeId)) {
            $agent = $this->model_extension_agentfy_module_agentfy_api->addAgent(
                $name,
                $prompt,
                $knowledgeId,
                $this->store_id
            );
            if (!empty($agent)) {
                $data["agent"] = $agent;
                $data["success"] = $this->language->get("success_save");
            }
        }

        $data["error"] = $this->error;

        $this->response->addHeader("Content-Type: application/json");
        $this->response->setOutput(json_encode($data));
    }

    public function createSource()
    {
        $this->load->language("extension/agentfy/module/agentfy");
        $this->load->model("extension/agentfy/module/agentfy/api");
        $this->load->model("setting/setting");
        $this->load->model("extension/agentfy/module/agentfy");

        $type = $_GET["type"];

        $module_setting = json_decode(
            $this->model_setting_setting->getValue(
                "module_agentfy_sources",
                $this->store_id
            ),
            true
        );

        try {
            if (!empty($module_setting[$type])) {
                $data["source"] = $this->model_extension_agentfy_module_agentfy_api->getSource(
                    $module_setting[$type],
                    $this->store_id
                );
            }
            
            if (empty($data["source"])) {
                $data["source"] = $this->model_extension_agentfy_module_agentfy_api->addSource(
                    $_GET["type"],
                    $this->store_id
                );
            }
        } catch (\Exception $e) {
            $this->error["warning"] = $e->getMessage();
        }
        if (!empty($data["source"])) {

            $knowledgeId = $this->model_extension_agentfy_module_agentfy->getKnowledgeId($this->store_id);
            $types = ["products", "categories", "manufacturers"];
            $sourceIds = [];
            foreach ($types as $type) {
                $sourceId = $this->model_extension_agentfy_module_agentfy->getSourceId(
                    $type,
                    $this->store_id
                );
                if (!empty($sourceId)) {
                    array_push($sourceIds, $sourceId);
                }
            }
            if (empty($knowledgeId)) {
                
                $this->model_extension_agentfy_module_agentfy_api->addKnowledge(
                    $this->store_id == 0 ? $this->config->get('config_name') : $this->store['name'],
                    $sourceIds,
                    $this->store_id
                );
            } else {
                $this->model_extension_agentfy_module_agentfy_api->updateKnowledge(
                    $knowledgeId,
                    $sourceIds,
                    $this->store_id
                );
            }

            $this->document->setTitle($this->language->get("heading_title"));

            $data["breadcrumbs"] = [];

            $data["breadcrumbs"][] = [
                "text" => $this->language->get("text_home"),
                "href" => $this->url->link(
                    "common/dashboard",
                    "user_token=" . $this->session->data["user_token"],
                    true
                ),
            ];

            $data["breadcrumbs"][] = [
                "text" => $this->language->get("text_extension"),
                "href" => $this->url->link(
                    "marketplace/extension",
                    "user_token=" .
                        $this->session->data["user_token"] .
                        "&type=module",
                    true
                ),
            ];

            $data["breadcrumbs"][] = [
                "text" => $this->language->get("heading_title"),
                "href" => $this->url->link(
                    "extension/agentfy/module/agentfy",
                    "user_token=" . $this->session->data["user_token"],
                    true
                ),
            ];

            $data["header"] = $this->load->controller("common/header");
            $data["column_left"] = $this->load->controller(
                "common/column_left"
            );
            $data["footer"] = $this->load->controller("common/footer");

            $data["create_cache"] = str_replace(
                "&amp;",
                "&",
                $this->url->link(
                    "extension/agentfy/module/agentfy".$this->separator."indexing",
                    "user_token=" .
                        $this->session->data["user_token"] .
                        "&store_id=".$this->store_id."&type=" .
                        $_GET["type"],
                    true
                )
            );

            $data["create_complete"] = str_replace(
                "&amp;",
                "&",
                $this->url->link(
                    "extension/agentfy/module/agentfy",
                    "user_token=" . $this->session->data["user_token"]."&store_id=".$this->store_id,
                    true
                )
            );

            $data["cancel"] = $this->url->link(
                "marketplace/extension",
                "user_token=" .
                    $this->session->data["user_token"] .
                    "&type=module"
            );

            $this->response->setOutput(
                $this->load->view("extension/agentfy/module/agentfy/indexing", $data)
            );
        } else {
            $this->index();
        }
    }

    public function indexing()
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->response->addHeader("Content-Type: application/json");
        try {
            $json = $this->model_extension_agentfy_module_agentfy->indexing(
                $_GET["type"],
                $this->store_id
            );
            $this->response->setOutput(json_encode($json));
        } catch (\Exception $e) {
            $this->response->setOutput(
                json_encode(["error" => $e->getMessage()])
            );
        }
    }

    protected function validate()
    {
        if (!$this->user->hasPermission("modify", "extension/agentfy/module/agentfy")) {
            $this->error["warning"] = $this->language->get("error_permission");
        }

        if (
            mb_strlen(
                $this->request->post["module_agentfy_setting"]["api_key"]
            ) < 3 ||
            mb_strlen(
                $this->request->post["module_agentfy_setting"]["api_key"]
            ) > 64
        ) {
            $this->error["api_key"] = $this->language->get("error_api_key");
        }

        return !$this->error;
    }

    public function autocompleteAgents()
    {
        $json = [];

        if (isset($this->request->get["filter_name"])) {
            $this->load->model("extension/agentfy/module/agentfy/api");

            $filter_data = [
                "filter_name" => $this->request->get["filter_name"],
                "sort" => "name",
                "order" => "ASC",
                "start" => 0,
                "limit" => 5,
            ];

            $results = $this->model_extension_agentfy_module_agentfy_api->getAgents(
                $filter_data["filter_name"],
                $this->store_id
            );
            if ($results) {
                foreach ($results as $result) {
                    $json[] = [
                        "agent_id" => $result["id"],
                        "name" => strip_tags(
                            html_entity_decode(
                                $result["name"],
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        ),
                    ];
                }
            }
        }

        $sort_order = [];

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value["name"];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader("Content-Type: application/json");
        $this->response->setOutput(json_encode($json));
    }

    public function uninstall()
    {
        $this->load->model("extension/agentfy/module/agentfy");
        $this->model_extension_agentfy_module_agentfy->removeSources($this->store_id);
        $this->model_extension_agentfy_module_agentfy->uninstallEvents();
    }
}