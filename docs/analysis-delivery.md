# Analysis 仪表盘接口交付文档

> **交付对象**：前端同事
> **交付人**：后端
> **文档版本**：v1.0
> **交付日期**：2026-04-22
> **对应需求文档**：`docs/analysis-api-spec.md`（v2.0）

---

## 一、落地说明

1. 本次新增的所有接口**全部放在独立模块 `app/modules/analysis/` 里**，未修改任何现有模块（`sms/body`、`game`、`user/wallet` 等一行未动）。
2. **路由前缀不沿用 `sms/analysis`、`sms/body`，统一为 `/analysis`**，7 个接口一个入口。
3. 最终 URL = `{ROUTE_PREFIX}/analysis/{endpoint}`：
   - 当前项目 `.env` 若设置 `ROUTE_PREFIX=api` → 实际 URL 是 `/api/analysis/overview`
   - 若未设 → 实际 URL 是 `/analysis/overview`
   - **前端按你们现有接口路径的 `api` 前缀规律照走即可**，和 `/api/user/wallet/getBalance` 的前缀规则完全一致。
4. A3（`sms/body/index` 加 `type` 字段）**没有修改原接口**。改为提供新接口 `GET /analysis/recentActivity`，返回结构在原基础上已包含 `type` 字段，前端直接切到新接口即可。

---

## 二、接口总览

| # | 原需求编号 | 方法 | 路径 | 优先级 |
|---|-----------|------|------|-------|
| 1 | B1 | GET | `/analysis/overview` | P0 |
| 2 | B2 | GET | `/analysis/statTrend` | P0 |
| 3 | B3 | GET | `/analysis/costTrend` | P0 |
| 4 | B4 | GET | `/analysis/kpiSparkline` | P1 |
| 5 | B5 | GET | `/analysis/gameStat` | P1 |
| 6 | B6 | GET | `/analysis/typeDistribution` | P1 |
| 7 | A3 替代 | GET | `/analysis/recentActivity` | P0 |

---

## 三、通用约定

- **鉴权**：JWT Bearer，请求头 `authorization: Bearer <token>`（小写）
- **成功响应**：`{ "code": 200, "msg": "ok", "data": {...} }`
- **失败响应**：`{ "code": <非200>, "msg": "<错误提示>", "data": {} }`
- **Token 失效**：`code == 1002`
- **日期入参**：`YYYY-MM-DD`（含 start 含 end）
- **时区**：服务器时区聚合，前端用 `TzManager.formatLocal` 转本地
- **金额字段**：**字符串**保留 **2 位小数**（如 `"350.60"`、`"0.00"`）
- **空数据**：`list: []`、数值字段 `0` 或 `"0.00"`、`growth_rate: 0`（不返回 null，不返回 Infinity）
- **参数缺失**：`code: 1001`，`msg: params_missing`
- **日期范围非法**（start > end）：`code: 404`，`msg: date_range_invalid`
- **日期跨度超 366 天**：`code: 404`，`msg: date_range_too_large`
- **granularity 非法**：`code: 1001`

---

## 四、接口详情

### 1. `GET /analysis/overview` 【P0】

仪表盘首屏概览，一次请求拿齐 4 个 KPI + 任务状态。

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `start_date` | string | 是 | 开始日期（含） |
| `end_date` | string | 是 | 结束日期（含） |

**响应 data**

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

**字段说明**

| 字段 | 类型 | 含义 |
|------|------|------|
| `balance` | string | 当前余额（**实时**，不受日期筛选影响） |
| `month_cost.current` | string | 选定范围内总花费 |
| `month_cost.prev` | string | 上一个等长周期总花费 |
| `month_cost.growth_rate` | number | 环比（%），1 位小数 |
| `sent_total.current` | int | 选定范围内发送数（`sms_batch_lots` 行数） |
| `sent_total.prev` | int | 上一个等长周期发送数 |
| `contact_total.current` | int | 通讯录总数（**实时**，不受筛选影响） |
| `contact_total.prev` | int | 当前周期天数前的通讯录总数 |
| `task_stat.running` | int | `run_status == 2` |
| `task_stat.success` | int | `run_status == 1` |
| `task_stat.failed` | int | `run_status == 3 或 4` |
| `task_stat.total` | int | 所有任务 |

**环比周期定义**：上一个等长周期。例如筛 `2026-04-01 ~ 2026-04-30`（30 天），prev 是 `2026-03-02 ~ 2026-03-31`。

**样例**

```
GET /analysis/overview?start_date=2026-04-01&end_date=2026-04-30
Authorization: Bearer <token>
```

---

### 2. `GET /analysis/statTrend` 【P0】

发送量按时间聚合。

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `start_date` | string | 是 | 开始日期（含） |
| `end_date` | string | 是 | 结束日期（含） |
| `granularity` | string | 是 | `day` / `week` / `month` |
| `subscribe_type` | int | 否 | 0=立即/1=生日/2=计划，不传或 -1=全部 |
| `send_type` | int | 否 | 0=sms/1=mms/2=email，不传或 -1=全部 |

**响应 data**

```json
{
  "list": [
    { "date": "2026-04-01", "sent_count": 120, "success_count": 115, "failed_count": 5, "consume_number": 138 },
    { "date": "2026-04-02", "sent_count": 98,  "success_count": 95,  "failed_count": 3, "consume_number": 110 }
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

**字段说明**

- `list[].date`：
  - `day` → `YYYY-MM-DD`
  - `week` → 该周**周一**的 `YYYY-MM-DD`（ISO 8601）
  - `month` → `YYYY-MM`
- 空桶已补零，时间轴连续
- `compare.prev_total_sent`：上一个等长周期发送总数

**样例**

```
GET /analysis/statTrend?start_date=2026-04-01&end_date=2026-04-30&granularity=day
```

---

### 3. `GET /analysis/costTrend` 【P0】

花费按时间聚合。

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `start_date` | string | 是 | 开始日期（含） |
| `end_date` | string | 是 | 结束日期（含） |
| `granularity` | string | 是 | `day` / `week` / `month` |
| `send_type` | int | 否 | 0=sms/1=mms/2=email，不传或 -1=全部 |

**响应 data**

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

所有金额字段：字符串，2 位小数。

**样例**

```
GET /analysis/costTrend?start_date=2026-04-01&end_date=2026-04-30&granularity=day
```

---

### 4. `GET /analysis/kpiSparkline` 【P1】

KPI 卡底部 30 天迷你趋势线。

**请求参数**

| 参数 | 类型 | 必填 | 说明 | 枚举 |
|------|------|------|------|-----|
| `type` | string | 是 | KPI 类型 | `balance` / `monthCost` / `sent` / `contacts` |

**响应 data**

```json
{
  "type": "balance",
  "data": [128.5, 132.1, 130.0, 145.8, ...]
}
```

- `data` 长度 **固定 30**，按时间升序（`data[0]` = 29 天前，`data[29]` = 今天）
- `balance` / `monthCost` 为 number（2 位小数，前端只画图，不计算）
- `sent` / `contacts` 为 int
- `balance` 取自 `wallet_topup_logs` 每日最后一次 `after_balance_cents`；无变动的日期用上一个已知余额前向填充；若 30 天内完全无充值记录，全 30 天用当前余额填充（前端 sparkline 会是平线）

**样例**

```
GET /analysis/kpiSparkline?type=balance
```

---

### 5. `GET /analysis/gameStat` 【P1】

游戏参与汇总，不受日期筛选影响。

**请求参数**：无

**响应 data**

```json
{
  "total_participants": 842,
  "total_winners": 108,
  "win_rate": 12.8,
  "weekly_new": 95
}
```

**字段说明**

| 字段 | 类型 | 含义 |
|------|------|------|
| `total_participants` | int | 累计参与人次（`sms_channel_logs` 中 `channel_type = 3` 轮盘游戏） |
| `total_winners` | int | 累计中奖人次（`game_logs.result` 非空且 ≠ "未中奖"） |
| `win_rate` | number | 胜率（%），1 位小数 |
| `weekly_new` | int | 最近 7 天新增参与人次 |

---

### 6. `GET /analysis/typeDistribution` 【P1】

发送类型占比（饼图）。

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `start_date` | string | 否 | 不传=全部历史 |
| `end_date` | string | 否 | 同上 |

**响应 data**

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

- `count` 基于 `sms_batch_lots.consume_number`（实际条数，不是批次数）
- 无数据：`total=0`、`items` 保留 3 项（`count: 0`, `percent: 0`）

---

### 7. `GET /analysis/recentActivity` 【P0，替代原 A3】

最近发送记录，**已带 `type` 字段**。前端请直接切换到此接口，不必等后端改 `sms/body/index`。

**请求参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `page` | int | 否 | 默认 1 |
| `limit` | int | 否 | 默认 10 |

> 注：分页沿用项目现有 `limit`/`offset` 中间件（和 `sms/body/index` 一致），前端传 `page`/`limit` 即可。

**响应 data**

```json
{
  "total": 153,
  "list": [
    {
      "id": 336,
      "code": "10000336",
      "content": "Hello world",
      "subject": "",
      "status": 1,
      "total_money": "0.60",
      "success_total": 10,
      "error_total": 0,
      "consume_number": 12,
      "subscribe_type": 0,
      "send_type": 0,
      "type": "sms",
      "created_at": "2026-04-21 12:30:45",
      "updated_at": "2026-04-21 12:30:45"
    }
  ]
}
```

**字段说明**

| 字段 | 类型 | 含义 |
|------|------|------|
| `list[].type` | string | **新增**。枚举 `sms` / `mms` / `email`（由 `send_type` 0/1/2 派生） |
| `list[].status` | int | 0=未开始 / 1=成功 / 2=执行中 / 3=失败 / 4=警告 |
| `list[].subscribe_type` | int | 0=立即 / 1=生日 / 2=计划 |
| `list[].total_money` | string | 本条消耗金额，字符串 2 位小数 |
| 其他字段 | — | 与 `sms/body/index` 保持一致 |

---

## 五、前端切换步骤

### B1 / B2 / B3 切换
`src/shared/utils/http.ts` 把 mock 注释掉，启用 `request()`：

```ts
// B1
export function getAnalysisOverview(parameter: any = {}): Promise<any> {
    return request({ url: '/analysis/overview', method: 'get', data: parameter })
}

// B2
export function getAnalysisSendTrend(parameter: any = {}): Promise<any> {
    return request({ url: '/analysis/statTrend', method: 'get', data: parameter })
}

// B3
export function getAnalysisCostTrend(parameter: any = {}): Promise<any> {
    return request({ url: '/analysis/costTrend', method: 'get', data: parameter })
}
```

（若项目已有自动补 `/api` 前缀逻辑，这里路径仍写 `/analysis/...` 即可，跟现有 `/user/wallet/getBalance` 写法规则一致。）

### B4 / B5 / B6
在 `api.ts` 新增 3 条常量后在 `http.ts` 切真实请求：

```ts
// api.ts
AnalysisKpiSparkline:    '/analysis/kpiSparkline',
AnalysisGameStat:        '/analysis/gameStat',
AnalysisTypeDistribution: '/analysis/typeDistribution',
```

### A3 替代
`最近活动`表把原来调 `sms/body/index` 的地方改为：

```ts
export function getRecentActivity(parameter: any = {}): Promise<any> {
    return request({ url: '/analysis/recentActivity', method: 'get', data: parameter })
}
```

`list[].type` 已带，直接消费。

---

## 六、验收清单

- [ ] 7 个接口全部连通，按文档返回字段
- [ ] `/analysis/recentActivity` 的 `list[].type` 值为 `sms` / `mms` / `email`
- [ ] 空数据：`list: []`、`total_* = 0`、`"0.00"`、`growth_rate: 0`
- [ ] 日期边界：同一天、跨月、跨年正确
- [ ] 金额字段统一字符串 2 位小数
- [ ] `granularity=week` 桶标签为该周周一（ISO 8601）
- [ ] `compare.growth_rate` 无历史时返回 0（不返回 Infinity / null）
- [ ] Token 失效返回 `code: 1002`
- [ ] 日期跨度超 366 天明确报错

---

## 七、后端已知限制与可优化点

1. **无缓存**：当前实现直接查库，未加 Redis 缓存。若 Analysis 页并发高，后续可按 `(uid, start_date, end_date, granularity)` 做 5-10 分钟缓存。
2. **kpiSparkline 的 balance 降级策略**：依赖 `wallet_topup_logs`，若该用户从未充值，会用当前余额填充 30 天（sparkline 呈平线）。完整的每日余额快照需要额外建表，当前版本未做。
3. **无新建数据表**：全部接口基于现有表聚合（`sms_batch_lots`、`sms_channel_logs`、`game_logs`、`address_books`、`wallets`、`wallet_topup_logs`、`job_definitions`）。
4. **任务状态字段映射**：按需求文档 v2.0 的约定（`run_status` 1=成功/2=运行中/3=失败/4=部分成功）。若现网数据语义不一致，需同步对齐。

---

**如有疑问或联调问题，直接找后端。**
