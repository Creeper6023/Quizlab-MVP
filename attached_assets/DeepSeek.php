<?php 

define('API_KEY', 'sk-cfc38ec5135d4862944600351618a5b2');
define('API_URL', 'https://api.deepseek.com/v1/chat/completions');

class DeepSeek {
	private $_curl_options;
	private $_data;
	
	public function __construct($api_key, $api_url) {
		$this->_data = [
			'model' => 'deepseek-chat',
			'messages' => [],
			'temperature' => 0.7,
			'max_tokens' => 1000
		];
		
		$this->_curl_options = [
			CURLOPT_URL => $api_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => 'UTF-8',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($this->_data),
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $api_key,
				'Content-Type: application/json'
			]
		];
	}
	
	private function _send_request() {
		try {
			$curl = curl_init();
			curl_setopt_array($curl, $this->_curl_options);
			$response = curl_exec($curl);
		} catch (Exception $e) {
			return $e;
		} finally {
			curl_close($curl);
		}
		return json_decode($response);
	}
	
	private function _add_message($role, $content) {
		array_push($this->_data['messages'], [
			'role' => $role,
			'content' => $content
		]);
		$this->_curl_options[CURLOPT_POSTFIELDS] = json_encode($this->_data);
	}
	
	private function _add_response($response) {
		$choices = $response->choices;
		$message = $choices[0]->message;
		$this->_add_message($message->role, $message->content);
		return $message->content;
	}
	
	public function new_chat($system_prompt) {
		$this->_data['messages'] = [];
		$this->_add_message('system', $system_prompt);
	}
	
	public function send_message($content) {
		$this->_add_message('user', $content);
		$response = $this->_send_request();
		$content = $this->_add_response($response);
		return $content;
	}
	
	public function chat_history() {
		return $this->_data['messages'];
	}
}

function create_ai() {
	return new DeepSeek(API_KEY, API_URL);
}

?>