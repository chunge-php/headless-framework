# 标准检查（模块 + 功能点 + 依赖）
php webman module:check

# 跳过功能点检查
php webman module:check --no-features

# 仅做依赖检查（跳过模块/功能点都不跳，本例没有单独开关；你可用 --no-features 即可减少噪音）
php webman module:check

# JSON 输出（给 CI）
php webman module:check --json > check-report.json

# 自定义模块目录
php webman module:check --modules-path="D:/Projects/xxx/app/modules"
