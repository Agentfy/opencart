<?php
namespace Opencart\Catalog\Controller\Extension\Agentfy\Module;

class Agentfy extends \Opencart\System\Engine\Controller
{
  private $error = [];

  public function index()
  {
    $this->load->model("extension/agentfy/module/agentfy");

    if ($this->config->get("module_agentfy_status")) {
      $this->load->language("extension/agentfy/module/agentfy");

      $_config = new \Opencart\System\Engine\Config();
      $_config->addPath(DIR_EXTENSION . 'agentfy/system/config/');
      $_config->load("agentfy");

      $config_setting = $_config->get("agentfy_setting");

      $setting = array_replace_recursive(
        (array) $config_setting,
        (array) $this->config->get("module_agentfy_setting")
      );


      $config_display = $_config->get("agentfy_display");

      $settingDisplay = array_replace_recursive(
        (array) $config_display,
        (array) $this->config->get("module_agentfy_display")
      );

      $data["code"] = html_entity_decode(
        $setting["api_key"],
        ENT_QUOTES,
        "UTF-8"
      );
      $data["agentId"] = html_entity_decode(
        $setting["agent_id"],
        ENT_QUOTES,
        "UTF-8"
      );
    }

    $data['options']= $settingDisplay;

    $data["error"] = $this->error;

    $this->response->addHeader("Content-Type: application/json");
    $this->response->setOutput(json_encode($data));
  }

  public function content_top_before(string &$route, array &$args)
  {
    $this->load->model("extension/agentfy/module/agentfy");
    if ($this->config->get("module_agentfy_status")) {
      $_config = new \Opencart\System\Engine\Config();
      $_config->addPath(DIR_EXTENSION . 'agentfy/system/config/');
      $_config->load("agentfy");

      $config_setting = $_config->get("paypal_setting");

      $setting = array_replace_recursive(
        (array) $config_setting,
        (array) $this->config->get("module_agentfy_setting")
      );

      $this->document->addScript("https://sdk.agentfy.ai/client-latest.umd.js");
      $this->document->addScript("extension/agentfy/catalog/view/javascript/agentfy.js");
    }
  }
}
