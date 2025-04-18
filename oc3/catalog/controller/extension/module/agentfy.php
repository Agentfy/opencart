<?php
class ControllerExtensionModuleAgentFy extends Controller
{
  private $error = [];

  public function getData()
  {
    $this->load->model("extension/module/agentfy");

    if ($this->config->get("module_agentfy_status")) {
      $this->load->language("extension/module/agentfy");

      $_config = new Config();
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


    $timestamp = round(microtime(true) * 1000);
    $nowDate = new DateTime();

    if (empty($setting['last_update_client']) || (!empty($setting['last_update_client']) && ($nowDate->getTimestamp() - $setting['last_update_client']) > 86400) || !file_exists(DIR_APPLICATION.'view/javascript/agentfy-client-latest.umd.js')) {
      file_put_contents(DIR_APPLICATION.'view/javascript/agentfy-client-latest.umd.js', file_get_contents("https://sdk.agentfy.ai/client-latest.umd.js"));
      $setting['last_update_client'] = $nowDate->getTimestamp();
      $this->model_extension_module_agentfy->editSettingValue('module_agentfy', 'module_agentfy_setting', $setting);
    }

    $data['agentfy_client'] = "catalog/view/javascript/agentfy-client-latest.umd.js?t=" . $timestamp;

    $data["error"] = $this->error;

    $this->response->addHeader("Content-Type: application/json");
    $this->response->setOutput(json_encode($data));
  }

  public function content_top_before($route, &$data)
  {
    $this->load->model("extension/module/agentfy");

    if ($this->config->get("module_agentfy_status")) {
      $_config = new Config();
      $_config->load("agentfy");

      $config_setting = $_config->get("module_agentfy_setting");

      $setting = array_replace_recursive(
        (array) $config_setting,
        (array) $this->config->get("module_agentfy_setting")
      );

      if ($setting['admin_only_access'] && !isset($this->session->data['user_id'])) {
        return;
      }

      $this->document->addScript("catalog/view/javascript/agentfy.js?t=" . $timestamp);
    }
  }
}
