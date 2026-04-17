<?php
/**
 * WhatsApp Webhook Endpoint
 * 
 * GET  - Webhook verification (Meta sends challenge)
 * POST - Receive incoming messages, status updates, etc.
 * 
 * URL: https://yourdomain.com/wa-platform/api/webhook.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/whatsapp-service.php';
require_once __DIR__ . '/chatbot-engine.php';

// Webhook Verify Token (set this in Meta Developer Console)
define('WEBHOOK_VERIFY_TOKEN', 'billionaire_homes_wa_verify_2026');

// Log all webhook requests
function logWebhook($type, $payload, $response = '', $status = 'success') {
    $conn = getDbConnection();
    $payload_json = is_string($payload) ? $payload : json_encode($payload);
    $stmt = $conn->prepare("INSERT INTO webhook_logs (event_type, payload, response, status, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param('sssss', $type, $payload_json, $response, $status, $ip);
    $stmt->execute();
    $conn->close();
}

// ============================================
// GET Request - Webhook Verification
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === WEBHOOK_VERIFY_TOKEN) {
        logWebhook('verification', $_GET, 'Verified successfully');
        http_response_code(200);
        echo $challenge;
        exit;
    }

    logWebhook('verification_failed', $_GET, 'Token mismatch', 'failed');
    http_response_code(403);
    echo json_encode(['error' => 'Verification failed']);
    exit;
}

// ============================================
// POST Request - Incoming Webhook Events
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        logWebhook('invalid_payload', $rawInput, 'Could not parse JSON', 'failed');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }

    // Always respond 200 quickly to Meta (they retry on non-200)
    http_response_code(200);
    echo json_encode(['status' => 'received']);

    // Flush output so Meta gets response immediately
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ob_flush();
        flush();
    }

    // Process the webhook payload
    try {
        processWebhookPayload($data);
    } catch (Exception $e) {
        logWebhook('processing_error', $data, $e->getMessage(), 'failed');
    }
    exit;
}

// Unsupported method
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;

// ============================================
// Process Webhook Payload
// ============================================
function processWebhookPayload($data) {
    // Meta sends data in this structure:
    // { "object": "whatsapp_business_account", "entry": [ { "changes": [ { "value": {...}, "field": "messages" } ] } ] }

    if (!isset($data['entry'])) {
        logWebhook('unknown_format', $data, 'No entry field');
        return;
    }

    foreach ($data['entry'] as $entry) {
        if (!isset($entry['changes'])) continue;

        foreach ($entry['changes'] as $change) {
            $value = $change['value'] ?? [];
            $field = $change['field'] ?? '';

            if ($field !== 'messages') continue;

            // Process status updates
            if (isset($value['statuses'])) {
                foreach ($value['statuses'] as $status) {
                    processStatusUpdate($status);
                }
            }

            // Process incoming messages
            if (isset($value['messages'])) {
                $contacts_info = $value['contacts'] ?? [];
                foreach ($value['messages'] as $message) {
                    // Find contact name from contacts array
                    $contact_name = 'Unknown';
                    foreach ($contacts_info as $ci) {
                        if (($ci['wa_id'] ?? '') === ($message['from'] ?? '')) {
                            $contact_name = $ci['profile']['name'] ?? 'Unknown';
                        }
                    }
                    processIncomingMessage($message, $contact_name);
                }
            }
        }
    }
}

// ============================================
// Process Incoming Message
// ============================================
function processIncomingMessage($message, $contact_name) {
    $conn = getDbConnection();

    $from = $message['from'] ?? '';
    $msg_id = $message['id'] ?? '';
    $timestamp = $message['timestamp'] ?? time();
    $msg_type = $message['type'] ?? 'text';

    // Extract message content based on type
    $content = '';
    $media_url = null;

    switch ($msg_type) {
        case 'text':
            $content = $message['text']['body'] ?? '';
            break;

        case 'image':
            $content = $message['image']['caption'] ?? '[Image]';
            $media_url = $message['image']['id'] ?? null; // Media ID, need to download via API
            $msg_type = 'image';
            break;

        case 'video':
            $content = $message['video']['caption'] ?? '[Video]';
            $media_url = $message['video']['id'] ?? null;
            $msg_type = 'video';
            break;

        case 'document':
            $content = $message['document']['caption'] ?? '[Document: ' . ($message['document']['filename'] ?? 'file') . ']';
            $media_url = $message['document']['id'] ?? null;
            $msg_type = 'document';
            break;

        case 'audio':
            $content = '[Voice message]';
            $media_url = $message['audio']['id'] ?? null;
            break;

        case 'location':
            $lat = $message['location']['latitude'] ?? 0;
            $lng = $message['location']['longitude'] ?? 0;
            $content = "[Location: $lat, $lng]";
            break;

        case 'contacts':
            $shared_contacts = $message['contacts'] ?? [];
            $names = array_map(fn($c) => $c['name']['formatted_name'] ?? 'Unknown', $shared_contacts);
            $content = '[Shared contacts: ' . implode(', ', $names) . ']';
            break;

        case 'interactive':
            // Button replies or list replies
            $interactive = $message['interactive'] ?? [];
            $reply_type = $interactive['type'] ?? '';
            if ($reply_type === 'button_reply') {
                $content = $interactive['button_reply']['title'] ?? '[Button Reply]';
            } elseif ($reply_type === 'list_reply') {
                $content = $interactive['list_reply']['title'] ?? '[List Reply]';
            } else {
                $content = '[Interactive: ' . $reply_type . ']';
            }
            break;

        case 'button':
            $content = $message['button']['text'] ?? '[Button]';
            break;

        case 'reaction':
            $emoji = $message['reaction']['emoji'] ?? '';
            $content = "[Reaction: $emoji]";
            break;

        case 'sticker':
            $content = '[Sticker]';
            $media_url = $message['sticker']['id'] ?? null;
            break;

        default:
            $content = "[Unsupported message type: $msg_type]";
    }

    // Normalize phone number (remove + prefix for matching)
    $phone_clean = $from;
    if (!str_starts_with($phone_clean, '+')) {
        $phone_clean = '+' . $phone_clean;
    }

    // 1. Find or create contact
    $stmt = $conn->prepare("SELECT id FROM contacts WHERE phone = ? OR phone = ?");
    $phone_no_plus = ltrim($phone_clean, '+');
    $stmt->bind_param('ss', $phone_clean, $phone_no_plus);
    $stmt->execute();
    $contact_result = $stmt->get_result()->fetch_assoc();

    if ($contact_result) {
        $contact_id = $contact_result['id'];
        // Update last message time
        $conn->query("UPDATE contacts SET last_message_at = NOW() WHERE id = $contact_id");
    } else {
        // Auto-create contact
        $stmt = $conn->prepare("INSERT INTO contacts (phone, name, status) VALUES (?, ?, 'active')");
        $stmt->bind_param('ss', $phone_clean, $contact_name);
        $stmt->execute();
        $contact_id = $conn->insert_id;

        logWebhook('new_contact', ['phone' => $phone_clean, 'name' => $contact_name], "Created contact #$contact_id");
    }

    // 2. Find or create conversation
    $stmt = $conn->prepare("SELECT id FROM conversations WHERE contact_id = ? AND status != 'closed'");
    $stmt->bind_param('i', $contact_id);
    $stmt->execute();
    $convo_result = $stmt->get_result()->fetch_assoc();

    if ($convo_result) {
        $convo_id = $convo_result['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO conversations (contact_id, status, last_message, last_message_at, unread_count) VALUES (?, 'open', ?, NOW(), 1)");
        $stmt->bind_param('is', $contact_id, $content);
        $stmt->execute();
        $convo_id = $conn->insert_id;
    }

    // 3. Store message
    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, contact_id, direction, message_type, content, media_url, status, wa_message_id, created_at) VALUES (?, ?, 'inbound', ?, ?, ?, 'read', ?, FROM_UNIXTIME(?))");
    $stmt->bind_param('iissssi', $convo_id, $contact_id, $msg_type, $content, $media_url, $msg_id, $timestamp);
    $stmt->execute();

    // 4. Update conversation
    $conn->query("UPDATE conversations SET last_message = '" . $conn->real_escape_string(substr($content, 0, 255)) . "', last_message_at = NOW(), unread_count = unread_count + 1, status = 'open' WHERE id = $convo_id");

    // 5. Log analytics event
    $conn->query("INSERT INTO analytics_events (event_type, contact_id, data) VALUES ('message_received', $contact_id, '{\"type\":\"$msg_type\"}')");

    logWebhook('message_received', [
        'from' => $from,
        'type' => $msg_type,
        'content' => substr($content, 0, 100),
        'contact_id' => $contact_id,
        'conversation_id' => $convo_id
    ], "Stored message in conversation #$convo_id");

    $conn->close();

    // 6. Trigger chatbot auto-reply
    triggerChatbotFlow($contact_id, $convo_id, $content, $msg_type);
}

// ============================================
// Process Status Update
// ============================================
function processStatusUpdate($status) {
    $conn = getDbConnection();

    $msg_id = $status['id'] ?? '';
    $wa_status = $status['status'] ?? '';
    $timestamp = $status['timestamp'] ?? time();
    $recipient = $status['recipient_id'] ?? '';

    // Map WhatsApp status to our status
    $status_map = [
        'sent' => 'sent',
        'delivered' => 'delivered',
        'read' => 'read',
        'failed' => 'failed'
    ];
    $our_status = $status_map[$wa_status] ?? $wa_status;

    // Update message status
    $stmt = $conn->prepare("UPDATE messages SET status = ? WHERE wa_message_id = ?");
    $stmt->bind_param('ss', $our_status, $msg_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;

    // Update broadcast recipient if applicable
    if ($our_status === 'delivered') {
        $stmt = $conn->prepare("UPDATE broadcast_recipients SET status = 'delivered', delivered_at = FROM_UNIXTIME(?) WHERE contact_id = (SELECT id FROM contacts WHERE phone LIKE CONCAT('%', ?) LIMIT 1) AND status IN ('pending','sent')");
        $stmt->bind_param('is', $timestamp, $recipient);
        $stmt->execute();
    } elseif ($our_status === 'read') {
        $stmt = $conn->prepare("UPDATE broadcast_recipients SET status = 'read', read_at = FROM_UNIXTIME(?) WHERE contact_id = (SELECT id FROM contacts WHERE phone LIKE CONCAT('%', ?) LIMIT 1) AND status IN ('pending','sent','delivered')");
        $stmt->bind_param('is', $timestamp, $recipient);
        $stmt->execute();
    }

    // Handle errors
    if ($our_status === 'failed' && isset($status['errors'])) {
        $error_msg = $status['errors'][0]['title'] ?? 'Unknown error';
        logWebhook('message_failed', $status, $error_msg, 'failed');
    }

    // Log analytics
    if (in_array($our_status, ['sent', 'delivered', 'read'])) {
        $conn->query("INSERT INTO analytics_events (event_type, data) VALUES ('message_$our_status', '{\"count\":1}')");
    }

    logWebhook('status_update', [
        'wa_message_id' => $msg_id,
        'status' => $our_status,
        'recipient' => $recipient
    ], "Updated $affected message(s) to $our_status");

    $conn->close();
}
?>
