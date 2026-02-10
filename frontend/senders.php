<?php
$active_page = 'senders';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">发件箱管理</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> 添加发件箱
        </button>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>邮箱</th>
                <th>SMTP 主机</th>
                <th>SMTP 端口</th>
                <th>SMTP 用户</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="senders-table-body">
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加发件箱</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <div class="mb-3">
                        <label class="form-label">邮箱地址</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SMTP 主机</label>
                            <input type="text" class="form-control" name="smtp_host" placeholder="smtp.example.com" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">端口</label>
                            <input type="number" class="form-control" name="smtp_port" value="465" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP 用户名</label>
                        <input type="text" class="form-control" name="smtp_user" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SMTP 密码</label>
                        <input type="password" class="form-control" name="smtp_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-info text-white" onclick="verifySender()">验证连接</button>
                <button type="button" class="btn btn-primary" onclick="addSender()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadSenders() {
        try {
            const data = await apiCall('/senders/');
            const tbody = document.getElementById('senders-table-body');
            tbody.innerHTML = '';
            data.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td>${item.id}</td>
                        <td>${item.email}</td>
                        <td>${item.smtp_host}</td>
                        <td>${item.smtp_port}</td>
                        <td>${item.smtp_user}</td>
                        <td>${item.status}</td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="deleteSender(${item.id})">删除</button>
                        </td>
                    </tr>
                `;
            });
        } catch (e) {
            console.error(e);
        }
    }

    async function verifySender() {
        const form = document.getElementById('addForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.smtp_port = parseInt(data.smtp_port);

        const btn = event.target;
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = '验证中...';

        try {
            await apiCall('/senders/verify', 'POST', data, true);
            alert('验证成功！SMTP连接正常');
        } catch (e) {
            console.error(e);
            if (e.message.includes('Method Not Allowed') || e.message.includes('Not Found')) {
                alert('验证失败: 后端接口未生效，请重启后端容器以应用更新 (docker compose restart app)');
            } else {
                alert('验证失败: ' + e.message);
            }
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }

    async function addSender() {
        const form = document.getElementById('addForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        // Convert port to int
        data.smtp_port = parseInt(data.smtp_port);
        
        const btn = event.target;
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = '保存中...';

        try {
            // The backend now verifies on create too.
            await apiCall('/senders/', 'POST', data);
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
            form.reset();
            loadSenders();
            alert('保存成功');
        } catch (e) {
            // handled in apiCall
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }

    async function deleteSender(id) {
        if (!confirm('确定要删除吗？')) return;
        try {
            await apiCall(`/senders/${id}`, 'DELETE');
            loadSenders();
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', loadSenders);
</script>

<?php include 'includes/footer.php'; ?>
