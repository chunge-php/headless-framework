app/
  command/
    InstallData.php
    CreateModel.php
  core/
    Foundation/
      ModuleRegistry.php
      RouterAssembler.php
      FeatureRegistry.php         # 可选
      FeatureLoader.php           # 可选
  modules/
    user/
      manifest.hf.json
      routes/api.php
      http/Controllers/UserController.php
      http/Services/UserService.php
      features/registry.php       # 可选
      migration/20250821100000__user__create_users_table.php
    address/
      manifest.hf.json
      routes/api.php
      http/Controllers/AddressController.php
      migration/20250821101000__address__create_addresses_table.php
  database/
    migrations/                   # 可选：项目级迁移
    stub/
      model.stub                  # Phinx 迁移模板（make:m 使用）
config/
  route.php
phinx.php
support/
  myclass/ConsoleOutputStyles.php
resource/
  stubs/ModelDemo.stub           # 模型类模板（make:m 使用）



你贴的这个就是 模块的 manifest.hf.json 配置文件，每个字段都代表了模块的元信息或依赖关系。
我来逐一解释：

{
  "name": "user",
  "version": "0.1.0",
  "display": "用户中心",
  "requires": {
    "php": ">=8.0",
    "webman": ">=1.5",
    "modules": {
      "address": "^0.1.0" // 模块依赖
    },
    "features": {
      "user.exists": "检查用户是否存在",// 键为能力点名称，值为能力点描述
    }
  },
  "provides": { 这个模块对外提供的能力点
    "features": {
      "user.exists": "检查用户是否存在", // 键为能力点名称，值为能力点描述
      "user.list": "列出用户"
    }
  }
}

字段解释

name

模块的唯一标识（英文短名）。

用来在依赖里引用，比如其他模块 "requires": { "modules": { "user": "^0.1.0" } }。

version

当前模块的版本号，语义化版本（major.minor.patch）。

用于模块依赖时判断版本是否符合要求。

display

模块的人类可读名称（中文、英文都可以）。

用于展示，非逻辑用途。

requires（声明依赖）

php

指定运行环境需要的 PHP 版本。

比如 ">=8.0" 表示必须是 PHP8.0 及以上。

webman

指定 webman 框架最低版本要求。

比如 ">=1.5" 表示至少 webman 1.5。

modules

声明依赖的其他模块（名字 -> 版本约束）。

例子：

"modules": {
  "address": "^0.1.0"
}


表示此模块依赖 address 模块，版本需符合 ^0.1.0。

features

声明依赖的功能点（feature 名称 -> 约束）。

例子：

"features": {
  "user.exists": "*"
}


表示此模块需要功能点 user.exists 存在，版本/实现不限（*）。

provides（提供能力）

features

列出此模块对外暴露的能力点（功能点）。

key：功能点的唯一名称

value：描述文字（给人看的，帮助理解用途）

在这个例子里：

"user.exists": "检查用户是否存在" → 提供一个功能点，外部可以调用 FeatureRegistry::call('user.exists', $userId) 来判断某个用户是否存在。

"user.list": "列出用户" → 提供一个功能点，返回用户列表。

总结

requires：声明“我需要什么”。

provides：声明“我能提供什么”。

modules：依赖模块级别。

features：依赖/提供能力点级别。

这样做的好处是：

在项目启动时，就能校验依赖是否完整，避免运行时报错。

模块之间通过 feature 能力点 解耦（只依赖约定的接口，不强依赖类路径）。


1. ModuleRegistry.php

👉 模块注册表

负责扫描 app/modules/* 目录，找到每个模块的 manifest.hf.json 描述文件。

把它们装配成统一的数据结构（id、路径、manifest 信息）。

在需要时，还可以做 依赖解析（比如 A 模块依赖 B，就先加载 B）。

🔧 为什么需要？
模块化系统必须先有一个“中央目录”，告诉框架有哪些模块、它们的依赖和元信息。否则路由加载、迁移扫描、功能注册都没法做。

2. RouterAssembler.php

👉 路由装配器

遍历所有模块，看它们有没有 routes/*.php。

根据需要给这些路由统一加前缀（例如 ROUTE_PREFIX=study → 所有模块的接口都挂在 /study 下）。

然后 require 这些路由文件，挂载到 Webman 的路由系统。

🔧 为什么需要？
这样每个模块可以独立维护自己的 routes/api.php，
而 RouterAssembler 负责“一键拼装”，保证路由自动加载，不用你在 config/route.php 里手工一个个写。

3. FeatureRegistry.php（可选）

👉 功能点注册表

提供一个全局的 FeatureRegistry::register('user.exists', callable)。

其他模块就可以 FeatureRegistry::call('user.exists', $id) 来用，而不用直接依赖某个类。

🔧 为什么需要？
这就是“解耦”的关键：

没有它时：Address 模块必须写死 use app\modules\user\http\Services\UserService。

有了它：只需调用 FeatureRegistry::call('user.exists')，不关心这个能力由哪个模块提供。

这样一来，模块之间只依赖“能力”而不是“实现类”，模块更可插拔。

4. FeatureLoader.php（可选）

👉 功能点加载器

遍历每个模块，看有没有 features/registry.php。

如果有，就 include 它，并把里面声明的功能点批量注册进 FeatureRegistry。

示例（user 模块的 features/registry.php）：

<?php
use app\modules\user\http\Services\UserService;

return [
  'user.exists' => UserService::class.'@exists',
  'user.list'   => UserService::class.'@all',
];


🔧 为什么需要？
如果没有 FeatureLoader，你得在 config/route.php 或 bootstrap 里手动写一堆 FeatureRegistry::register()，很麻烦。
有了 FeatureLoader，模块只要带一个 registry 文件，加载时自动注册，模块就即插即用。

总结类比

可以把这 4 个类看成一个“小框架内核”：

ModuleRegistry：像机场的“航班清单”，告诉系统有哪些飞机（模块）。

RouterAssembler：像“航站楼”，根据清单把每个航班的登机口（路由）安排好。

FeatureRegistry：像“航空管制中心”，定义通用的飞行能力（起飞、降落），谁来实现不重要。

FeatureLoader：像“自动报备系统”，航班一来就自动把它的能力报上去，不用人工录入。

要点：

如果你只是自己玩，不需要太多解耦，可以只保留 ModuleRegistry + RouterAssembler。

如果你想让模块真正解耦、可共享，才会用 FeatureRegistry + FeatureLoader。



顶层配置
phinx.php

作用：Phinx 配置文件，自动扫描迁移目录。

扫描路径：

app/modules/*/migration

app/database/migrations（可选）

读取数据库连接自环境变量 DB_HOST/DB_DATABASE/DB_USER_NAME/DB_PASSWORD/DB_PORT/DB_DRIVER。

可通过 PHINX_ENV=development|production 切换环境。

templates.file 指向 app/database/stub/model.stub，用于 make:m 生成迁移的模板。

config/route.php

作用：全局路由装配入口。

主要流程：

通过 ModuleRegistry 扫描 app/modules 获取模块列表。

（可选）FeatureLoader::mount() 自动注册模块能力点。

RouterAssembler 加载各模块的 routes/*.php，可统一添加前缀（ROUTE_PREFIX 环境变量）。

核心（Core/Foundation）
ModuleRegistry.php

作用：模块注册表。

扫描 app/modules/*/manifest.hf.json，收集模块 id/path/manifest。

resolved() 返回模块列表（默认按名称排序；如需严格依赖可扩展为拓扑排序）。

RouterAssembler.php

作用：路由装配器。

遍历模块，查找 routes/*.php；

若设置 ROUTE_PREFIX=xxx，自动将所有模块路由挂在 /xxx 前缀下；否则直接加载。

让每个模块维护自己的路由文件，实现即插即用。

FeatureRegistry.php（可选）

作用：功能点注册表（能力中心）。

提供 register(name, callable) 与 call(name, ...args)。

让模块之间以“能力名”交互，避免直接依赖具体类（解耦）。

FeatureLoader.php（可选）

作用：功能点自动发现与注册。

遍历模块的 features/registry.php，读取数组并批量注册到 FeatureRegistry。

约定：features/registry.php 返回 ['feature.name' => callable|Class@method]。

注：如果你的项目不需要模块间解耦的“能力点”机制，可不使用 FeatureRegistry 与 FeatureLoader。

全局命令（Console）
app/command/InstallData.php

作用：数据库初始化命令（全局）。

用法：

php bin/console install:data migrate：执行迁移（自动扫描所有模块迁移目录）。

php bin/console install:data all：删库→建库→迁移。

php bin/console install:data exec：打印数据库连接信息。

细节：

读取 phinx.php 并通过 PhinxApplication 调用 migrate。

可选安全：环境变量 FUN_PASSWORD_MD5 存在时先校验口令。

app/command/CreateModel.php

作用：生成“模型类 + 迁移文件”。

用法：php bin/console make:m User "用户表"

流程：

根据 resource/stubs/ModelDemo.stub 生成模型类文件到 app/model（或你指定子目录）。

调用 Phinx 的 create 生成迁移文件（模板来自 app/database/stub/model.stub）。

对迁移文件进行占位符二次替换（如 DummyTableName、DummyDescribe、DummyClass）。

模块示例（Modules）
共同约定

每个模块位于 app/modules/<module_id>。

建议包含：

manifest.hf.json：模块元信息（名称、版本、依赖、提供的能力等）。

routes/*.php：模块路由定义（示例：routes/api.php）。

http/Controllers/*：控制器。

http/Services/*：服务/业务逻辑。

migration/*：Phinx 迁移文件（自动被扫描执行）。

features/registry.php（可选）：本模块对外暴露的能力点表。

app/modules/user/*
manifest.hf.json

作用：声明模块元信息与（可选）对外提供的能力点。

示例字段：

name/version/display：基础信息。

requires：依赖（php、webman、其它模块/特性）。

provides.features（可选）：对外能力声明（如 user.exists、user.list）。

routes/api.php

作用：本模块的路由定义。
例：GET /api/user/index。

http/Controllers/UserController.php

作用：处理请求，返回响应。

可直接调用本模块 Services，或通过 FeatureRegistry::call() 暴露/复用能力。

http/Services/UserService.php

作用：业务逻辑（示例中用数组模拟，实际应接入数据库）。

features/registry.php（可选）

作用：能力声明表。

返回 ['user.exists' => UserService::class.'@exists', ...]，由 FeatureLoader 自动注册。

migration/*.php

作用：模块数据库迁移。

命名建议：YYYYMMDDHHMMSS__module__action.php（便于排序与回溯）。

app/modules/address/*

结构与 user 模块类似。

若需要依赖 user，可在自己的 manifest.hf.json 中的 requires.modules.user 声明版本要求（例如 ^0.1.0），并在代码里调用 UserService::exists() 或 FeatureRegistry::call('user.exists', $id)。

项目级迁移与模板
app/database/migrations/（可选）

作用：放项目级（非模块级）的迁移文件。

也会被 phinx.php 自动扫描执行。

app/database/stub/model.stub

作用：Phinx create 的模板文件。

由 CreateModel 命令使用，常见占位符：DummyTableName、DummyClass、DummyDescribe。

模型模板
resource/stubs/ModelDemo.stub

作用：CreateModel 生成模型类时使用的代码模板。

常见占位符：DummyNamespace、DummyClass、DummyDescribe。

控制台输出样式
support/myclass/ConsoleOutputStyles.php

作用：统一控制台输出风格（info/error/comment/question）。

被命令类调用，提升 CLI 交互体验。

环境变量速查

路由前缀：ROUTE_PREFIX（如设为 study → /study/api/...）

数据库：DB_DRIVER、DB_HOST、DB_PORT、DB_DATABASE、DB_USER_NAME、DB_PASSWORD

迁移环境：PHINX_ENV=development|production|testing

迁移日志表前缀：db_prefix（影响 phinxlogs 表名）

初始化命令口令：FUN_PASSWORD_MD5（可选）


