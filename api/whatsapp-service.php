<?php
/**
 * WhatsApp API Service
 * 
 * Handles sending messages via Meta WhatsApp Cloud API
 * Supports both Mock and Live modes
 */

require_once __DIR__ . '/../config/database.php';

class WhatsAppService {

    private $api_url;
    private $phone_number_id;
    private $access_token;
    private $mode;

    public function __construct() {
        $this->mode = WA_API_MODE;
        $this->api_url = WA_API_URL;
        $this->phone_number_id = WA_PHONE_NUMBER_ID;
        $this->access_token = WA_ACCESS_TOKEN;
    }

    /**
     * Send a text message
     */
    public function sendTextMessage($to, $text) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $text]
        ];
        return $this->sendRequest($payload);
    }

    /**
     * Send a template message
     */
    public function sendTemplateMessage($to, $template_name, $language = 'en', $components = []) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => ['code' => $language],
                'components' => $components
            ]
        ];
        return $this->sendRequest($payload);
    }

    /**
     * Send an image message
     */
    public function sendImageMessage($to, $image_url, $caption = '') {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'image',
            'image' => ['link' => $image_url, 'caption' => $caption]
        ];
        return $this->sendRequest($payload);
    }

    /**
     * Send a document message
     */
    public function sendDocumentMessage($to, $doc_url, $filename, $caption = '') {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'document',
            'document' => ['link' => $doc_url, 'filename' => $filename, 'caption' => $caption]
        ];
        return $this->sendRequest($payload);
    }

    /**
     * Send interactive button message
     */
    public function sendButtonMessage($to, $body_text, $buttons) {
        $button_items = [];
        foreach ($buttons as $i => $btn) {
            $button_items[] = [
                'type' => 'reply',
                'reply' => ['id' => 'btn_' . $i, 'title' => substr($btn, 0, 20)]
            ];
        }
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body_text],
                'action' => ['buttons' => array_slice($button_items, 0, 3)] // Max 3 buttons
            ]
        ];
        return $this->sendRequest($payload);
    }

    /**
     * Send interactive list message
     */
    public function sendListMessage($to, $body_text, $button_text, $sections) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $body_text],
                'action' => [
                    'button' => $button_text,
                    'sections' => $sections
                ]
            ]
        ];
        return $this->sendRequest($payload);
    }

    /**
     * Download media by ID
     */
    public function getMediaUrl($media_id) {
        if ($this->mode === 'mock') {
            return ['success' => true, 'url' => 'https://example.com/mock-media/' . $media_id];
        }

        $url = $this->api_url . $media_id;
        $response = $this->curlRequest($url, null, 'GET');
        if ($response && isset($response['url'])) {
            return ['success' => true, 'url' => $response['url']];
        }
        return ['success' => false, 'error' => 'Could not retrieve media URL'];
    }

    /**
     * Mark message as read
     */
    public function markAsRead($message_id) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $message_id
        ];
        return $this->sendRequest($payload, 'messages');
    }

    /**
     * Core send request method
     */
    private function sendRequest($payload, $endpoint = 'messages') {
        if ($this->mode === 'mock') {
            return $this->mockSend($payload);
        }

        $url = $this->api_url . $this->phone_number_id . '/' . $endpoint;
        $response = $this->curlRequest($url, $payload);

        // Store outgoing message in DB
        $this->storeOutboundMessage($payload, $response);

        return $response;
    }

    /**
     * Mock send - simulate API response
     */
    private function mockSend($payload) {
        $mock_id = 'wamid.' . bin2hex(random_bytes(16));
        $response = [
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => $payload['to'] ?? '', 'wa_id' => $payload['to'] ?? '']],
            'messages' => [['id' => $mock_id]],
            'mock' => true
        ];

        // Store outgoing message
        $this->storeOutboundMessage($payload, $response);

        // Log
        $conn = getDbConnection();
        $payload_json = json_encode($payload);
        $response_json = json_encode($response);
        $stmt = $conn->prepare("INSERT INTO webhook_logs (event_type, payload, response, status, ip_address, created_at) VALUES ('mock_send', ?, ?, 'success', 'localhost', NOW())");
        $stmt->bind_param('ss', $payload_json, $response_json);
        $stmt->execute();
        $conn->close();

        return $response;
    }

    /**
     * Store outbound message in database
     */
    private function storeOutboundMessage($payload, $response) {
        $conn = getDbConnection();
        $to = $payload['to'] ?? '';
        $type = $payload['type'] ?? 'text';
        $wa_msg_id = $response['messages'][0]['id'] ?? null;

        // Extract content
        $content = '';
        switch ($type) {
            case 'text': $content = $payload['text']['body'] ?? ''; break;
            case 'template': $content = '[Template: ' . ($payload['template']['name'] ?? '') . ']'; break;
            case 'image': $content = $payload['image']['caption'] ?? '[Image]'; break;
            case 'document': $content = '[Document: ' . ($payload['document']['filename'] ?? '') . ']'; break;
            case 'interactive':
                $itype = $payload['interactive']['type'] ?? '';
                $content = $payload['interactive']['body']['text'] ?? "[Interactive: $itype]";
                break;
        }

        // Find contact
        $phone_plus = str_starts_with($to, '+') ? $to : '+' . $to;
        $phone_no_plus = ltrim($to, '+');
        $stmt = $conn->prepare("SELECT id FROM contacts WHERE phone = ? OR phone = ?");
        $stmt->bind_param('ss', $phone_plus, $phone_no_plus);
        $stmt->execute();
        $contact = $stmt->get_result()->fetch_assoc();

        if ($contact) {
            $contact_id = $contact['id'];

            // Find or create conversation
            $stmt = $conn->prepare("SELECT id FROM conversations WHERE contact_id = ? AND status != 'closed'");
            $stmt->bind_param('i', $contact_id);
            $stmt->execute();
            $convo = $stmt->get_result()->fetch_assoc();

            if ($convo) {
                $convo_id = $convo['id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO conversations (contact_id, status, last_message, last_message_at) VALUES (?, 'open', ?, NOW())");
                $stmt->bind_param('is', $contact_id, $content);
                $stmt->execute();
                $convo_id = $conn->insert_id;
            }

            // Store message
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, contact_id, direction, message_type, content, status, wa_message_id) VALUES (?, ?, 'outbound', ?, ?, 'sent', ?)");
            $stmt->bind_param('iisss', $convo_id, $contact_id, $type, $content, $wa_msg_id);
            $stmt->execute();

            // Update conversation
            $conn->query("UPDATE conversations SET last_message = '" . $conn->real_escape_string(substr($content, 0, 255)) . "', last_message_at = NOW() WHERE id = $convo_id");
        }

        $conn->close();
    }

    /**
     * cURL request to Meta API
     */
    private function curlRequest($url, $payload = null, $method = 'POST') {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST' && $payload) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);
        if ($http_code >= 400) {
            $error = $decoded['error']['message'] ?? 'API Error';
            return ['success' => false, 'error' => $error, 'http_code' => $http_code];
        }

        return $decoded;
    }

    /**
     * Normalize phone number
     */
    private function normalizePhone($phone) {
        return ltrim(preg_replace('/[^0-9]/', '', $phone), '0');
    }
}
?>
