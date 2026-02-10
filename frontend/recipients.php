<?php
$active_page = 'recipients';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">收件人管理</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="input-group me-2">
            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="搜索邮箱/姓名/公司..." onkeyup="handleSearch(event)">
            <button class="btn btn-sm btn-outline-secondary" onclick="loadRecipients()">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload"></i> 导入 CSV
        </button>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> 添加收件人
        </button>
    </div>
</div>

<!-- Batch Toolbar -->
<div id="batch-toolbar" class="mb-3 p-2 bg-light border rounded d-none align-items-center">
    <span class="me-3">已选择 <span id="selected-count" class="fw-bold">0</span> 项</span>
    <button class="btn btn-sm btn-outline-primary me-2" onclick="openBatchGroupModal()">
        <i class="bi bi-folder-plus"></i> 批量分组
    </button>
    <button class="btn btn-sm btn-outline-warning me-2" onclick="batchStatus('inactive')">
        <i class="bi bi-pause-circle"></i> 停用
    </button>
    <button class="btn btn-sm btn-outline-success me-2" onclick="batchStatus('active')">
        <i class="bi bi-play-circle"></i> 启用
    </button>
    <button class="btn btn-sm btn-outline-danger" onclick="batchDelete()">
        <i class="bi bi-trash"></i> 删除
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
                <th>ID</th>
                <th>邮箱</th>
                <th>姓名</th>
                <th>公司</th>
                <th>分组</th>
                <th>备注</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="recipients-table-body">
            <!-- Data loaded via JS -->
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加收件人</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <div class="mb-3">
                        <label class="form-label">邮箱</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">公司</label>
                        <input type="text" class="form-control" name="company">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分组</label>
                        <select class="form-select group-select" name="group_id">
                            <option value="">(无分组)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">备注</label>
                        <input type="text" class="form-control" name="note">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="addRecipient()">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑收件人</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="id">
                    <div class="mb-3">
                        <label class="form-label">邮箱</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">姓名</label>
                        <input type="text" class="form-control" name="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">公司</label>
                        <input type="text" class="form-control" name="company">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分组</label>
                        <select class="form-select group-select" name="group_id">
                            <option value="">(无分组)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">备注</label>
                        <input type="text" class="form-control" name="note">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">状态</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="updateRecipient()">保存修改</button>
            </div>
        </div>
    </div>
</div>

<!-- Batch Group Modal -->
<div class="modal fade" id="batchGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">批量设置分组</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="batchGroupForm">
                    <div class="mb-3">
                        <label class="form-label">选择分组</label>
                        <select class="form-select group-select" name="group_id">
                            <option value="">(无分组)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="batchSetGroup()">确定</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">导入 CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>CSV 格式要求：表头需包含 <code>email</code>, <code>name</code>, <code>company</code>, <code>note</code></p>
                <form id="importForm">
                    <div class="mb-3">
                        <label class="form-label">选择分组</label>
                        <select class="form-select group-select" name="group_id">
                            <option value="">(无分组)</option>
                        </select>
                        <div class="form-text">导入的收件人将加入此分组</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">选择文件</label>
                        <input type="file" class="form-control" name="file" accept=".csv" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="importRecipients()">开始导入</button>
            </div>
        </div>
    </div>
</div>

<script>
    let groupsMap = {};
    let allRecipients = [];

    async function loadGroupsAndRecipients() {
        // Load groups first
        try {
            const groups = await apiCall('/recipients/groups/');
            groupsMap = {};
            const selectEls = document.querySelectorAll('.group-select');
            
            // Clear existing options (except first)
            selectEls.forEach(select => {
                select.innerHTML = '<option value="">(无分组)</option>';
                groups.forEach(g => {
                    groupsMap[g.id] = g.group_name;
                    select.innerHTML += `<option value="${g.id}">${g.group_name}</option>`;
                });
            });
        } catch (e) {
            console.error("Failed to load groups", e);
        }

        // Then load recipients
        await loadRecipients();
    }

    async function loadRecipients() {
        try {
            const searchInput = document.getElementById('searchInput');
            let url = '/recipients/';
            if (searchInput && searchInput.value.trim() !== '') {
                url += `?q=${encodeURIComponent(searchInput.value.trim())}`;
            }

            const data = await apiCall(url);
            allRecipients = data;
            const tbody = document.getElementById('recipients-table-body');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">暂无数据</td></tr>';
                return;
            }

            data.forEach(item => {
                const groupName = item.group_id && groupsMap[item.group_id] ? groupsMap[item.group_id] : '-';
                const statusBadge = item.status === 'active' 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-secondary">Inactive</span>';
                
                // Construct JSON string safely
                const itemJson = JSON.stringify(item).replace(/"/g, '&quot;');
                
                tbody.innerHTML += `
                    <tr>
                        <td><input type="checkbox" class="recipient-checkbox" value="${item.id}" onclick="updateBatchUI()"></td>
                        <td>${item.id}</td>
                        <td>${item.email}</td>
                        <td>${item.name || '-'}</td>
                        <td>${item.company || '-'}</td>
                        <td>${groupName}</td>
                        <td>${item.note || '-'}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(${itemJson})">编辑</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRecipient(${item.id})">删除</button>
                        </td>
                    </tr>
                `;
            });
            updateBatchUI();
        } catch (e) {
            console.error(e);
        }
    }

    // --- Search Handler ---
    let searchTimeout = null;
    function handleSearch(event) {
        if (event.key === 'Enter') {
            loadRecipients();
            return;
        }
        // Debounce search
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadRecipients();
        }, 500);
    }

    // --- Single Item Actions ---

    async function addRecipient() {
        const form = document.getElementById('addForm');
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'group_id' && value === "") {
                data[key] = null;
            } else {
                data[key] = value;
            }
        });
        
        try {
            await apiCall('/recipients/', 'POST', data);
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
            form.reset();
            loadRecipients();
        } catch (e) {}
    }

    function openEditModal(item) {
        const form = document.getElementById('editForm');
        form.id.value = item.id;
        form.email.value = item.email;
        form.name.value = item.name || '';
        form.company.value = item.company || '';
        form.note.value = item.note || '';
        form.status.value = item.status;
        form.group_id.value = item.group_id || '';
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    async function updateRecipient() {
        const form = document.getElementById('editForm');
        const id = form.id.value;
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'group_id' && value === "") {
                data[key] = null;
            } else {
                data[key] = value;
            }
        });

        try {
            await apiCall(`/recipients/${id}`, 'PATCH', data);
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadRecipients();
        } catch (e) {}
    }

    async function deleteRecipient(id) {
        if (!confirm('确定要删除吗？')) return;
        try {
            await apiCall(`/recipients/${id}`, 'DELETE');
            loadRecipients();
        } catch (e) {}
    }

    // --- Batch Actions ---

    function toggleSelectAll() {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.recipient-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBatchUI();
    }

    function updateBatchUI() {
        const checkboxes = document.querySelectorAll('.recipient-checkbox:checked');
        const count = checkboxes.length;
        document.getElementById('selected-count').innerText = count;
        
        const toolbar = document.getElementById('batch-toolbar');
        if (count > 0) {
            toolbar.classList.remove('d-none');
            toolbar.classList.add('d-flex');
        } else {
            toolbar.classList.add('d-none');
            toolbar.classList.remove('d-flex');
        }
    }

    function getSelectedIds() {
        const checkboxes = document.querySelectorAll('.recipient-checkbox:checked');
        return Array.from(checkboxes).map(cb => parseInt(cb.value));
    }

    async function batchStatus(status) {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        
        try {
            await apiCall('/recipients/batch', 'POST', {
                recipient_ids: ids,
                action: 'set_status',
                value: status
            });
            loadRecipients();
        } catch (e) {}
    }

    async function batchDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        if (!confirm(`确定要删除选中的 ${ids.length} 个收件人吗？`)) return;

        try {
            await apiCall('/recipients/batch', 'POST', {
                recipient_ids: ids,
                action: 'delete'
            });
            loadRecipients();
        } catch (e) {}
    }

    function openBatchGroupModal() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        new bootstrap.Modal(document.getElementById('batchGroupModal')).show();
    }

    async function batchSetGroup() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        
        const form = document.getElementById('batchGroupForm');
        const groupId = form.group_id.value || null; // Handle empty string as null

        try {
            await apiCall('/recipients/batch', 'POST', {
                recipient_ids: ids,
                action: 'set_group',
                value: groupId
            });
            bootstrap.Modal.getInstance(document.getElementById('batchGroupModal')).hide();
            loadRecipients();
        } catch (e) {}
    }

    // --- Import ---

    async function importRecipients() {
        const form = document.getElementById('importForm');
        const formData = new FormData(form);
        const groupId = formData.get('group_id');
        
        // Prepare URL with query param if group_id is present
        let url = '/api/recipients/import';
        if (groupId) {
            url += `?group_id=${groupId}`;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData // Content-Type header handled automatically for FormData
            });
            const result = await response.json();
            if (response.ok) {
                alert(`导入成功: ${result.imported_count} 条`);
                if(result.errors && result.errors.length > 0) {
                    alert('部分错误:\n' + result.errors.join('\n'));
                }
                bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
                form.reset();
                loadRecipients();
            } else {
                alert('导入失败: ' + (result.detail || 'Unknown error'));
            }
        } catch (e) {
            alert('导入错误: ' + e.message);
        }
    }

    document.addEventListener('DOMContentLoaded', loadGroupsAndRecipients);
</script>

<?php include 'includes/footer.php'; ?>
