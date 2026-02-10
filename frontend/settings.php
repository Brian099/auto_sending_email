<?php
$active_page = 'settings';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">系统设置</h1>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                默认发送配置
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">最小发送间隔 (秒)</label>
                            <input type="number" class="form-control" name="default_interval_min" id="default_interval_min" min="0.1" step="0.1" required>
                            <div class="form-text">发送任务时的最小等待时间。</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">最大发送间隔 (秒)</label>
                            <input type="number" class="form-control" name="default_interval_max" id="default_interval_max" min="0.1" step="0.1" required>
                            <div class="form-text">发送任务时的最大等待时间。</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">默认并发数量</label>
                        <input type="number" class="form-control" name="default_concurrency" id="default_concurrency" min="1" max="10" required>
                        <div class="form-text">创建新任务时默认使用的并发发送线程数。建议不要超过5。</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">系统基础URL (Base URL)</label>
                        <input type="text" class="form-control" name="system_base_url" id="system_base_url" placeholder="http://example.com:18088">
                        <div class="form-text">用于生成邮件中图片和链接的绝对路径。请填写外部可访问的地址 (包含 http/https)。</div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="saveSettings()">保存设置</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                账户安全
            </div>
            <div class="card-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">当前密码</label>
                        <input type="password" class="form-control" id="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">新密码</label>
                        <input type="password" class="form-control" id="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">确认新密码</label>
                        <input type="password" class="form-control" id="confirm_password" required>
                    </div>
                    <button type="button" class="btn btn-warning" onclick="changePassword()">修改密码</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadSettings() {
        try {
            // Load interval min
            const intervalMinData = await apiCall('/settings/default_interval_min');
            if (intervalMinData) {
                document.getElementById('default_interval_min').value = intervalMinData.value;
            }

            // Load interval max
            const intervalMaxData = await apiCall('/settings/default_interval_max');
            if (intervalMaxData) {
                document.getElementById('default_interval_max').value = intervalMaxData.value;
            }

            // Load concurrency
            const concurrencyData = await apiCall('/settings/default_concurrency');
            if (concurrencyData) {
                document.getElementById('default_concurrency').value = concurrencyData.value;
            }

            // Load base url
            const baseUrlData = await apiCall('/settings/system_base_url');
            if (baseUrlData) {
                document.getElementById('system_base_url').value = baseUrlData.value;
            }
        } catch (e) {
            console.error(e);
            alert('加载设置失败');
        }
    }

    async function saveSettings() {
        const intervalMin = document.getElementById('default_interval_min').value;
        const intervalMax = document.getElementById('default_interval_max').value;
        const concurrency = document.getElementById('default_concurrency').value;
        const baseUrl = document.getElementById('system_base_url').value;
        
        if (parseFloat(intervalMin) > parseFloat(intervalMax)) {
            alert('最小间隔不能大于最大间隔');
            return;
        }

        try {
            // Save interval min
            await apiCall('/settings/default_interval_min', 'PUT', {
                value: intervalMin,
                description: 'Default minimum sending interval'
            });

            // Save interval max
            await apiCall('/settings/default_interval_max', 'PUT', {
                value: intervalMax,
                description: 'Default maximum sending interval'
            });

            // Save concurrency
            await apiCall('/settings/default_concurrency', 'PUT', {
                value: concurrency,
                description: 'Default concurrency'
            });

            // Save base url
            await apiCall('/settings/system_base_url', 'PUT', {
                value: baseUrl,
                description: 'System Base URL for images/links'
            });

            alert('设置已保存');
        } catch (e) {
            console.error(e);
            alert('保存失败: ' + e.message);
        }
    }

    async function changePassword() {
        const oldPassword = document.getElementById('old_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (!oldPassword || !newPassword || !confirmPassword) {
            alert('请填写所有密码字段');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            alert('两次输入的新密码不一致');
            return;
        }
        
        try {
            await apiCall('/auth/change-password', 'POST', {
                old_password: oldPassword,
                new_password: newPassword
            });
            
            alert('密码修改成功');
            document.getElementById('passwordForm').reset();
        } catch (e) {
            console.error(e);
            alert('密码修改失败: ' + e.message);
        }
    }

    document.addEventListener('DOMContentLoaded', loadSettings);
</script>

<?php include 'includes/footer.php'; ?>
