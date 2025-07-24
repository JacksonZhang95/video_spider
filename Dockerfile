# --- STAGE 1: Composer & Dependencies ---
# 使用官方的 Composer 镜像作为构建阶段的基础
FROM composer:2 AS vendor

# 在容器内设置工作目录
WORKDIR /app

# 复制 composer 配置文件到工作目录。
# Docker 在构建时，上下文是 video_spider 目录，所以能直接找到 composer.json
COPY composer.json .

# 安装生产环境的依赖项
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts


# --- STAGE 2: Final Application Image ---
# 使用官方的 PHP-CLI 镜像作为最终的运行环境
FROM php:8.2-cli

# 在最终镜像中设置工作目录
WORKDIR /app

# 从 'vendor' 构建阶段复制已安装的依赖项到当前工作目录
COPY --from=vendor /app/vendor/ ./vendor/

# 将项目的所有源代码（从构建上下文的根目录）复制到当前工作目录
# 这里的第一个 '.' 指的是构建上下文的根目录 (即 video_spider/ 目录下的所有内容)
# 第二个 '.' 指的是容器内的 WORKDIR (即 /app)
COPY . .

# 暴露端口 8000，与您启动命令中的端口一致
EXPOSE 8000

# 容器启动时执行的默认命令。
# 该命令在 WORKDIR (/app) 中执行，能够正确找到 public 目录
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
