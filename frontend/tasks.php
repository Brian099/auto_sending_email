<?php
$active_page = 'tasks';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">发送任务</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i> 新建任务
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>状态</th>
                        <th>收件人分组</th>
                        <th>创建时间</th>
                        <th>配置</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="tasks-table-body">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新建发送任务</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">最小间隔 (秒)</label>
                            <input type="number" class="form-control" name="interval_min" id="task-interval-min" min="0.1" step="0.1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">最大间隔 (秒)</label>
                            <input type="number" class="form-control" name="interval_max" id="task-interval-max" min="0.1" step="0.1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">并发数量</label>
                        <input type="number" class="form-control" name="concurrency" id="task-concurrency" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">目标收件人分组 (可选)</label>
                        <div id="group-select" class="border p-2" style="max-height: 200px; overflow-y: auto;">
                            <!-- loaded via JS -->
                        </div>
                        <div class="form-text">请勾选目标分组。不选则发送给所有活跃收件人。</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="addTask()">创建任务</button>
            </div>
        </div>
    </div>
</div>

<script>
    async function loadTasks() {
        // Load default settings
        try {
            const intervalMinData = await apiCall('/settings/default_interval_min');
            if (intervalMinData) document.getElementById('task-interval-min').value = intervalMinData.value;
            
            const intervalMaxData = await apiCall('/settings/default_interval_max');
            if (intervalMaxData) document.getElementById('task-interval-max').value = intervalMaxData.value;
            
            const concurrencyData = await apiCall('/settings/default_concurrency');
            if (concurrencyData) document.getElementById('task-concurrency').value = concurrencyData.value;
        } catch (e) {
            console.error("Failed to load default settings", e);
            // Fallback
            if(!document.getElementById('task-interval-min').value) document.getElementById('task-interval-min').value = 5;
            if(!document.getElementById('task-interval-max').value) document.getElementById('task-interval-max').value = 10;
            if(!document.getElementById('task-concurrency').value) document.getElementById('task-concurrency').value = 1;
        }

        try {
            // Load groups first to display names
            const groups = await apiCall('/recipients/groups/');
            const groupMap = {};
            const container = document.getElementById('group-select');
            container.innerHTML = '';
            
            if (groups.length === 0) {
                container.innerHTML = '<div class="text-muted p-2">暂无分组</div>';
            } else {
                groups.forEach(g => {
                    groupMap[g.id] = g.group_name;

                    const div = document.createElement('div');
                    div.className = 'form-check';
                    
                    const input = document.createElement('input');
                    input.className = 'form-check-input';
                    input.type = 'checkbox';
                    input.value = g.id;
                    input.id = 'group_check_' + g.id;
                    
                    const label = document.createElement('label');
                    label.className = 'form-check-label';
                    label.htmlFor = 'group_check_' + g.id;
                    label.innerText = g.group_name;
                    
                    div.appendChild(input);
                    div.appendChild(label);
                    container.appendChild(div);
                });
            }

            const data = await apiCall('/tasks/');
            const tbody = document.getElementById('tasks-table-body');
            tbody.innerHTML = '';
            
            data.forEach(item => {
                let statusBadge = 'bg-secondary';
                if (item.status === 'running') statusBadge = 'bg-success';
                if (item.status === 'paused') statusBadge = 'bg-warning text-dark';
                if (item.status === 'completed') statusBadge = 'bg-info text-dark';
                if (item.status === 'cancelled') statusBadge = 'bg-danger';

                const config = item.config || {};
                const interval = config.interval;
                const interval_min = config.interval_min;
                const interval_max = config.interval_max;
                let intervalDisplay = '';
                
                if (interval_min !== undefined && interval_max !== undefined) {
                    intervalDisplay = `${interval_min}-${interval_max}s`;
                } else {
                    intervalDisplay = `${interval || 1}s`;
                }
                
                const concurrency = config.concurrency || 1;

                // Process group names
                let groupNames = '所有 (默认)';
                if (config.group_ids && config.group_ids.length > 0) {
                    const names = config.group_ids.map(gid => groupMap[gid] || `未知分组(${gid})`);
                    groupNames = names.join(', ');
                }

                tbody.innerHTML += `
                    <tr>
                        <td>${item.id}</td>
                        <td><span class="badge ${statusBadge}">${item.status}</span></td>
                        <td>${groupNames}</td>
                        <td>${new Date(item.created_at).toLocaleString()}</td>
                        <td>${intervalDisplay} / ${concurrency}线程</td>
                        <td>
                            <a href="task_logs.php?task_id=${item.id}" class="btn btn-sm btn-info text-white">日志</a>
                            
                            ${item.status === 'pending' || item.status === 'paused' ? 
                                `<button class="btn btn-sm btn-success" onclick="controlTask(${item.id}, 'start', event)">启动</button>` : ''}
                            
                            ${item.status === 'running' ? 
                                `<button class="btn btn-sm btn-warning" onclick="controlTask(${item.id}, 'pause', event)">暂停</button>` : ''}
                            
                            ${item.status !== 'completed' && item.status !== 'cancelled' ? 
                                `<button class="btn btn-sm btn-danger" onclick="controlTask(${item.id}, 'stop', event)">停止</button>` : ''}
                        </td>
                    </tr>
                `;
            });

        } catch (e) {
            console.error(e);
        }
    }

    async function addTask() {
        const form = document.getElementById('addForm');
        const formData = new FormData(form);
        
        // Construct config object
        const config = {
            interval_min: parseFloat(formData.get('interval_min')),
            interval_max: parseFloat(formData.get('interval_max')),
            concurrency: parseInt(formData.get('concurrency')),
            group_ids: []
        };
        
        if (config.interval_min > config.interval_max) {
            alert("最小间隔不能大于最大间隔");
            return;
        }
        
        // Handle multiple select for groups
        const checkboxes = document.querySelectorAll('#group-select input[type="checkbox"]:checked');
        checkboxes.forEach(cb => {
            config.group_ids.push(parseInt(cb.value));
        });
        
        const payload = {
            status: "pending",
            config: config
        };

        try {
            await apiCall('/tasks/', 'POST', payload);
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
            form.reset();
            loadTasks();
        } catch (e) {
            // handled in apiCall
        }
    }

    async function controlTask(id, action, event) {
        if (event) event.stopPropagation();
        try {
            await apiCall(`/tasks/${id}/${action}`, 'POST');
            loadTasks();
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadTasks();
        // Refresh task list every 5s
        setInterval(loadTasks, 5000);
    });
</script>

<?php include 'includes/footer.php'; ?>
