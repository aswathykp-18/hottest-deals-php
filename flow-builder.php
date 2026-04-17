<?php
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Flow Builder';
$conn = getDbConnection();
$base = BASE_URL;

$flow = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM chatbot_flows WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $flow = $stmt->get_result()->fetch_assoc();
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['flow_name'];
    $description = $_POST['flow_description'] ?: null;
    $trigger_keyword = $_POST['trigger_keyword'];
    $flow_data = $_POST['flow_data'];

    if (isset($_POST['flow_id']) && $_POST['flow_id'] > 0) {
        $fid = intval($_POST['flow_id']);
        $stmt = $conn->prepare("UPDATE chatbot_flows SET name=?, description=?, trigger_keyword=?, flow_data=? WHERE id=?");
        $stmt->bind_param('ssssi', $name, $description, $trigger_keyword, $flow_data, $fid);
    } else {
        $stmt = $conn->prepare("INSERT INTO chatbot_flows (name, description, trigger_keyword, flow_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $description, $trigger_keyword, $flow_data);
    }
    $stmt->execute();
    jsonResponse(['success' => true, 'id' => $stmt->insert_id ?: ($_POST['flow_id'] ?? 0)]);
}

include 'includes/header.php';
?>

<div class="flow-builder-page">
    <div class="flow-builder-toolbar">
        <div class="toolbar-left">
            <a href="chatbot-flows.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
            <input type="text" id="flowName" class="toolbar-input" value="<?php echo sanitize($flow['name'] ?? 'New Flow'); ?>" placeholder="Flow Name">
            <input type="text" id="flowTrigger" class="toolbar-input-sm" value="<?php echo sanitize($flow['trigger_keyword'] ?? ''); ?>" placeholder="Trigger keyword">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-sm btn-outline" onclick="clearCanvas()"><i class="fas fa-trash"></i> Clear</button>
            <button class="btn btn-sm btn-wa" onclick="saveFlow()"><i class="fas fa-save"></i> Save Flow</button>
        </div>
    </div>

    <div class="flow-builder-container">
        <!-- Node Palette -->
        <div class="node-palette">
            <h4>Nodes</h4>
            <div class="palette-node" draggable="true" data-type="send_message">
                <i class="fas fa-comment"></i> Send Message
            </div>
            <div class="palette-node" draggable="true" data-type="ask_question">
                <i class="fas fa-question-circle"></i> Ask Question
            </div>
            <div class="palette-node" draggable="true" data-type="condition">
                <i class="fas fa-code-branch"></i> Condition
            </div>
            <div class="palette-node" draggable="true" data-type="delay">
                <i class="fas fa-clock"></i> Delay
            </div>
            <div class="palette-node" draggable="true" data-type="api_call">
                <i class="fas fa-globe"></i> API Call
            </div>
            <div class="palette-node" draggable="true" data-type="assign_agent">
                <i class="fas fa-user"></i> Assign Agent
            </div>
            <div class="palette-node" draggable="true" data-type="add_tag">
                <i class="fas fa-tag"></i> Add Tag
            </div>
        </div>

        <!-- Canvas -->
        <div class="flow-canvas" id="flowCanvas">
            <svg id="connectionsSvg" class="connections-layer"></svg>
            <div id="nodesContainer" class="nodes-container"></div>
        </div>

        <!-- Node Properties -->
        <div class="node-properties" id="nodeProperties" style="display:none">
            <h4>Node Properties</h4>
            <div id="propertiesContent"></div>
        </div>
    </div>
</div>

<input type="hidden" id="flowId" value="<?php echo $flow['id'] ?? ''; ?>">
<input type="hidden" id="flowDescription" value="<?php echo sanitize($flow['description'] ?? ''); ?>">
<input type="hidden" id="initialFlowData" value='<?php echo $flow ? addslashes($flow['flow_data']) : ''; ?>'>

<script>
const BASE_URL = '<?php echo $base; ?>';
let nodes = [];
let connections = [];
let selectedNode = null;
let nodeCounter = 0;
let isDragging = false;
let dragNode = null;
let dragOffset = { x: 0, y: 0 };
let isConnecting = false;
let connectFrom = null;

const canvas = document.getElementById('flowCanvas');
const nodesContainer = document.getElementById('nodesContainer');
const svg = document.getElementById('connectionsSvg');

// Node colors
const nodeColors = {
    send_message: '#25D366',
    ask_question: '#3b82f6',
    condition: '#f59e0b',
    delay: '#8b5cf6',
    api_call: '#ef4444',
    assign_agent: '#06b6d4',
    add_tag: '#ec4899'
};

const nodeIcons = {
    send_message: 'fa-comment',
    ask_question: 'fa-question-circle',
    condition: 'fa-code-branch',
    delay: 'fa-clock',
    api_call: 'fa-globe',
    assign_agent: 'fa-user',
    add_tag: 'fa-tag'
};

// Load existing flow
const initialData = document.getElementById('initialFlowData').value;
if (initialData) {
    try {
        const data = JSON.parse(initialData);
        if (data.nodes) {
            data.nodes.forEach(n => {
                createNode(n.type, n.position.x, n.position.y, n.data, n.id);
            });
        }
        if (data.edges) {
            data.edges.forEach(e => {
                connections.push({ from: e.from, to: e.to });
            });
            drawConnections();
        }
    } catch(e) { console.log('No existing flow data'); }
}

// Drag from palette
document.querySelectorAll('.palette-node').forEach(el => {
    el.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('nodeType', el.dataset.type);
    });
});

canvas.addEventListener('dragover', (e) => e.preventDefault());
canvas.addEventListener('drop', (e) => {
    e.preventDefault();
    const type = e.dataTransfer.getData('nodeType');
    if (type) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left + canvas.scrollLeft - 75;
        const y = e.clientY - rect.top + canvas.scrollTop - 25;
        createNode(type, x, y);
    }
});

function createNode(type, x, y, data = {}, existingId = null) {
    nodeCounter++;
    const id = existingId || 'node_' + Date.now() + '_' + nodeCounter;
    const color = nodeColors[type] || '#666';
    const icon = nodeIcons[type] || 'fa-circle';

    const node = {
        id, type, data,
        position: { x, y }
    };
    nodes.push(node);

    const el = document.createElement('div');
    el.className = 'flow-node';
    el.id = id;
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    el.style.borderTopColor = color;
    el.innerHTML = `
        <div class="node-header" style="background:${color}">
            <i class="fas ${icon}"></i>
            <span>${type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
            <button class="node-delete" onclick="deleteNode('${id}')">&times;</button>
        </div>
        <div class="node-body">
            ${getNodePreview(type, data)}
        </div>
        <div class="node-ports">
            <div class="port port-in" data-node="${id}" data-port="in" title="Connect input"></div>
            <div class="port port-out" data-node="${id}" data-port="out" title="Connect output"></div>
        </div>
    `;

    // Drag to move
    const header = el.querySelector('.node-header');
    header.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('node-delete')) return;
        isDragging = true;
        dragNode = node;
        const rect = el.getBoundingClientRect();
        dragOffset = { x: e.clientX - rect.left, y: e.clientY - rect.top };
        el.style.zIndex = 100;
    });

    // Click to select
    el.addEventListener('click', (e) => {
        if (!e.target.classList.contains('port')) {
            selectNode(node);
        }
    });

    // Port connections
    el.querySelectorAll('.port').forEach(port => {
        port.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            if (port.dataset.port === 'out') {
                isConnecting = true;
                connectFrom = id;
                canvas.style.cursor = 'crosshair';
            }
        });
        port.addEventListener('mouseup', (e) => {
            e.stopPropagation();
            if (isConnecting && port.dataset.port === 'in' && connectFrom !== id) {
                connections.push({ from: connectFrom, to: id });
                drawConnections();
            }
            isConnecting = false;
            connectFrom = null;
            canvas.style.cursor = 'default';
        });
    });

    nodesContainer.appendChild(el);
    return node;
}

document.addEventListener('mousemove', (e) => {
    if (isDragging && dragNode) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left + canvas.scrollLeft - dragOffset.x;
        const y = e.clientY - rect.top + canvas.scrollTop - dragOffset.y;
        dragNode.position = { x, y };
        const el = document.getElementById(dragNode.id);
        el.style.left = x + 'px';
        el.style.top = y + 'px';
        drawConnections();
    }
});

document.addEventListener('mouseup', () => {
    if (isDragging && dragNode) {
        document.getElementById(dragNode.id).style.zIndex = 1;
    }
    isDragging = false;
    dragNode = null;
    isConnecting = false;
    connectFrom = null;
    canvas.style.cursor = 'default';
});

function getNodePreview(type, data) {
    switch(type) {
        case 'send_message': return `<p>${data.message || 'Click to set message...'}</p>`;
        case 'ask_question': return `<p>${data.question || 'Click to set question...'}</p>`;
        case 'condition': return `<p>If: ${data.variable || 'variable'} = ?</p>`;
        case 'delay': return `<p>Wait ${data.seconds || '?'} seconds</p>`;
        case 'api_call': return `<p>${data.method || 'GET'} ${data.url || '/api/...'}</p>`;
        case 'assign_agent': return `<p>Assign to agent</p>`;
        case 'add_tag': return `<p>Tag: ${data.tag || '...'}</p>`;
        default: return '<p>Configure node...</p>';
    }
}

function selectNode(node) {
    selectedNode = node;
    document.querySelectorAll('.flow-node').forEach(el => el.classList.remove('selected'));
    document.getElementById(node.id).classList.add('selected');
    showProperties(node);
}

function showProperties(node) {
    const panel = document.getElementById('nodeProperties');
    const content = document.getElementById('propertiesContent');
    panel.style.display = 'block';

    let html = `<div class="prop-group"><label>Node Type</label><input value="${node.type.replace(/_/g, ' ')}" disabled></div>`;

    switch(node.type) {
        case 'send_message':
            html += `<div class="prop-group"><label>Message</label><textarea id="propMessage" rows="4" onchange="updateNodeData('message', this.value)">${node.data.message || ''}</textarea></div>`;
            break;
        case 'ask_question':
            html += `<div class="prop-group"><label>Question</label><textarea id="propQuestion" rows="3" onchange="updateNodeData('question', this.value)">${node.data.question || ''}</textarea></div>`;
            html += `<div class="prop-group"><label>Options (comma-separated)</label><input id="propOptions" value="${(node.data.options || []).join(',')}" onchange="updateNodeData('options', this.value.split(',').map(s=>s.trim()))"></div>`;
            break;
        case 'condition':
            html += `<div class="prop-group"><label>Variable</label><input value="${node.data.variable || ''}" onchange="updateNodeData('variable', this.value)"></div>`;
            break;
        case 'delay':
            html += `<div class="prop-group"><label>Seconds</label><input type="number" value="${node.data.seconds || 5}" onchange="updateNodeData('seconds', parseInt(this.value))"></div>`;
            break;
        case 'api_call':
            html += `<div class="prop-group"><label>URL</label><input value="${node.data.url || ''}" onchange="updateNodeData('url', this.value)"></div>`;
            html += `<div class="prop-group"><label>Method</label><select onchange="updateNodeData('method', this.value)"><option ${node.data.method=='GET'?'selected':''}>GET</option><option ${node.data.method=='POST'?'selected':''}>POST</option></select></div>`;
            break;
        case 'add_tag':
            html += `<div class="prop-group"><label>Tag Name</label><input value="${node.data.tag || ''}" onchange="updateNodeData('tag', this.value)"></div>`;
            break;
    }

    html += `<button class="btn btn-sm btn-danger" onclick="deleteNode('${node.id}')" style="margin-top:15px;width:100%"><i class="fas fa-trash"></i> Delete Node</button>`;
    content.innerHTML = html;
}

function updateNodeData(key, value) {
    if (selectedNode) {
        selectedNode.data[key] = value;
        const el = document.getElementById(selectedNode.id);
        el.querySelector('.node-body').innerHTML = getNodePreview(selectedNode.type, selectedNode.data);
    }
}

function deleteNode(id) {
    nodes = nodes.filter(n => n.id !== id);
    connections = connections.filter(c => c.from !== id && c.to !== id);
    document.getElementById(id)?.remove();
    document.getElementById('nodeProperties').style.display = 'none';
    selectedNode = null;
    drawConnections();
}

function drawConnections() {
    svg.innerHTML = '';
    connections.forEach(conn => {
        const fromEl = document.getElementById(conn.from);
        const toEl = document.getElementById(conn.to);
        if (!fromEl || !toEl) return;

        const fromPort = fromEl.querySelector('.port-out');
        const toPort = toEl.querySelector('.port-in');
        const fromRect = fromPort.getBoundingClientRect();
        const toRect = toPort.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();

        const x1 = fromRect.left - canvasRect.left + canvas.scrollLeft + 6;
        const y1 = fromRect.top - canvasRect.top + canvas.scrollTop + 6;
        const x2 = toRect.left - canvasRect.left + canvas.scrollLeft + 6;
        const y2 = toRect.top - canvasRect.top + canvas.scrollTop + 6;

        const midY = (y1 + y2) / 2;
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', `M${x1},${y1} C${x1},${midY} ${x2},${midY} ${x2},${y2}`);
        path.setAttribute('stroke', '#25D366');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.setAttribute('marker-end', 'url(#arrowhead)');
        svg.appendChild(path);
    });

    // Add arrowhead marker
    if (!svg.querySelector('defs')) {
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        defs.innerHTML = `<marker id="arrowhead" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto"><polygon points="0 0, 10 3.5, 0 7" fill="#25D366" /></marker>`;
        svg.prepend(defs);
    }
}

function clearCanvas() {
    if (confirm('Clear all nodes?')) {
        nodes = [];
        connections = [];
        nodesContainer.innerHTML = '';
        svg.innerHTML = '';
        document.getElementById('nodeProperties').style.display = 'none';
    }
}

function saveFlow() {
    const flowData = JSON.stringify({
        nodes: nodes.map(n => ({ id: n.id, type: n.type, data: n.data, position: n.position })),
        edges: connections
    });

    const formData = new FormData();
    formData.append('flow_name', document.getElementById('flowName').value);
    formData.append('flow_description', document.getElementById('flowDescription').value);
    formData.append('trigger_keyword', document.getElementById('flowTrigger').value);
    formData.append('flow_data', flowData);
    formData.append('flow_id', document.getElementById('flowId').value || '');

    fetch('flow-builder.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Flow saved successfully!');
                if (!document.getElementById('flowId').value) {
                    document.getElementById('flowId').value = data.id;
                }
            }
        })
        .catch(err => alert('Error saving flow'));
}
</script>

<?php $conn->close(); include 'includes/footer.php'; ?>
