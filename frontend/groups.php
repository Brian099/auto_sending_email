<?php
$active_page = 'groups';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">收件人分组管理</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal()">
            <i class="bi bi-plus"></i> 新建分组
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>分组名称</th>
                <th>描述</th>
                <th>已绑定模板</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="groups-table-body">
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="groupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">新建分组</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="groupForm">
                    <input type="hidden" name="id" id="groupId">
                    <div class="mb-3">
                        <label class="form-label">分组名称</label>
                        <input type="text" class="form-control" name="group_name" id="groupName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea class="form-control" name="description" id="groupDescription"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">绑定邮件模板 (可选)</label>
                        <div id="groupTemplates" class="border p-2" style="max-height: 200px; overflow-y: auto;">
                            <!-- populated by JS -->
                        </div>
                        <div class="form-text">请勾选需要绑定的模板。发送给该分组时将优先使用选中的模板。</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="saveGroup()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
    let groupModal = null;
    let allTemplates = [];

    document.addEventListener('DOMContentLoaded', () => {
        groupModal = new bootstrap.Modal(document.getElementById('groupModal'));
        loadGroups();
        loadTemplates();
    });

    async function loadTemplates() {
        try {
            allTemplates = await apiCall('/templates/');
        } catch (e) {
            console.error('Failed to load templates', e);
        }
    }

    async function loadGroups() {
        try {
            const data = await apiCall('/recipients/groups/');
            const tbody = document.getElementById('groups-table-body');
            tbody.innerHTML = '';
            
            data.forEach(item => {
                let templatesHtml = '';
                if (item.template_names && item.template_names.length > 0) {
                    templatesHtml = item.template_names.map(name => `<span class="badge bg-info text-dark me-1">${name}</span>`).join('');
                } else {
                    templatesHtml = '<span class="text-muted small">无</span>';
                }

                tbody.innerHTML += `
                    <tr>
                        <td>${item.id}</td>
                        <td>${item.group_name}</td>
                        <td>${item.description || '-'}</td>
                        <td>${templatesHtml}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick='editGroup(${JSON.stringify(item)})'>编辑</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteGroup(${item.id})">删除</button>
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            console.error(e);
        }
    }

    function openModal() {
        document.getElementById('groupForm').reset();
        document.getElementById('groupId').value = '';
        document.getElementById('modalTitle').innerText = '新建分组';
        
        renderTemplateSelect([]);
        
        groupModal.show();
    }

    function editGroup(item) {
        document.getElementById('groupId').value = item.id;
        document.getElementById('groupName').value = item.group_name;
        document.getElementById('groupDescription').value = item.description || '';
        document.getElementById('modalTitle').innerText = '编辑分组';
        
        renderTemplateSelect(item.template_ids || []);
        
        groupModal.show();
    }

    function renderTemplateSelect(selectedIds) {
        const container = document.getElementById('groupTemplates');
        container.innerHTML = '';
        
        if (allTemplates.length === 0) {
            container.innerHTML = '<div class="text-muted p-2">暂无可用模板</div>';
            return;
        }
        
        allTemplates.forEach(tpl => {
            const div = document.createElement('div');
            div.className = 'form-check';
            
            const input = document.createElement('input');
            input.className = 'form-check-input';
            input.type = 'checkbox';
            input.value = tpl.id;
            input.id = 'tpl_check_' + tpl.id;
            if (selectedIds.includes(tpl.id)) {
                input.checked = true;
            }
            
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = 'tpl_check_' + tpl.id;
            label.innerText = tpl.subject;
            
            div.appendChild(input);
            div.appendChild(label);
            container.appendChild(div);
        });
    }

    async function saveGroup() {
        const form = document.getElementById('groupForm');
        // FormData doesn't handle multiple select well by default with Object.fromEntries for same key
        // But here we need to extract it manually
        
        const groupName = document.getElementById('groupName').value;
        const description = document.getElementById('groupDescription').value;
        
        const checkboxes = document.querySelectorAll('#groupTemplates input[type="checkbox"]:checked');
        const templateIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
        
        const data = {
            group_name: groupName,
            description: description,
            template_ids: templateIds
        };
        
        const id = document.getElementById('groupId').value;
        const method = id ? 'PATCH' : 'POST';
        const url = id ? `/recipients/groups/${id}` : '/recipients/groups/';

        try {
            await apiCall(url, method, data);
            groupModal.hide();
            loadGroups();
        } catch (e) {
            // handled in apiCall
        }
    }

    async function deleteGroup(id) {
        if (!confirm('确定要删除这个分组吗？组内收件人将变为无分组状态。')) return;
        try {
            await apiCall(`/recipients/groups/${id}`, 'DELETE');
            loadGroups();
        } catch (e) {}
    }
</script>

<?php include 'includes/footer.php'; ?>
