<?php
class ModelExtensionAgentfyApi extends Model
{
    private $codename = "agentfy";

    private $apiUrl = "https://api.agentfy.ai/v1";
    // private $apiUrl = 'http://localhost:3333';

    private $store_url = '';
	private $catalog_url = '';

	public function __construct($registry) {
		parent::__construct($registry);


		if ($this->request->server['HTTPS']) {
            $this->store_url = HTTPS_SERVER;
            $this->catalog_url = HTTPS_CATALOG;
        } else {
            $this->store_url = HTTP_SERVER;
            $this->catalog_url = HTTP_CATALOG;
        }
	}


    public function addSource($type, $store_id = 0)
    {
        $this->load->model('setting/store');
        $store = $this->model_setting_store->getStore($store_id);

        $response = $this->request("POST", "/sources", [
            "type" => "opencart",
            "name" =>  $type.' ('.($store_id == 0 ? $this->config->get('config_name') : $store['name']) .')',
            "data" => [
                "type" => $type,
            ],
            "image" => "",
            "reindexStatus" => false,
            "intervalDay" => 0,
            "intervalHour" => 0,
            "intervalMinute" => 0,
        ], $store_id);
        if (!empty($response['data'])) {
            $this->load->model("setting/setting");

            $module_setting = $this->model_setting_setting->getSetting(
                "module_agentfy",
                $store_id
            );

            if (!isset($module_setting["module_agentfy_sources"])) {
                $module_setting["module_agentfy_sources"] = [];
            }

            $module_setting["module_agentfy_sources"][$type] =
                $response["data"]["id"];

            $this->model_setting_setting->editSetting(
                "module_agentfy",
                $module_setting,
                $store_id
            );
        }
        return !empty($response["data"]) ? $response["data"] : null;
    }

    public function login($login_data)
    {

        $response = $this->request("POST", "/auth/login", [
            "email" => $login_data["email"],
            "password" => $login_data["password"],
            "deviceId" => "opencart",
        ], 0 , '');

        return isset($response["accessToken"]) ? $response["accessToken"] : null;
    }

    public function addApiKey($team_id)
    {

        $response = $this->request("POST", "/apiKeys", [
            "name" => $this->config->get('config_name') . ' API Key'
        ], 0 , "/teams/:teamId", $team_id);

        return isset($response["data"]) ? $response["data"] : null;
    }

    public function removeSource($sourceId, $store_id)
    {
        $this->request("DELETE", "/sources/" . $sourceId,[],$store_id);
    }

    public function getSource($id, $store_id)
    {
        $response = $this->request("GET", "/sources/" . $id, [],$store_id);

        if (!empty($response)) {
            return !empty($response["data"]) ? $response["data"] : null;
        }
    }

    public function addKnowledge($name, $sourceIds, $store_id = 0)
    {
        $response = $this->request("POST", "/knowledges", [
            "name" => $name,
            "sourceIds" => $sourceIds,
            "entityTypes" => [],
            "relationshipTypes" => [],
        ], $store_id);

        if(!empty($response)) {
            $this->load->model("setting/setting");

            $module_setting = $this->model_setting_setting->getSetting(
                "module_agentfy",
                $store_id
            );


            $module_setting["module_agentfy_knowledge"] =
                $response["data"]["id"];

            $this->model_setting_setting->editSetting(
                "module_agentfy",
                $module_setting,
                $store_id
            );
            return $response['data'];
        }
            
        return null;
    }

    public function updateKnowledge($id, $sourceIds, $store_id)
    {
        $knowledge = $this->request("GET", "/knowledges/".$id,[], $store_id);
        if (!empty($knowledge['data'])) {
            $response = $this->request("PUT", "/knowledges/".$id, [
                "name" => $knowledge['data']['name'],
                "sourceIds" => $sourceIds,
                "entityTypes" => $knowledge['data']['entityTypes'],
                "relationshipTypes" => $knowledge['data']['relationshipTypes'],
            ],$store_id);
    
            if(!empty($response)) {
                return $response['data'];
            }
        }
        
        return null;
    }


    public function addAgent($name, $prompt, $knowledgeId, $store_id)
    {
        $parsedUrl = parse_url($this->catalog_url);
        $domain = $parsedUrl['host']; 
        $response = $this->request("POST", "/agents", [
            "name" => $name,
            "prompt" => $prompt,
            "knowledgeId" => $knowledgeId,
            "public" => false,
            "useRerank" => false,
            "useSearchPrompt" => false,
            "searchCount" => 0,
            "whitelist" => $domain
        ], $store_id);

        return !empty($response) ? $response['data'] : null;
    }

    public function updateAgentPrompt($id, $team_id, $prompt, $store_id)
    {
        $agent = $this->getAgent($id, $store_id);
        if (empty($agent)) {
            return null;
        }
        $parsedUrl = parse_url($this->catalog_url);
        $domain = $parsedUrl['host']; 
        $response = $this->request("PUT", "/agents/" . $id, [
            "name" => $agent['name'],
            "prompt" => $prompt,
            "knowledgeId" => $agent['knowledgeId'],
            "public" => $agent['public'],
            "useRerank" => $agent['useRerank'],
            "useSearchPrompt" => $agent['useSearchPrompt'],
            "searchCount" => $agent['searchCount'],
            "whitelist" => $agent['whitelist'],
        ], $store_id);

        return !empty($response) ? $response['data'] : null;
    }


    public function addTeam($name, $codename, $store_id)
    {
        $parsedUrl = parse_url($this->catalog_url);
        $domain = $parsedUrl['host']; 
        $response = $this->request("POST", "/teams", [
            "name" => $name,
            "codename" => $codename,
        ], $store_id, "");

        return !empty($response) ? $response['data'] : null;
    }

    public function addDefaultTeam()
    {
        $words = explode(' ', $this->config->get('config_name'));
        $codename = '';

        foreach ($words as $word) {
            $word = preg_replace('/[^a-zA-Z]/', '', $word);
            $codename .= strtolower($word[0]);
        }

        if(strlen($codename) < 3) {
            $codename .= '-team';
        }

        $response = $this->request("POST", "/teams", [
            "name" => $this->config->get('config_name'),
            "codename" => $codename,
        ], 0, "");

        return isset($response['data']) ? $response['data'] : null;
    }

    public function addDocument($sourceId, $externalId, $name, $pageContent, $metadata = [], $store_id = 0)
    {
        return $this->request("POST", "/sources/" . $sourceId . "/documents", [
            "externalId" => $externalId,
            "title" => $name,
            "pageContent" => $pageContent,
            "metadata" => array_merge(["externalId" => $externalId], $metadata),
        ], $store_id);
    }

    public function updateDocument(
        $sourceId,
        $documentId,
        $summary,
        $externalId,
        $name,
        $pageContent,
        $metadata = [],
        $store_id = 0
    ) {
        $response = $this->request(
            "PUT",
            "/sources/" . $sourceId . "/documents/" . $documentId,
            [
                "externalId" => $externalId,
                "title" => $name,
                "pageContent" => $pageContent,
                "summary" => $summary,
                "metadata" => array_merge(["externalId" => $externalId], $metadata),
            ], 
            $store_id
            
        );

        if (!empty($response)) {
            return !empty($response["data"]) ? $response["data"] : null;
        }
    }

    public function deleteAllDocuments($sourceId, $store_id)
    {
        $response = $this->request("DELETE", "/sources/" . $sourceId . "/documents", [], $store_id);
        if (!empty($response)) {
            return $response;
        }
    }
    public function deleteAllIndexes($sourceId, $store_id)
    {
        $response = $this->request("DELETE", "/sources/" . $sourceId . "/indexes", [], $store_id);
        if (!empty($response)) {
            return $response;
        }
    }
    public function indexDocument($sourceId, $documentId, $store_id)
    {
        return $this->request(
            "POST",
            "/sources/" . $sourceId . "/documents/" . $documentId . "/index", $store_id
        );
    }

    public function indexSource($sourceId, $store_id)
    {
        return $this->request("POST", "/sources/" . $sourceId . "/index", [], $store_id);
    }

    public function getDocument($sourceId, $externalId, $store_id)
    {
        $response = $this->request(
            "GET",
            "/sources/" . $sourceId . "/documents?externalId=" . $externalId,
            [],
            $store_id
        );

        if (!empty($response)) {
            if (count($response["data"]) > 0) {
                return $response["data"][0];
            }
        }
    }

    public function getTeams($search = "", $store_id = 0)
    {
        $response = $this->request("GET", "/teams?search=" . $search, [], $store_id, "");

        if (!empty($response)) {
            if (count($response["data"]) > 0) {
                return $response["data"];
            }
        }
    }


    public function getTeam($id, $store_id)
    {
        $response = $this->request("GET", "/teams/" . $id, [], $store_id, "");

        if (!empty($response["data"])) {
            return $response["data"];
        }
    }

    public function getAgents($search = "", $store_id = 0)
    {
        $response = $this->request("GET", "/agents?search=" . $search, [], $store_id);

        if (!empty($response)) {
            if (count($response["data"]) > 0) {
                return $response["data"];
            }
        }
    }

    public function getAgent($id, $store_id)
    {
        $response = $this->request("GET", "/agents/" . $id, [], $store_id);

        if (!empty($response["data"])) {
            return $response["data"];
        }
    }

    public function getKnowledge($id, $store_id)
    {
        $response = $this->request("GET", "/knowledges/" . $id, [], $store_id);

        if (!empty($response["data"])) {
            return $response["data"];
        }
    }

    public function request($method, $url, $body = [], $store_id = 0, $prefix="/teams/:teamId", $team_id = null)
    {
        $this->load->model("setting/setting");
        $setting = $this->model_setting_setting->getSettingValue("module_agentfy_setting", $store_id);
        
        if(!isset($this->session->data['agentfy_bearer_token'])) {
            if (empty ($setting) && $url != "/auth/login") {
                throw new Exception("Invalid API key");
                return;
            }
        }
        
        $module_setting = json_decode($setting, true);
        $curl = curl_init();

        $apiUrl = $this->apiUrl;


        if (!empty($module_setting["api_url"])) {
            $apiUrl = $module_setting["api_url"];
        }

        if (!empty($prefix)) {
            $url = str_replace(":teamId", $team_id ? $team_id : $module_setting["team_id"], $prefix) . $url;
        }

        $headers = [
            "Content-Type: application/json",
            "api-key: " . $module_setting["api_key"],
        ];

        if (isset($this->session->data['agentfy_bearer_token'])) {
            $headers[] = "Authorization: Bearer " . $this->session->data['agentfy_bearer_token'];
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl . $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if (!empty($body)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $errors = curl_error($curl);
        $responseContent = array();

        if (!empty($response)) {
            $responseContent = json_decode($response, true);
        }
        if ($status == 403) {
            throw new Exception("Invalid API key");
            return;
        }
        if ($status == 401) {
            throw new Exception("Invalid API key");
            return;
        }
        if (!empty($responseContent['error'])) {
            $this->sendSentryError(new Exception($responseContent['error']), 'error', [
                'url' => $apiUrl . $url,
                'method' => $method,
                'headers' => [
                    "Content-Type" => "application/json",
                    "api-key" => $module_setting["api_key"],
                ],
                'data' => $body,
            ]);
            throw new Exception($responseContent['error']);

        }
        if (!empty($errors)) {
            $this->sendSentryError(new Exception($errors), 'error', [
                'url' => $apiUrl . $url,
                'method' => $method,
                'headers' => [
                    "Content-Type" => "application/json",
                    "api-key" => $module_setting["api_key"],
                ],
                'data' => $body,
            ]);
            throw new Exception($errors);
        }

        if (!empty($response)) {
            curl_close($curl);
            return !empty($responseContent) ? $responseContent : null;
        }
        return;
    }

    public function sendSentryError($exception, $level = 'error', $request = null){
        $publicKey = '74fe15f733831993b4a2c35f8e0d7a47';
        $projectId = '4509249648787536';
        $sentryHost = 'https://o4509133849034752.ingest.de.sentry.io';
    
        $eventId = bin2hex(random_bytes(16));
        $timestamp = time();
        $url = "$sentryHost/api/$projectId/envelope/";
    
        $authHeader = "Sentry sentry_version=7, sentry_client=custom-php/1.0, sentry_key=$publicKey";
    
        $headers = [
            'Content-Type: application/x-sentry-envelope',
            "X-Sentry-Auth: $authHeader"
        ];
    
        $stacktrace = [];
        foreach ($exception->getTrace() as $frame) {
            $stacktrace[] = [
                'filename' => $frame['file'] ?? '[internal]',
                'function' => $frame['function'] ?? '',
                'lineno' => $frame['line'] ?? 0,
                'module' => $frame['class'] ?? '',
            ];
        }
    
        $exceptionBlock = [
            'values' => [[
                'type' => get_class($exception),
                'value' => $exception->getMessage(),
                'stacktrace' => ['frames' => array_reverse($stacktrace)],
            ]]
        ];
    
        $eventPayload = json_encode([
            'event_id' => $eventId,
            'timestamp' => gmdate('c'),
            'level' => $level,
            'platform' => 'php',
            'logger' => 'custom-opencart',
            'exception' => $exceptionBlock,
            'message' => ['formatted' => $exception->getMessage()],
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'cli',
            'environment' => 'production',
            'request' => $request,
        ]);
    
        $envelopeHeader = json_encode([
            'event_id' => $eventId,
            'sent_at' => gmdate('c'),
        ]);
    
        $itemHeader = json_encode([
            'type' => 'event'
        ]);
    
        $body = implode("\n", [$envelopeHeader, $itemHeader, $eventPayload]);
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            error_log('Sentry error: ' . curl_error($ch));
        }
    
        curl_close($ch);
    }
}
