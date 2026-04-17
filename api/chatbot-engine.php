<?php
/**
 * Chatbot Flow Engine
 * 
 * Processes incoming messages against chatbot flows and
 * triggers auto-replies based on keyword matches
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/whatsapp-service.php';

/**
 * Check if incoming message triggers any chatbot flow
 */
function triggerChatbotFlow($contact_id, $conversation_id, $message_text, $message_type) {
    // Only process text messages for chatbot triggers
    if ($message_type !== 'text' && $message_type !== 'interactive' && $message_type !== 'button') {
        return;
    }

    $conn = getDbConnection();
    $message_lower = strtolower(trim($message_text));

    // Find active flows with matching trigger keywords
    $flows = $conn->query("SELECT * FROM chatbot_flows WHERE is_active = 1 ORDER BY id ASC");

    $matched_flow = null;
    while ($flow = $flows->fetch_assoc()) {
        $keyword = strtolower(trim($flow['trigger_keyword']));
        if (empty($keyword)) continue;

        // Check for keyword match (exact, starts with, or contains)
        if ($message_lower === $keyword ||
            str_starts_with($message_lower, $keyword) ||
            str_contains($message_lower, $keyword)) {
            $matched_flow = $flow;
            break;
        }
    }

    if (!$matched_flow) {
        $conn->close();
        return;
    }

    // Get contact phone
    $stmt = $conn->prepare("SELECT phone, name FROM contacts WHERE id = ?");
    $stmt->bind_param('i', $contact_id);
    $stmt->execute();
    $contact = $stmt->get_result()->fetch_assoc();

    if (!$contact) {
        $conn->close();
        return;
    }

    // Parse flow data
    $flow_data = json_decode($matched_flow['flow_data'], true);
    if (!$flow_data || !isset($flow_data['nodes'])) {
        $conn->close();
        return;
    }

    // Log flow trigger
    logFlowExecution($conn, $matched_flow['id'], $contact_id, $conversation_id, 'triggered', "Keyword matched: " . $matched_flow['trigger_keyword']);

    // Execute flow nodes
    executeFlowNodes($conn, $flow_data, $contact, $contact_id, $conversation_id, $message_text);

    $conn->close();
}

/**
 * Execute flow nodes in sequence
 */
function executeFlowNodes($conn, $flow_data, $contact, $contact_id, $conversation_id, $user_input) {
    $nodes = $flow_data['nodes'];
    $edges = $flow_data['edges'] ?? [];

    if (empty($nodes)) return;

    // Build edge map: from_node_id -> [to_node_ids]
    $edge_map = [];
    foreach ($edges as $edge) {
        $from = $edge['from'] ?? '';
        $to = $edge['to'] ?? '';
        if ($from && $to) {
            $edge_map[$from][] = $to;
        }
    }

    // Build node lookup
    $node_map = [];
    foreach ($nodes as $node) {
        $node_map[$node['id']] = $node;
    }

    // Find the first node (no incoming edges)
    $has_incoming = [];
    foreach ($edges as $edge) {
        $has_incoming[$edge['to'] ?? ''] = true;
    }

    $start_node = null;
    foreach ($nodes as $node) {
        if (!isset($has_incoming[$node['id']])) {
            $start_node = $node;
            break;
        }
    }

    if (!$start_node) {
        $start_node = $nodes[0]; // Fallback to first node
    }

    // Execute nodes following the flow
    $current_node = $start_node;
    $max_steps = 10; // Prevent infinite loops
    $step = 0;
    $wa_service = new WhatsAppService();

    while ($current_node && $step < $max_steps) {
        $step++;
        $node_type = $current_node['type'] ?? '';
        $node_data = $current_node['data'] ?? [];
        $node_id = $current_node['id'];

        switch ($node_type) {
            case 'send_message':
                $message = $node_data['message'] ?? '';
                if ($message) {
                    // Replace variables
                    $message = str_replace('{{name}}', $contact['name'], $message);
                    $message = str_replace('{{phone}}', $contact['phone'], $message);
                    $message = str_replace('{{1}}', $contact['name'], $message);

                    // Send message
                    $wa_service->sendTextMessage($contact['phone'], $message);

                    // Small delay between messages
                    usleep(500000); // 0.5 seconds
                }
                break;

            case 'ask_question':
                $question = $node_data['question'] ?? '';
                $options = $node_data['options'] ?? [];

                if ($question && !empty($options)) {
                    // Send as interactive button message (max 3 options) or list
                    if (count($options) <= 3) {
                        $wa_service->sendButtonMessage($contact['phone'], $question, $options);
                    } else {
                        // Send as text with numbered options
                        $text = $question . "\n\n";
                        foreach ($options as $i => $opt) {
                            $text .= ($i + 1) . ". " . $opt . "\n";
                        }
                        $text .= "\nReply with your choice number.";
                        $wa_service->sendTextMessage($contact['phone'], $text);
                    }
                } elseif ($question) {
                    $wa_service->sendTextMessage($contact['phone'], $question);
                }
                // After ask_question, we typically stop and wait for user response
                // The next message from user will re-trigger flow matching
                return;

            case 'condition':
                $variable = $node_data['variable'] ?? 'answer';
                $conditions = $node_data['conditions'] ?? [];

                // Try to match user input to conditions
                $matched_next = null;
                foreach ($conditions as $cond) {
                    $value = strtolower($cond['value'] ?? '');
                    $next = $cond['next'] ?? '';
                    if (str_contains(strtolower($user_input), $value) && $next && isset($node_map[$next])) {
                        $matched_next = $node_map[$next];
                        break;
                    }
                }

                if ($matched_next) {
                    $current_node = $matched_next;
                    continue 2; // Skip the default next-node logic
                }
                break;

            case 'delay':
                $seconds = intval($node_data['seconds'] ?? 2);
                $seconds = min($seconds, 5); // Cap at 5 seconds
                sleep($seconds);
                break;

            case 'api_call':
                // In mock mode, just log. In live mode, make the API call
                $api_url = $node_data['url'] ?? '';
                $api_method = $node_data['method'] ?? 'GET';
                // Skip actual API calls for now - log it
                logFlowExecution($conn, 0, $contact_id, $conversation_id, 'api_call', "Would call $api_method $api_url");
                break;

            case 'assign_agent':
                // Update conversation to assign to an agent
                $conn->query("UPDATE conversations SET assigned_to = 1 WHERE id = $conversation_id");
                break;

            case 'add_tag':
                $tag = $node_data['tag'] ?? '';
                if ($tag) {
                    $stmt = $conn->prepare("SELECT tags FROM contacts WHERE id = ?");
                    $stmt->bind_param('i', $contact_id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $existing_tags = $result['tags'] ?? '';
                    $tags_array = $existing_tags ? explode(',', $existing_tags) : [];
                    if (!in_array($tag, $tags_array)) {
                        $tags_array[] = $tag;
                        $new_tags = implode(',', $tags_array);
                        $stmt = $conn->prepare("UPDATE contacts SET tags = ? WHERE id = ?");
                        $stmt->bind_param('si', $new_tags, $contact_id);
                        $stmt->execute();
                    }
                }
                break;
        }

        // Move to next node via edges
        $next_nodes = $edge_map[$node_id] ?? [];
        if (!empty($next_nodes) && isset($node_map[$next_nodes[0]])) {
            $current_node = $node_map[$next_nodes[0]];
        } else {
            break; // No more nodes
        }
    }
}

/**
 * Log flow execution
 */
function logFlowExecution($conn, $flow_id, $contact_id, $conversation_id, $action, $details) {
    $data = json_encode([
        'flow_id' => $flow_id,
        'contact_id' => $contact_id,
        'conversation_id' => $conversation_id,
        'action' => $action,
        'details' => $details
    ]);
    $stmt = $conn->prepare("INSERT INTO webhook_logs (event_type, payload, response, status, ip_address, created_at) VALUES ('chatbot_flow', ?, ?, 'success', 'system', NOW())");
    $stmt->bind_param('ss', $data, $details);
    $stmt->execute();
}
?>
