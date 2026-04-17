<?php
/**
 * Webhook Test Endpoint
 * 
 * Simulates incoming WhatsApp messages for testing
 * POST /api/webhook-test.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST method required'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$from = $input['from'] ?? '+971501234567';
$message = $input['message'] ?? 'Hello';
$name = $input['name'] ?? 'Test User';
$type = $input['type'] ?? 'text';

// Build a realistic Meta webhook payload
$timestamp = time();
$msg_id = 'wamid.test_' . bin2hex(random_bytes(8));

$webhook_payload = [
    'object' => 'whatsapp_business_account',
    'entry' => [
        [
            'id' => 'BUSINESS_ACCOUNT_ID',
            'changes' => [
                [
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '971500000000',
                            'phone_number_id' => 'PHONE_NUMBER_ID'
                        ],
                        'contacts' => [
                            [
                                'profile' => ['name' => $name],
                                'wa_id' => ltrim($from, '+')
                            ]
                        ],
                        'messages' => [
                            buildTestMessage($type, $from, $message, $msg_id, $timestamp)
                        ]
                    ],
                    'field' => 'messages'
                ]
            ]
        ]
    ]
];

// Send to our own webhook
$webhook_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php';

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhook_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

jsonResponse([
    'success' => true,
    'message' => 'Test webhook sent',
    'simulated' => [
        'from' => $from,
        'name' => $name,
        'message' => $message,
        'type' => $type,
        'message_id' => $msg_id
    ],
    'webhook_response' => [
        'http_code' => $http_code,
        'body' => json_decode($response, true)
    ]
]);

function buildTestMessage($type, $from, $content, $msg_id, $timestamp) {
    $base = [
        'from' => ltrim($from, '+'),
        'id' => $msg_id,
        'timestamp' => (string)$timestamp
    ];

    switch ($type) {
        case 'image':
            return array_merge($base, [
                'type' => 'image',
                'image' => ['id' => 'media_' . bin2hex(random_bytes(8)), 'mime_type' => 'image/jpeg', 'caption' => $content]
            ]);
        case 'document':
            return array_merge($base, [
                'type' => 'document',
                'document' => ['id' => 'media_' . bin2hex(random_bytes(8)), 'mime_type' => 'application/pdf', 'filename' => 'document.pdf', 'caption' => $content]
            ]);
        case 'button_reply':
            return array_merge($base, [
                'type' => 'interactive',
                'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => 'btn_0', 'title' => $content]]
            ]);
        case 'location':
            return array_merge($base, [
                'type' => 'location',
                'location' => ['latitude' => 25.2048, 'longitude' => 55.2708, 'name' => 'Dubai', 'address' => $content]
            ]);
        default: // text
            return array_merge($base, [
                'type' => 'text',
                'text' => ['body' => $content]
            ]);
    }
}
?>
