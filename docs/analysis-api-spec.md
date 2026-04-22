# 168-gdm Analysis 页后端接口需求文档

> **交付对象**：后端同事
> **交付人**：前端团队
> **页面**：`/analysis`（登录后默认首页，`src/views/Sms/Dashboard/Analysis.vue`）
> **文档版本**：v2.0（基于前端 Analysis 页 v1.2 实现产出）
> **更新日期**：2026-04-21

---

## 一、页面功能简述

Analysis 是登录后的默认数据仪表盘，面向 B 端商户一眼看清账户健康度和营销成效。页面结构自上而下 5 个区块：

1. **顶部日期筛选器** — 今日 / 本周 / 本月 / 最近 30 天 / 自定义，默认"本月"
2. **KPI 4 卡**（响应日期筛选）—— ① 当前余额 · ② 本月花费 · ③ 已发消息总数 · ④ 活跃联系人数；每张卡底部带 30 天 mini 趋势线（sparkline）和环比增长标签
3. **趋势图 2 张**（响应日期筛选）—— 左：发送量趋势（成功 / 失败叠加折线）；右：花费趋势（总金额折线）；均支持 day / week / month 粒度切换
4. **第三排 3 个小卡**（不响应日期筛选，全局静态）—— 任务执行（运行中 / 成功 / 失败 / 总数）· 游戏参与（参与人数 / 中奖人数 / 中奖率）· 发送类型分布（SMS / MMS / Email 饼图）
5. **最近活动表** — 最近 10 条发送记录（时间 / 类型 / 内容 / 状态 / 数量 / 花费），点击跳到 `/history/sms`

---

## 二、接口总览（一张表快速看懂工作量）

| # | 接口路径 | 状态 | 优先级 | 说明 |
|---|---------|------|-------|------|
| A1 | `GET /api/user/wallet/getBalance` | 复用 | — | KPI ① 当前余额（备用兜底，首选走 B1） |
| A2 | `GET /api/user/addressbook/index` | 复用 | — | KPI ④ 联系人总数（备用兜底，首选走 B1） |
| A3 | `GET /api/sms/body/index` | 复用 + 待补字段 | — | 最近活动表；⚠️ 需后端补 `list[].type` |
| A4 | `GET /api/sms/body/monthIndex` | 复用 | — | KPI ② 本月花费（备用兜底，首选走 B1） |
| A5 | `GET /api/jobTask/index` | 复用 | — | 任务执行卡（备用兜底，首选走 B1） |
| A6 | `GET /api/game/getParticipantIndex` | 复用 | — | 游戏参与兜底（首选走 B5） |
| B1 | `GET /api/sms/analysis/overview` | 新增 | **P0** | 4 KPI + 任务状态一次返回 |
| B2 | `GET /api/sms/body/statTrend` | 新增 | **P0** | 发送量趋势（折线图） |
| B3 | `GET /api/sms/body/costTrend` | 新增 | **P0** | 花费趋势（折线图） |
| B4 | `GET /api/sms/analysis/kpiSparkline` | 新增 | P1 | KPI 卡底部 30 天迷你趋势 |
| B5 | `GET /api/game/getStat` | 新增 | P1 | 游戏参与汇总（总人数 / 中奖数 / 胜率） |
| B6 | `GET /api/sms/body/typeDistribution` | 新增 | P1 | SMS / MMS / Email 占比（饼图） |

**合计 12 个接口**：复用 6 个（其中 A3 需补 1 个字段）+ 新增 P0 级 3 个（Analysis 核心）+ 新增 P1 级 3 个（辅助，可稍后做）。

---

## 三、通用约定（沿用现有项目规范）

### 鉴权
- 方式：JWT Bearer
- 请求头：`authorization: Bearer <token>`（**小写 `authorization`**，和现有接口保持一致）
- 过期响应码：`code == 1002` 或 `code == 2015` → 前端会自动清 cookie 并跳登录

### 响应结构（强制统一）
```json
{
  "code": 200,
  "msg": "ok",
  "data": { ... }
}
```
- `code == 200` 代表成功，其他值一律视为失败（沿用现有规范）
- 错误时 `msg` 必须是可直接展示给用户的文案（前端直接 toast）
- **空数据请返回 `data.list: []` 或 0 值，禁止返回 `null` 或 HTTP 404**

### 时间字段格式
| 场景 | 格式 | 示例 |
|------|------|------|
| 入参日期（仅日期） | `YYYY-MM-DD` | `2026-04-01` |
| 入参日期时间 | `YYYY-MM-DD HH:mm:ss` | `2026-04-21 12:30:00` |
| 返回的 created_at / updated_at | `YYYY-MM-DD HH:mm:ss` | `2026-04-21 12:30:00` |
| 聚合桶标签 | day→`YYYY-MM-DD` / week→该周**周一**的 `YYYY-MM-DD`（ISO 8601）/ month→`YYYY-MM` | — |
| 时区 | 按服务器时区统计，前端展示时用 `TzManager` 转本地 | — |

### 分页约定（沿用现有 `sms/body/index` 风格）
- 入参：`page`（从 1 开始）、`limit`（每页数量）
- 返回：`{ list: [...], page, limit, total }`

### 日期筛选参数命名（Analysis 专用新约定）
| 参数 | 类型 | 说明 |
|------|------|------|
| `start_date` | string | 开始日期（**包含**当天），格式 `YYYY-MM-DD` |
| `end_date` | string | 结束日期（**包含**当天），格式 `YYYY-MM-DD` |
| `granularity` | string | 聚合粒度，枚举 `day` / `week` / `month` |

> 注：项目现有历史接口（Game/Join）用的是 `start_time` + `end_time`。Analysis 页新接口**本规范统一采用** `start_date` + `end_date`，更符合"纯日期"语义。**同一套接口内命名必须一致**。

### 金额字段约定
- 所有金额字段用**字符串**（如 `"350.60"`）保留 **2 位小数**
- 与现有 `total_price` / `sms_price` / `mms_price` / `total_money` 风格一致
- 避免 JS number 精度问题（前端用 Big.js 处理）

---

## 四、Part A：复用现有接口（仅告知，后端无需修改）

> 以下接口前端已在其他页面调用，Analysis 页只是**增加一处调用**。**后端不需要新增或修改**，仅告知 Analysis 页会额外增加这些接口的调用频次，请关注缓存与性能。
>
> **重要策略**：Analysis 页的 4 个 KPI 首选走 **B1 overview**（一次请求拿齐），Part A 的 A1/A2/A4/A5 是 B1 未上线前的兜底/备选。B1 上线后 Part A 的大部分接口仍在别的页面使用（`/history/sms`、`/contact`、`/bill` 等），不会下线。

### A1. 当前余额（备用兜底，B1 首选）
- 接口：`GET /api/user/wallet/getBalance`
- 状态：**已满足**
- 入参：无
- 前端用到的字段：`balance`
- 使用位置：`src/layout/Sms/SmsMenu.vue` 已在用；Analysis KPI ① 通过 B1 的 `data.balance` 获取

### A2. 通讯录总数（备用兜底，B1 首选）
- 接口：`GET /api/user/addressbook/index`
- 状态：**已满足**
- 入参（最小化）：`page=1&limit=1`（前端只取 `total`，不关心 list）
- 前端用到的字段：`total`
- 使用位置：`src/views/Sms/Contact/Index.vue` 已在用；Analysis KPI ④ 通过 B1 的 `data.contact_total.current` 获取

### A3. 最近活动（最近 N 条发送记录）⚠️ 需新增 `type` 字段
- 接口：`GET /api/sms/body/index`
- 状态：**需在 `list[]` 内新增 `type` 字段**（详见下文）
- 入参：
  | 参数 | 类型 | 必填 | 说明 |
  |------|------|------|------|
  | `page` | int | 是 | 固定传 1 |
  | `limit` | int | 是 | Analysis 页默认 10 |
- 前端需要的字段：
  | 字段 | 类型 | 来源 | 说明 |
  |------|------|------|------|
  | `list[].code` | string | 已有 | 消息 ID |
  | `list[].content` | string | 已有 | 消息内容（可能含 HTML） |
  | `list[].status` | int | 已有 | 0=待发/1=成功/2=发送中/3=失败/4=部分成功 |
  | `list[].total_money` | string | 已有 | 本条消耗金额（字符串，2 位小数） |
  | `list[].success_total` | int | 已有 | 成功条数 |
  | `list[].error_total` | int | 已有 | 失败条数 |
  | `list[].consume_number` | int | 已有 | 消耗的短信 / 彩信 / 邮件条数 |
  | `list[].subscribe_type` | int | 已有 | 0=立即 / 1=生日 / 2=计划（History 页在用） |
  | `list[].updated_at` | string | 已有 | `YYYY-MM-DD HH:mm:ss` |
  | `list[].created_at` | string | 已有 | `YYYY-MM-DD HH:mm:ss` |
  | **`list[].type`** | string | ⚠️ **需补** | 枚举：`sms` / `mms` / `email` |
- 使用位置：Analysis.vue 最近活动表（L204-251）；`src/views/Sms/History/Sms.vue` 已在用
- **⚠️ 新增字段说明**：
  - History 页目前只按 `subscribe_type`（立即/生日/计划）展示，没有用到消息类型（SMS/MMS/Email）
  - Analysis 页**新增**"类型"列，需要区分 SMS / MMS / Email
  - 在 `list[]` 内**新增 `type` 字段**，枚举 `sms` / `mms` / `email`（小写字符串）
  - 如果后端已有 `send_type` 数字字段且与消息形态一一映射（如 0=sms/1=mms/2=email），可直接在返回中增派生的 `type` 字段，无需新增物理列
- 备注：History 页已用 `consume_number`，Analysis 页复用同字段作为"数量"列，无需新增

### A4. 月度账单（备用兜底，B1 首选）
- 接口：`GET /api/sms/body/monthIndex`
- 状态：**已满足**
- 入参：`page=1&limit=30`
- 前端用到的字段：`list[0].total_price`（最近一个月 = 本月花费）
- 使用位置：`src/views/Sms/Bill/Outbound/InvoiceHistory.vue` 已在用；Analysis KPI ② 首选走 B1 的 `data.month_cost.current`
- 备注：B1 上线后本接口在 Analysis 页不再直接使用，但 Bill 页仍在用

### A5. 任务执行情况（备用兜底，B1 首选）
- 接口：`GET /api/jobTask/index`
- 状态：**已满足**
- 入参：`page=1&limit=30&seek=""`
- 前端用到的字段：`list[].run_status`（0=结束/1=成功/2=运行中/3=失败/4=部分成功）
- 使用位置：`src/views/Sms/Task/Plan.vue` 已在用；Analysis 第三排"任务执行"小卡首选走 B1 的 `data.task_stat`
- 备注：若 B1 的 `task_stat` 聚合查询成本高，前端可降级本地聚合

### A6. 游戏参与（兜底，B5 首选）
- 接口：`GET /api/game/getParticipantIndex`
- 状态：**已满足**
- 入参：`page=1&limit=10&start_time&end_time&to=""`
- 前端用到的字段：`total`（总参与数）
- 使用位置：`src/views/Sms/Game/Join.vue` 已在用；Analysis 第三排"游戏参与"小卡首选走 B5
- 备注：该接口入参沿用历史 `start_time`/`end_time`，不是 `start_date`/`end_date`，**后端无需为 Analysis 改动**

---

## 五、Part B：新增接口（后端需要新开）

### P0 级：必须做（Analysis 页核心功能）

---

#### B1. Analysis 仪表盘概览 【P0】

- **中文名**：Analysis 页首屏概览（4 KPI + 任务状态一次返回）
- **接口**：`GET /api/sms/analysis/overview`
- **鉴权**：JWT Bearer
- **用途**：**一次请求拿齐 4 个 KPI + 任务状态**，避免前端打 5-6 个接口

##### 请求参数
| 参数 | 类型 | 必填 | 说明 | 示例 |
|------|------|------|------|------|
| `start_date` | string | 是 | 筛选开始日期（含） | `2026-04-01` |
| `end_date` | string | 是 | 筛选结束日期（含） | `2026-04-30` |

##### 返回 `data` 结构
```json
{
  "balance": "128.50",
  "month_cost": {
    "current": "350.60",
    "prev": "320.80",
    "growth_rate": 9.3
  },
  "sent_total": {
    "current": 3240,
    "prev": 2810,
    "growth_rate": 15.3
  },
  "contact_total": {
    "current": 1280,
    "prev": 1180,
    "growth_rate": 8.5
  },
  "task_stat": {
    "running": 3,
    "success": 12,
    "failed": 1,
    "total": 16
  }
}
```

##### 字段说明
| 字段 | 类型 | 含义 |
|------|------|------|
| `balance` | string | 当前余额（实时值，**不受日期筛选影响**），2 位小数 |
| `month_cost.current` | string | 选定范围内总花费 |
| `month_cost.prev` | string | 上一个等长周期总花费 |
| `month_cost.growth_rate` | number | 环比（%），保留 1 位小数。公式 `(current - prev) / prev * 100` |
| `sent_total.current` | int | 选定范围内发送数 |
| `sent_total.prev` | int | 上一个等长周期发送数 |
| `sent_total.growth_rate` | number | 环比（%） |
| `contact_total.current` | int | 通讯录总数（实时，不受筛选影响） |
| `contact_total.prev` | int | N 天前的通讯录总数（N = 当前周期天数） |
| `contact_total.growth_rate` | number | 环比（%） |
| `task_stat.running` | int | 任务运行中数（对应 `jobTask.run_status == 2`） |
| `task_stat.success` | int | 任务成功数（`run_status == 1`） |
| `task_stat.failed` | int | 任务失败数（`run_status == 3 或 4`） |
| `task_stat.total` | int | 任务总数（含所有状态） |

##### 样例请求
```
GET /api/sms/analysis/overview?start_date=2026-04-01&end_date=2026-04-30
Authorization: Bearer <token>
```

##### 边界处理
- 新用户无历史：`month_cost.prev = "0.00"`、`growth_rate = 0`（不要返回 Infinity 或 null）
- `task_stat` 无任务：各项 = 0
- 即使某个子查询失败，也要返回默认值而不是整接口 500
- 环比周期定义：上一个**等长**周期（如筛 4 月 30 天，prev 是 3 月 30 天；如筛 2026-04-10 ~ 2026-04-20 共 11 天，prev 是 2026-03-30 ~ 2026-04-09）

##### 前端消费位置
`Analysis.vue` L431-459 `loadOverview()` 函数；对应 KPI 4 卡（L23-41）和任务执行卡（L72-110）

---

#### B2. 短信发送量趋势 【P0】

- **中文名**：短信发送量按时间聚合
- **接口**：`GET /api/sms/body/statTrend`
- **鉴权**：JWT Bearer
- **用途**：发送量趋势图（折线/柱状），响应日期筛选 + granularity 切换

##### 请求参数
| 参数 | 类型 | 必填 | 说明 | 示例 |
|------|------|------|------|------|
| `start_date` | string | 是 | 开始日期（含） | `2026-04-01` |
| `end_date` | string | 是 | 结束日期（含） | `2026-04-30` |
| `granularity` | string | 是 | `day` / `week` / `month` | `day` |
| `subscribe_type` | int | 否 | 0=立即 / 1=生日 / 2=计划，不传=全部 | — |
| `send_type` | int | 否 | 发送类型，不传=全部 | — |

##### 返回 `data` 结构
```json
{
  "list": [
    { "date": "2026-04-01", "sent_count": 120, "success_count": 115, "failed_count": 5,  "consume_number": 138 },
    { "date": "2026-04-02", "sent_count": 98,  "success_count": 95,  "failed_count": 3,  "consume_number": 110 }
  ],
  "total_sent": 218,
  "total_success": 210,
  "total_failed": 8,
  "total_consume": 248,
  "compare": {
    "prev_total_sent": 190,
    "growth_rate": 14.7
  }
}
```

##### 字段说明
| 字段 | 类型 | 含义 |
|------|------|------|
| `list[].date` | string | 聚合桶标签。day→`YYYY-MM-DD`，week→该周**周一**的 `YYYY-MM-DD`（ISO 8601），month→`YYYY-MM` |
| `list[].sent_count` | int | 该桶内"一次发送动作"的条数（`sms/body` 记录数） |
| `list[].success_count` | int | 该桶内成功数（对应 `success_total` 累加） |
| `list[].failed_count` | int | 该桶内失败数（对应 `error_total` 累加） |
| `list[].consume_number` | int | 该桶内消耗的短信条数（对应 `consume_number` 累加） |
| `total_sent` | int | 整个范围发送数（给 B1 `sent_total.current` 也可复用此计算） |
| `total_success` / `total_failed` / `total_consume` | int | 对应汇总 |
| `compare.prev_total_sent` | int | 上一个等长周期的 `total_sent` |
| `compare.growth_rate` | number | 环比（%），保留 1 位小数 |

##### 样例请求
```
GET /api/sms/body/statTrend?start_date=2026-04-01&end_date=2026-04-30&granularity=day
```

##### 边界处理
- **空桶补零**：按 `granularity` 枚举所有桶，无数据的桶也返回 `{ date, sent_count: 0, success_count: 0, failed_count: 0, consume_number: 0 }`（前端不做填充）
- 日期跨度超过 **366 天**：返回 `code: 404`、`msg: "日期范围不能超过 366 天"`
- `start_date > end_date`：返回 `code: 404`、`msg: "开始日期不能晚于结束日期"`
- 无数据：返回 `list: []` + 各 `total_* = 0` + `compare.prev_total_sent = 0` + `growth_rate = 0`，**不要 404、不要 null**

##### 前端消费位置
`Analysis.vue` L478-506 `loadSendTrend()`；渲染到发送量趋势图（L45-48）

---

#### B3. 短信花费趋势 【P0】

- **中文名**：短信花费按时间聚合
- **接口**：`GET /api/sms/body/costTrend`
- **鉴权**：JWT Bearer
- **用途**：花费趋势图（折线/柱状），响应日期筛选 + granularity 切换

##### 请求参数
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `start_date` | string | 是 | 开始日期（含） |
| `end_date` | string | 是 | 结束日期（含） |
| `granularity` | string | 是 | `day` / `week` / `month` |
| `send_type` | int | 否 | 发送类型，不传=全部 |

##### 返回 `data` 结构
```json
{
  "list": [
    { "date": "2026-04-01", "total_cost": "12.50", "sms_cost": "10.30", "mms_cost": "2.20", "email_cost": "0.00" },
    { "date": "2026-04-02", "total_cost": "9.80",  "sms_cost": "8.50",  "mms_cost": "1.30", "email_cost": "0.00" }
  ],
  "total_cost": "22.30",
  "total_sms_cost": "18.80",
  "total_mms_cost": "3.50",
  "total_email_cost": "0.00",
  "compare": {
    "prev_total_cost": "20.40",
    "growth_rate": 9.3
  }
}
```

##### 字段说明
| 字段 | 类型 | 含义 |
|------|------|------|
| `list[].date` | string | 聚合桶标签，规则同 B2 |
| `list[].total_cost` | string | 该桶总花费（**字符串，2 位小数**） |
| `list[].sms_cost` / `list[].mms_cost` / `list[].email_cost` | string | 拆分花费，风格与 A4 `sms_price`/`mms_price`/`email_price` 对齐 |
| `total_cost` / `total_sms_cost` / `total_mms_cost` / `total_email_cost` | string | 整个范围汇总 |
| `compare.prev_total_cost` | string | 上一个等长周期总花费 |
| `compare.growth_rate` | number | 环比（%） |

##### 样例请求
```
GET /api/sms/body/costTrend?start_date=2026-04-01&end_date=2026-04-30&granularity=day
```

##### 边界处理
- 同 B2（空桶补零、跨度限制、日期校验）
- **金额字段统一字符串**（避免 JS number 精度问题）

##### 前端消费位置
`Analysis.vue` L508-531 `loadCostTrend()`；渲染到花费趋势图（L49-52）

---

### P1 级：辅助功能（可稍后做，P0 上线后再补）

---

#### B4. KPI 卡 30 天迷你趋势（Sparkline）【P1】

- **中文名**：KPI 卡底部迷你趋势线数据
- **接口**：`GET /api/sms/analysis/kpiSparkline`
- **鉴权**：JWT Bearer
- **用途**：4 张 KPI 卡底部各显示一条 30 天迷你趋势线（宽度 ~100px，高度 ~30px）

##### 请求参数
| 参数 | 类型 | 必填 | 说明 | 枚举值 |
|------|------|------|------|-------|
| `type` | string | 是 | KPI 类型 | `balance` / `monthCost` / `sent` / `contacts` |

##### 返回 `data` 结构
```json
{
  "type": "balance",
  "data": [128.5, 132.1, 130.0, 145.8]
}
```

- `data` 长度固定 **30**，按时间升序（`data[0]` = 29 天前，`data[29]` = 今天）
- `balance` / `monthCost` 类型的值可以是**数字保留 2 位小数**（前端不做精度计算，只画图，number 可接受）
- `sent` / `contacts` 类型的值是整数

##### 样例请求
```
GET /api/sms/analysis/kpiSparkline?type=balance
```

##### 备注
- 如果实现成本高，P1 可**降级**为前端用 B2/B3 的日粒度数据自己切 30 天聚合（balance 和 contacts 可能拿不到历史，这种情况返回空数组 `[]` 前端会自动隐藏 sparkline）
- 4 次请求可串行或并发（前端用 `Promise.all` 并发）

##### 前端消费位置
`Analysis.vue` L461-476 `loadSparklines()`；渲染到各 KPI 卡底部

---

#### B5. 游戏参与汇总 【P1】

- **中文名**：游戏参与全局统计
- **接口**：`GET /api/game/getStat`
- **鉴权**：JWT Bearer
- **用途**：Analysis 第三排"游戏参与"小卡展示

##### 请求参数
无（返回全局累计数据，不受日期筛选影响）

##### 返回 `data` 结构
```json
{
  "total_participants": 842,
  "total_winners": 108,
  "win_rate": 12.8,
  "weekly_new": 95
}
```

##### 字段说明
| 字段 | 类型 | 含义 |
|------|------|------|
| `total_participants` | int | 累计参与人次 |
| `total_winners` | int | 累计中奖人次 |
| `win_rate` | number | 胜率百分比（保留 1 位小数），公式 `total_winners / total_participants * 100` |
| `weekly_new` | int | 最近 7 天新增参与人次（可选字段，目前前端不展示，提供便于未来扩展） |

##### 边界处理
- 无参与数据：全部返回 0、`win_rate = 0`，不要 null

##### 前端消费位置
`Analysis.vue` L546-560 `loadGameStat()`；渲染到游戏参与卡（L114-159）

---

#### B6. 发送类型分布 【P1】

- **中文名**：发送类型占比（饼图）
- **接口**：`GET /api/sms/body/typeDistribution`
- **鉴权**：JWT Bearer
- **用途**：Analysis 第三排"发送类型分布"饼图

##### 请求参数
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `start_date` | string | 否 | 不传=全部历史；传则按范围统计 |
| `end_date` | string | 否 | 同上 |

（MVP 可先不接日期，默认全部历史）

##### 返回 `data` 结构
```json
{
  "total": 8600,
  "items": [
    { "name": "sms",   "label": "SMS",   "count": 5200, "percent": 60.5 },
    { "name": "mms",   "label": "MMS",   "count": 2400, "percent": 27.9 },
    { "name": "email", "label": "Email", "count": 1000, "percent": 11.6 }
  ]
}
```

##### 字段说明
| 字段 | 类型 | 含义 |
|------|------|------|
| `total` | int | 三类总和 |
| `items[].name` | string | 类型 key，固定 `sms` / `mms` / `email` |
| `items[].label` | string | 展示名，固定 `SMS` / `MMS` / `Email` |
| `items[].count` | int | 该类型数量 |
| `items[].percent` | number | 百分比（0-100 范围，保留 1 位小数） |

##### 边界处理
- 无数据：`total = 0`、`items` 保留 3 项但 `count: 0, percent: 0`

##### 前端消费位置
`Analysis.vue` L562-572 `loadTypeDistribution()`；渲染到饼图（L162-179）

---

## 六、接口切换流程（前后端联调）

每个 Part B 接口后端实现完毕后，前端切换步骤（非常简单）：

### 例：切换 B1 `overview`
找到 `src/shared/utils/http.ts` L671-674：
```ts
// 切换前
export function getAnalysisOverview(parameter: any = {}): Promise<any> {
    // return request({ url: api.AnalysisOverview, method: 'get', data: parameter })
    return mockOverview(parameter)
}
```
改为：
```ts
// 切换后
export function getAnalysisOverview(parameter: any = {}): Promise<any> {
    return request({ url: api.AnalysisOverview, method: 'get', data: parameter })
}
```
B2 / B3 同理（`getAnalysisSendTrend` L677、`getAnalysisCostTrend` L683）。

### 切换 Part A 包装（A1 / A2 / A4）
`http.ts` L689-707 Analysis 版本包装默认走 mock，切换时把 `// return getUserBalance()` 打开、删掉 mock 那行即可。A3（最近活动）后端补完 `type` 字段后切真实接口。

### B4 / B5 / B6
目前 `api.ts` 还没注册这三个端点常量，后端接口部署完成告知路径后，前端补 `api.ts` 一行 + `http.ts` 切换 mock。

### 验证
1. 后端部署完接口 → 告知前端
2. 前端按上面步骤切真实接口 → 重启 `yarn dev` 验证
3. 有问题：前端排查或反馈后端

---

## 七、后端实现注意事项

1. **性能**：B2/B3 在 `granularity=day` + 30 天跨度时返回 30 个桶，建议按 `(user_id, start_date, end_date, granularity)` 做 5-10 分钟缓存
2. **B1 一把梭 vs 拆分**：B1 推荐一个接口返回 KPI + 任务状态，便于缓存同一个 key。如果评估聚合成本高，可拆成：
   - B1.1 `getBalance`（现有 A1）
   - B1.2 `costOverview`（花费+环比）
   - B1.3 `sentOverview`（发送+环比）
   - B1.4 `contactOverview`（联系人+环比）
   - B1.5 `taskOverview`（任务状态）
   - 前端 `Promise.all` 并发调用。但**强烈推荐合成 B1**
3. **时区**：所有聚合按**服务器时区**统计（与现有 `monthIndex` 一致），前端展示时走 `TzManager.formatLocal` 转本地
4. **空数据**：`list: []` / `total_* = 0` / `"0.00"`，禁止 `null` 或 HTTP 404
5. **金额字段**：全部字符串保留 2 位小数
6. **错误处理**：`code != 200` 前端统一 toast 弹 `msg`，请确保 `msg` 可直接展示
7. **接口命名**：建议用 `sms/body/*` 或 `sms/analysis/*` 前缀（和现有 `sms/body/index`/`showIndex`/`monthIndex` 风格对齐），**不要** RESTful `/users/:id/stats` 风格
8. **鉴权头大小写**：小写 `authorization`
9. **Swagger 文档**：新增接口请同步更新 Apipost（项目目前前后端约 50% 接口对不上，Analysis 接口务必补全）

---

## 八、验收清单（前端联调时会逐项验）

- [ ] B1/B2/B3 三个 P0 接口按文档参数调通并返回定义字段
- [ ] A3 `sms/body/index` 返回的 `list[]` 新增 `type` 字段，枚举 `sms` / `mms` / `email`
- [ ] 空数据：`list: []`、`total_* = 0`、`compare.prev_* = 0`、`growth_rate = 0`（不报错不 404 不 null）
- [ ] 日期边界：同一天（`start==end`）、跨月、跨年、跨年末（`2025-12-28 ~ 2026-01-05`）结果正确
- [ ] 金额字段类型统一字符串保留 2 位小数
- [ ] 时间字段统一 `YYYY-MM-DD` 或 `YYYY-MM-DD HH:mm:ss`
- [ ] granularity 三种值（day/week/month）返回的 `date` 桶标签符合约定
- [ ] week 粒度的桶标签是该周**周一**日期（ISO 8601）
- [ ] `compare.growth_rate` 计算正确（无历史数据时返回 0，不返回 Infinity）
- [ ] token 失效返回 `code: 1002`，前端已支持自动跳登录
- [ ] 日期跨度超限（>366 天）返回明确错误提示
- [ ] B2/B3 **按 granularity 补零**所有桶（无数据桶也返回 0）
- [ ] Swagger 文档同步更新

---

## 九、前端已拍板的规则（直接照做即可）

> 本章原为"待后端确认的 10 个问题"，现已全部由前端拍板，落地为 10 条**单向规范**。后端按此实现即可，无需提前回复对齐。若实现过程中遇到硬卡点（例如某条聚合查询成本过高无法落地），到时单独同步即可，不需要在开工前逐条对齐。

1. **✅ A3 `sms/body/index` 的 `type` 字段** — 本文档约定：`list[]` 内**新增 `type` 字段**，枚举值固定为 `sms` / `mms` / `email`（小写字符串）。
   - 实现策略：若表中已有 `send_type` 数字字段且与消息形态一一映射（如 0=sms/1=mms/2=email），接口层派生一个 `type` 字符串字段返回即可，无需新增物理列；若没有任何可映射字段，则新增 `type` 列。
   - 理由：前端不再维护数字到字符串的映射表，统一按字符串消费。

2. **✅ B1 采用一把梭聚合返回 4 KPI + 任务状态** — 前端方案：`/api/sms/analysis/overview` 一个接口同时返回 `balance` / `month_cost` / `sent_total` / `contact_total` / `task_stat` 五段数据。
   - 理由：减少请求数，前端只打一个接口首屏即渲染，体感最佳，也便于后端按同一个 cache key 缓存。
   - 例外说明：若后端评估该聚合查询成本过高无法实现，按现有 A1/A2/A4/A5 并发调用兜底即可（前端已预埋兜底逻辑），**无需提前沟通**，接口按现有约定实现就行。

3. **✅ B2/B3 空桶按 granularity 补零** — 前端方案：即使某日/某周/某月无发送，也返回 `{ date, sent_count: 0, success_count: 0, failed_count: 0, consume_number: 0 }`（B3 同理返回 `total_cost: "0.00"` 等）。
   - 理由：前端不做填充，直接把 `list` 喂给 ECharts 画图，保证时间轴连续、视觉不断档。
   - 例外说明：无。

4. **✅ 环比周期 = 上一个等长周期** — 前端方案：`compare.prev_*` 一律按"上一个等长周期"计算。
   - 举例：筛 4 月 30 天，则 prev 是 3 月 30 天；筛 `2026-04-10 ~ 2026-04-20` 共 11 天，则 prev 是 `2026-03-30 ~ 2026-04-09`。
   - 理由：和"本周/本月/最近 30 天"等筛选器的心智模型一致，不采用"去年同期"。
   - 例外说明：无。

5. **✅ `granularity=week` 的周起始日 = 周一（ISO 8601）** — 前端方案：week 桶的 `date` 字段统一返回该周**周一**的 `YYYY-MM-DD`。
   - 理由：国际标准 ISO 8601，与前端日期库默认行为一致；避免周日起始导致跨月桶归属歧义。
   - 例外说明：无。

6. **✅ 接口命名** — 本规范采用以下 6 个路径，后端按此建：
   - `GET /api/sms/analysis/overview`（B1）
   - `GET /api/sms/body/statTrend`（B2）
   - `GET /api/sms/body/costTrend`（B3）
   - `GET /api/sms/analysis/kpiSparkline`（B4）
   - `GET /api/game/getStat`（B5）
   - `GET /api/sms/body/typeDistribution`（B6）
   - 理由：与现有 `sms/body/index` / `showIndex` / `monthIndex` 前缀风格对齐，不走 RESTful `/users/:id/stats` 风格。
   - 例外说明：无。

7. **✅ 金额精度 = 字符串保留 2 位小数** — 前端方案：所有金额字段（`balance` / `*_cost` / `total_money` / `*_price` 等）一律返回 **字符串**且保留 **2 位小数**（例如 `"350.60"`、`"0.00"`）。
   - 理由：避免 JS number 精度丢失；与现有 `total_price` / `sms_price` / `mms_price` / `total_money` 风格完全一致，前端用 Big.js 做计算。
   - 例外说明：无。禁止返回 number 类型或不带小数的 `"0"`。

8. **✅ 时区按服务器时区聚合** — 前端方案：所有聚合（B1/B2/B3/B6 等）按**服务器时区**统计（和现有 `monthIndex` 一致），前端用 `TzManager.formatLocal` 自行转本地时区展示。
   - 理由：复用现有统计口径，避免多时区聚合带来的跨桶切割成本。
   - 例外说明：无。

9. **✅ 工期评估由后端给出** — 本文档不预设工期，后端实现前**评估后告知 Boss 即可**，便于排期。
   - 建议粒度：P0（B1/B2/B3 + A3 补 `type` 字段）一个工期，P1（B4/B5/B6）一个工期。
   - 理由：只保留这一条必要的工期沟通点，其他规则已全部拍板，不需要回头对齐。

10. **✅ B4 Sparkline 可降级实现** — 前端方案：如果 B4 实现成本高，允许**后端返回空数组 `[]`**，前端会自动隐藏 sparkline，不影响主功能。
    - 后端可选两种实现方式：
      - 完整版：按 `type` 返回真实 30 天数据；
      - 降级版：返回空数组 `[]`，或直接不做 B4（P0 上线后再补）；
    - 理由：sparkline 只是 KPI 卡底部装饰性趋势线，对页面核心功能无影响。
    - 例外说明：无。

---

**文档版本：v2.0，最后更新 2026-04-21。如实现中遇到硬卡点，随时找前端同步。**
