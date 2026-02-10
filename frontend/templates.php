<?php
$active_page = 'templates';
include 'includes/header.php';
?>

<!-- 引入 TinyMCE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">邮件模版</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" onclick="openModal()">
            <i class="bi bi-plus"></i> 新建模版
        </button>
    </div>
</div>

<div class="row" id="templates-container">
    <!-- Data loaded via JS -->
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">新建模版</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" name="id" id="templateId">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">主题</label>
                            <input type="text" class="form-control" name="subject" id="subject" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-text mb-2">
                            可用变量: <span class="badge bg-light text-dark">{name}</span> <span class="badge bg-light text-dark">{company}</span> <span class="badge bg-light text-dark">{email}</span>
                        </div>
                        <textarea class="form-control" name="content" id="content-editor" rows="20"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
    let editorInstance = null;
    let templateModal = null;

    document.addEventListener('DOMContentLoaded', () => {
        templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
        
        // 解决 Bootstrap Modal 中 TinyMCE 的焦点问题
        document.addEventListener('focusin', (e) => {
            if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                e.stopImmediatePropagation();
            }
        });

        loadTemplates();
    });

    async function loadTemplates() {
        try {
            const data = await apiCall('/templates/');
            const container = document.getElementById('templates-container');
            container.innerHTML = '';
            
            data.forEach(item => {
                // 简单的文本预览，去除 HTML 标签
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = item.content;
                const textPreview = tempDiv.textContent || tempDiv.innerText || "";
                
                container.innerHTML += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-truncate" title="${item.subject}">${item.subject}</h6>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick='editTemplate(${JSON.stringify(item)})'>编辑</button>
                                    <button class="btn btn-outline-danger" onclick="deleteTemplate(${item.id})">删除</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <hr>
                                <div class="template-preview small text-muted" style="max-height: 100px; overflow: hidden;">
                                    ${textPreview.substring(0, 100)}...
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

        } catch (e) {
            console.error(e);
        }
    }

    function initEditor() {
        if (tinymce.get('content-editor')) {
            return;
        }
        tinymce.init({
            selector: '#content-editor',
            language: 'zh-Hans', // 如果需要中文包需要额外引入，这里先用默认英文或浏览器自适应
            height: 500,
            plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons',
            toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
            menubar: 'file edit view insert format tools table help',
            branding: false,
            promotion: false,
            // Image Upload Configuration
            images_upload_url: '/api/upload/image',
            automatic_uploads: true,
            file_picker_types: 'image',
            // Optional: Custom file picker if needed, but default image button works with upload_url
        });
    }

    function openModal() {
        document.getElementById('templateForm').reset();
        document.getElementById('templateId').value = '';
        document.getElementById('modalTitle').innerText = '新建模版';
        
        templateModal.show();
        
        // Modal 显示后初始化编辑器（避免渲染问题）
        setTimeout(initEditor, 100);
        // 清空编辑器
        if (tinymce.get('content-editor')) {
            tinymce.get('content-editor').setContent('');
        }
    }

    function editTemplate(item) {
        document.getElementById('templateId').value = item.id;
        document.getElementById('subject').value = item.subject;
        document.getElementById('modalTitle').innerText = '编辑模版';
        
        templateModal.show();
        
        setTimeout(() => {
            initEditor();
            if (tinymce.get('content-editor')) {
                tinymce.get('content-editor').setContent(item.content);
            }
        }, 100);
    }

    async function saveTemplate() {
        // 同步 TinyMCE 数据到 textarea
        if (tinymce.get('content-editor')) {
            tinymce.triggerSave();
        }

        const form = document.getElementById('templateForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        const id = document.getElementById('templateId').value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `/templates/${id}` : '/templates/';

        // 如果是 PUT，确保 ID 不在 body 中或者后端忽略（SQLModel 通常忽略）
        if (id) delete data.id;

        try {
            await apiCall(url, method, data);
            templateModal.hide();
            loadTemplates();
            // 成功提示
            alert('保存成功');
        } catch (e) {
            // Error handled in apiCall
        }
    }

    async function deleteTemplate(id) {
        if (!confirm('确定要删除这个模版吗？')) return;
        try {
            await apiCall(`/templates/${id}`, 'DELETE');
            loadTemplates();
        } catch (e) {
            console.error(e);
        }
    }
</script>


<?php include 'includes/footer.php'; ?>
