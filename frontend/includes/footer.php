            </div><!-- /.container-fluid -->
        </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <footer class="main-footer bg-white border-top p-3 text-center text-muted small" style="margin-left: var(--sidebar-width); transition: margin-left 0.3s ease-in-out;">
        <strong>Copyright &copy; <?php echo date('Y'); ?> Email System.</strong> All rights reserved.
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle Script
        function toggleSidebar() {
            const body = document.body;
            if (window.innerWidth <= 768) {
                body.classList.toggle('sidebar-open');
                body.classList.remove('sidebar-collapse');
            } else {
                body.classList.toggle('sidebar-collapse');
            }
        }

        // Adjust footer on resize/toggle
        // Note: CSS transition handles most, but we need to match footer margin to content wrapper
        // The footer style inline above handles it with CSS var if we put it in :root or use class.
        // Actually, let's update the style block in header to handle footer margin too.
        
        // Common API Helper
        const API_BASE = '/api';

        async function apiCall(endpoint, method = 'GET', data = null, suppressError = false) {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            if (data) {
                options.body = JSON.stringify(data);
            }
            try {
                const response = await fetch(API_BASE + endpoint, options);
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.detail || 'API Request Failed');
                }
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                if (!suppressError) {
                    alert('操作失败: ' + error.message);
                }
                throw error;
            }
        }
    </script>
</body>
</html>