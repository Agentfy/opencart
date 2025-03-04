<?php
namespace Opencart\Catalog\Controller\Extension\Agentfy\Module;

class Agentfy extends \Opencart\System\Engine\Controller
{
  private $error = [];
  public function __construct($registry) {
    parent::__construct($registry);
    if(!isset($this->user)){
        $this->user = new \Opencart\System\Library\Cart\User($registry);
    }
}
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

      $settingDisplay['apiUrl'] = html_entity_decode(
        $setting["api_url"],
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

      $config_setting = $_config->get("module_agentfy_setting");

      $setting = array_replace_recursive(
        (array) $config_setting,
        (array) $this->config->get("module_agentfy_setting")
      );
      if ($setting['admin_only_access'] && !$this->user->isLogged()) {
        return;
      }
      
      $timestamp = date("Ymd");

      $nowDate = new \DateTime();

      if (empty($setting['last_update_client']) || (!empty($setting['last_update_client']) && ($nowDate->getTimestamp() - $setting['last_update_client']) > 86400)) {
        file_put_contents(DIR_EXTENSION.'agentfy/catalog/view/javascript/agentfy-client-latest.umd.js', file_get_contents("https://sdk.agentfy.ai/client-latest.umd.js"));
        $setting['last_update_client'] = $nowDate->getTimestamp();
        $this->model_extension_agentfy_module_agentfy->editSettingValue('module_agentfy', 'module_agentfy_setting', $setting);
      }
      
      $this->document->addScript("extension/agentfy/catalog/view/javascript/agentfy-client-latest.umd.js?t=" . $timestamp);
      $this->document->addScript("extension/agentfy/catalog/view/javascript/agentfy.js?t=" . $timestamp);

    }
  }
}
