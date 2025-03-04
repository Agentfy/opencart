<?php
namespace Opencart\Catalog\Model\Extension\Agentfy\Module;

class Agentfy extends \Opencart\System\Engine\Model {
	/**
	 * @param string       $code
	 * @param string       $key
	 * @param string|array $value
	 * @param int          $store_id
	 *
	 * @return void
	 */
	public function editSettingValue(string $code = '', string $key = '', string|array $value = '', int $store_id = 0): void {
		if (!is_array($value)) {
			$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $this->db->escape($value) . "', `serialized` = '0'  WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND `store_id` = '" . (int)$store_id . "'");
		} else {
			$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $this->db->escape(json_encode($value)) . "', `serialized` = '1' WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND `store_id` = '" . (int)$store_id . "'");
		}
	}
}