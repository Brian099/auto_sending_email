FROM php:8.2-apache

# 设置工作目录
WORKDIR /var/www/html

# 1. 安装系统依赖和 Python
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# 2. 启用 Apache 模块 (反向代理和重写)
RUN a2enmod proxy proxy_http rewrite

# 临时修复：在开发环境中让 Apache 以 root 运行以避免挂载卷的权限问题
# 注意：这在某些环境中可能导致 Apache 拒绝启动，如果遇到问题请注释掉
RUN sed -i 's/export APACHE_RUN_USER=www-data/export APACHE_RUN_USER=root/' /etc/apache2/envvars && \
    sed -i 's/export APACHE_RUN_GROUP=www-data/export APACHE_RUN_GROUP=root/' /etc/apache2/envvars

# 3. 准备后端环境
# 创建后端目录和上传目录
RUN mkdir -p /app/backend /app/uploads && \
    chmod 777 /app/uploads

# 复制依赖文件
COPY backend/requirements.txt /app/backend/

# 安装 Python 依赖
# 使用 --break-system-packages 因为是在容器中，使用 --ignore-installed 避免系统包冲突
RUN pip3 install --no-cache-dir --ignore-installed -r /app/backend/requirements.txt --break-system-packages

# 复制后端代码
COPY backend /app/backend

# 4. 准备前端环境
# 复制前端代码到 Apache 默认目录
COPY frontend /var/www/html

# 5. 配置文件
# 复制 Apache 站点配置
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf

# 复制 Supervisor 配置
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 复制启动脚本
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# 暴露端口
EXPOSE 80

# 启动 Supervisor (通过入口脚本)
CMD ["/usr/local/bin/entrypoint.sh"]
