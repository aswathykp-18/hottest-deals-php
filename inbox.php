<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Inbox';
$conn = getDbConnection();
$base = BASE_URL;

// Handle send message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $convo_id = intval($_POST['conversation_id']);
    $contact_id = intval($_POST['contact_id']);
    $content = $_POST['message_content'];

    $stmt = $conn->prepare("INSERT INTO messages (conversation_id, contact_id, direction, message_type, content, status) VALUES (?, ?, 'outbound', 'text', ?, 'sent')");
    $stmt->bind_param('iis', $convo_id, $contact_id, $content);
    $stmt->execute();

    $conn->query("UPDATE conversations SET last_message = '" . $conn->real_escape_string($content) . "', last_message_at = NOW() WHERE id = $convo_id");

    // Mock: simulate delivery after send
    $msg_id = $conn->insert_id;
    $conn->query("UPDATE messages SET status = 'delivered' WHERE id = $msg_id");

    header('Location: ' . $base . 'inbox.php?chat=' . $convo_id);
    exit;
}

// Get conversations
$conversations = $conn->query("SELECT c.*, ct.name as contact_name, ct.phone FROM conversations c JOIN contacts ct ON c.contact_id = ct.id ORDER BY c.last_message_at DESC");

// Active chat
$active_chat = null;
$chat_messages = null;
if (isset($_GET['chat'])) {
    $chat_id = intval($_GET['chat']);
    $stmt = $conn->prepare("SELECT c.*, ct.name as contact_name, ct.phone, ct.email, ct.tags FROM conversations c JOIN contacts ct ON c.contact_id = ct.id WHERE c.id = ?");
    $stmt->bind_param('i', $chat_id);
    $stmt->execute();
    $active_chat = $stmt->get_result()->fetch_assoc();

    if ($active_chat) {
        $chat_messages = $conn->query("SELECT * FROM messages WHERE conversation_id = $chat_id ORDER BY created_at ASC");
        $conn->query("UPDATE conversations SET unread_count = 0 WHERE id = $chat_id");
    }
}

include 'includes/header.php';
?>

<div class="inbox-layout">
    <!-- Conversation List -->
    <div class="inbox-sidebar">
        <div class="inbox-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search conversations..." id="convoSearch" onkeyup="filterConversations(this.value)">
        </div>
        <div class="convo-list">
            <?php while ($convo = $conversations->fetch_assoc()): ?>
            <a href="inbox.php?chat=<?php echo $convo['id']; ?>" class="convo-list-item <?php echo (isset($_GET['chat']) && $_GET['chat'] == $convo['id']) ? 'active' : ''; ?>">
                <div class="avatar-sm"><?php echo strtoupper(substr($convo['contact_name'], 0, 1)); ?></div>
                <div class="convo-list-info">
                    <div class="convo-list-name">
                        <?php echo sanitize($convo['contact_name']); ?>
                        <span class="convo-list-time"><?php echo timeAgo($convo['last_message_at']); ?></span>
                    </div>
                    <div class="convo-list-preview">
                        <?php echo sanitize(substr($convo['last_message'] ?? 'No messages yet', 0, 45)); ?>
                    </div>
                </div>
                <?php if ($convo['unread_count'] > 0): ?>
                    <span class="unread-badge"><?php echo $convo['unread_count']; ?></span>
                <?php endif; ?>
            </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
        <?php if ($active_chat): ?>
            <div class="chat-header">
                <div class="chat-contact-info">
                    <div class="avatar-md"><?php echo strtoupper(substr($active_chat['contact_name'], 0, 1)); ?></div>
                    <div>
                        <h4><?php echo sanitize($active_chat['contact_name']); ?></h4>
                        <span class="chat-phone"><i class="fab fa-whatsapp"></i> <?php echo sanitize($active_chat['phone']); ?></span>
                    </div>
                </div>
                <div class="chat-actions">
                    <span class="status-pill status-<?php echo $active_chat['status']; ?>"><?php echo ucfirst($active_chat['status']); ?></span>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php while ($msg = $chat_messages->fetch_assoc()): ?>
                <div class="message <?php echo $msg['direction']; ?>">
                    <div class="message-bubble">
                        <div class="message-content"><?php echo nl2br(sanitize($msg['content'])); ?></div>
                        <div class="message-meta">
                            <span class="message-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                            <?php if ($msg['direction'] == 'outbound'): ?>
                                <span class="message-status">
                                    <?php if ($msg['status'] == 'read'): ?><i class="fas fa-check-double" style="color:#53bdeb"></i>
                                    <?php elseif ($msg['status'] == 'delivered'): ?><i class="fas fa-check-double"></i>
                                    <?php else: ?><i class="fas fa-check"></i>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="chat-input">
                <form method="POST" class="chat-form">
                    <input type="hidden" name="send_message" value="1">
                    <input type="hidden" name="conversation_id" value="<?php echo $active_chat['id']; ?>">
                    <input type="hidden" name="contact_id" value="<?php echo $active_chat['contact_id']; ?>">
                    <button type="button" class="chat-btn"><i class="fas fa-paperclip"></i></button>
                    <input type="text" name="message_content" placeholder="Type a message..." required autofocus>
                    <button type="button" class="chat-btn"><i class="fas fa-smile"></i></button>
                    <button type="submit" class="chat-btn send-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        <?php else: ?>
            <div class="chat-empty">
                <i class="fab fa-whatsapp"></i>
                <h3>Select a conversation</h3>
                <p>Choose a contact from the list to start chatting</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll to bottom of chat
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

function filterConversations(query) {
    const items = document.querySelectorAll('.convo-list-item');
    items.forEach(item => {
        const name = item.querySelector('.convo-list-name').textContent.toLowerCase();
        item.style.display = name.includes(query.toLowerCase()) ? 'flex' : 'none';
    });
}
</script>

<?php
$conn->close();
include 'includes/footer.php';
?>
