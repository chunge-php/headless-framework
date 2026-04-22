/*
 Navicat Premium Data Transfer

 Source Server         : 本地
 Source Server Type    : MySQL
 Source Server Version : 80012
 Source Host           : localhost:3306
 Source Schema         : headless

 Target Server Type    : MySQL
 Target Server Version : 80012
 File Encoding         : 65001

 Date: 22/04/2026 10:12:58
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for address_books
-- ----------------------------
DROP TABLE IF EXISTS `address_books`;
CREATE TABLE `address_books`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `last_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '姓',
  `first_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '邮箱',
  `birthday` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '生日',
  `month_day` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '月日',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `address_books_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 20812 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '通讯录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for api_clients
-- ----------------------------
DROP TABLE IF EXISTS `api_clients`;
CREATE TABLE `api_clients`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `access_key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'accessKey',
  `secret_key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'secretKey',
  `status` int(11) NULL DEFAULT 1 COMMENT '状态0禁用1启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `access_key`(`access_key` ASC, `secret_key` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `api_clients_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '客户端key值管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for auto_topup_rules
-- ----------------------------
DROP TABLE IF EXISTS `auto_topup_rules`;
CREATE TABLE `auto_topup_rules`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `wallets_id` bigint(20) NULL DEFAULT NULL COMMENT '钱包Id',
  `enabled` int(11) NULL DEFAULT NULL COMMENT '状态1启用 0停用',
  `threshold_cents` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '低于此余额触发',
  `topup_amount_cents` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '固定充值金额',
  `reminder_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '低于N元发送提醒邮件',
  `reminder_status` int(11) NULL DEFAULT 0 COMMENT '提醒状态0未提醒1已提醒',
  `cooldown_sec` int(11) NULL DEFAULT 180 COMMENT '冷却时间单位毫秒',
  `today_count` int(11) NULL DEFAULT 0 COMMENT '今日已触发数',
  `last_run_at` timestamp NULL DEFAULT NULL COMMENT '上次触发时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC, `wallets_id` ASC) USING BTREE,
  INDEX `wallets_id`(`wallets_id` ASC) USING BTREE,
  CONSTRAINT `auto_topup_rules_ibfk_1` FOREIGN KEY (`wallets_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `auto_topup_rules_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '自动充值规则' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for batch_logs
-- ----------------------------
DROP TABLE IF EXISTS `batch_logs`;
CREATE TABLE `batch_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `scope_id` bigint(20) NULL DEFAULT NULL COMMENT '作用域id',
  `scope` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '应用领域',
  `status` int(11) NULL DEFAULT 0 COMMENT '状态0未执行1完成2执行中3失败4部分成功',
  `total` int(11) NULL DEFAULT 0 COMMENT '总数',
  `success` int(11) NULL DEFAULT 0 COMMENT '成功数',
  `fail` int(11) NULL DEFAULT 0 COMMENT '失败数',
  `describe` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '错误描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '任务开始时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '任务结束时间',
  `duration` int(11) NULL DEFAULT 0 COMMENT '任务耗时（秒）',
  `exists` int(11) NULL DEFAULT 0 COMMENT '已存在的记录数',
  `invalid_phones` json NULL COMMENT '无效手机号明细（JSON格式）',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `batch_logs_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 485 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '批次任务记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for bill_seqs
-- ----------------------------
DROP TABLE IF EXISTS `bill_seqs`;
CREATE TABLE `bill_seqs`  (
  `biz` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '业务标识：BILL/ORDER等',
  `ymd` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'YYYYMMDD（按 America/New_York 计算）',
  `val` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日已使用的序号（原子自增）',
  `updated_at` datetime NULL DEFAULT NULL COMMENT '更新时间（应用层维护）',
  `created_at` datetime NULL DEFAULT NULL COMMENT '创建时间（可在首次插入时填）',
  PRIMARY KEY (`biz`, `ymd`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '账单编号生成记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for bills
-- ----------------------------
DROP TABLE IF EXISTS `bills`;
CREATE TABLE `bills`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '编号',
  `batch_id` bigint(20) NULL DEFAULT NULL COMMENT '短信批次id/充值批次id(2个不同表)',
  `total_money` decimal(10, 2) NULL DEFAULT NULL COMMENT '产生的总金额',
  `balance` decimal(10, 2) NULL DEFAULT NULL COMMENT '当前余额',
  `send_type` int(11) NULL DEFAULT 0 COMMENT '发送类型0普通短信1彩信2邮件',
  `bill_type` int(11) NULL DEFAULT 0 COMMENT '账单类型0消费1充值',
  `years` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '年',
  `months` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '月',
  `days` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '日',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC, `uid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 284 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '充值和消费账单记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for cards
-- ----------------------------
DROP TABLE IF EXISTS `cards`;
CREATE TABLE `cards`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '用户全称',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `acctid` int(11) NULL DEFAULT NULL COMMENT 'acctid',
  `profileid` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '用户profileid',
  `accttype` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '信用卡类型',
  `expiry` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '到期日期',
  `token` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'token',
  `address` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '用户地址',
  `line2` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'line2',
  `city` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '用户城市',
  `region` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'NY' COMMENT '地区',
  `country` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'US' COMMENT '国家',
  `postal` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '邮编',
  `company` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '公司名称',
  `later_number` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '卡号后4位',
  `cvv` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'cvv',
  `status` int(11) NULL DEFAULT 1 COMMENT '状态0禁用1正常',
  `default_state` int(11) NULL DEFAULT 0 COMMENT '默认扣款0否1是',
  `response_data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '响应结果',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `token`(`token` ASC, `uid` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '信用卡管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for code_logs
-- ----------------------------
DROP TABLE IF EXISTS `code_logs`;
CREATE TABLE `code_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '账号',
  `code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '验证码',
  `auto_type` int(11) NULL DEFAULT 1 COMMENT '0邮箱1手机',
  `msg` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '发送结果',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '短信验证码发送记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for config_items
-- ----------------------------
DROP TABLE IF EXISTS `config_items`;
CREATE TABLE `config_items`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `signs` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '标识',
  `values` json NULL COMMENT '值',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '单一配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for game_lists
-- ----------------------------
DROP TABLE IF EXISTS `game_lists`;
CREATE TABLE `game_lists`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `options` json NULL COMMENT '前端自定义配置',
  `type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '类型前端自定义',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '游戏配置' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for game_logs
-- ----------------------------
DROP TABLE IF EXISTS `game_logs`;
CREATE TABLE `game_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `to` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '手机号',
  `type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '类型前端自定义',
  `body` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '短信发送内容',
  `result` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '奖品',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `game_logs_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '中奖记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for game_users
-- ----------------------------
DROP TABLE IF EXISTS `game_users`;
CREATE TABLE `game_users`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '游戏编号',
  `type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '类型前端自定义',
  `body` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '短信发送内容',
  `options` json NULL COMMENT '前端自定义配置',
  `pre` json NULL COMMENT '中奖率和奖品配置',
  `switch` json NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `game_users_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户选择的游戏' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for group_users
-- ----------------------------
DROP TABLE IF EXISTS `group_users`;
CREATE TABLE `group_users`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `total` bigint(20) NULL DEFAULT NULL COMMENT '总人数',
  `file_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '文件地址',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `channel_type` int(10) NOT NULL DEFAULT 0 COMMENT '来源渠道0平台1QuickPay2餐饮3轮盘游戏',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC, `name` ASC) USING BTREE,
  CONSTRAINT `group_users_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '分组用户管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for job_definitions
-- ----------------------------
DROP TABLE IF EXISTS `job_definitions`;
CREATE TABLE `job_definitions`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `kind` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'birthday/appointment',
  `price_id` bigint(20) NULL DEFAULT NULL COMMENT '短信价格id',
  `status` int(11) NULL DEFAULT 0 COMMENT '状态0禁用1启用',
  `run_status` int(11) NULL DEFAULT 0 COMMENT '状态0未开始1运行中2待执行',
  `run_total` int(11) NULL DEFAULT 0 COMMENT '执行总数',
  `meta_json` json NULL COMMENT '扩展字段',
  `next_days` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '下次执行日期',
  `next_time` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '下次执行时间',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '发送内容',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `job_definitions_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '任务定义' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for legals
-- ----------------------------
DROP TABLE IF EXISTS `legals`;
CREATE TABLE `legals`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `slug` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '唯一标识用户协议user-agreement,隐私政策privacy-policy',
  `locale` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'en-US' COMMENT '多语言',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '内容',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `slug`(`slug` ASC, `locale` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户隐私协议或其大文本存储表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for lessee_logs
-- ----------------------------
DROP TABLE IF EXISTS `lessee_logs`;
CREATE TABLE `lessee_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '租户名称第三方提供',
  `code` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '租户编号第三方提供',
  `sms_batch_lots_id` bigint(20) NULL DEFAULT NULL COMMENT '批次id',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sms_batch_lots_id`(`sms_batch_lots_id` ASC) USING BTREE,
  CONSTRAINT `lessee_logs_ibfk_1` FOREIGN KEY (`sms_batch_lots_id`) REFERENCES `sms_batch_lots` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '第三方租户信息' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for message_logs
-- ----------------------------
DROP TABLE IF EXISTS `message_logs`;
CREATE TABLE `message_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `sms_batch_lots_id` bigint(20) NULL DEFAULT NULL COMMENT '批次id',
  `account_number` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '账号',
  `type` int(11) NULL DEFAULT 0 COMMENT '类型0sms1彩信2邮箱',
  `status` int(11) NULL DEFAULT 0 COMMENT '执行状态0未开始1成功2执行中3失败4重新发送',
  `total_money` decimal(10, 2) NULL DEFAULT NULL COMMENT '产生的总金额',
  `consume_number` int(11) NULL DEFAULT 0 COMMENT '消费总条数',
  `unusual_msg` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '异常结果',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sms_batch_lots_id`(`sms_batch_lots_id` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `message_logs_ibfk_1` FOREIGN KEY (`sms_batch_lots_id`) REFERENCES `sms_batch_lots` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `message_logs_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 287 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '短信发送记录' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for phinxlogs
-- ----------------------------
DROP TABLE IF EXISTS `phinxlogs`;
CREATE TABLE `phinxlogs`  (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for sms_batch_lots
-- ----------------------------
DROP TABLE IF EXISTS `sms_batch_lots`;
CREATE TABLE `sms_batch_lots`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '编号',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `scope_id` bigint(20) NULL DEFAULT NULL COMMENT '来源id比如定时任务的id',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '单价元',
  `price_id` bigint(20) NULL DEFAULT NULL COMMENT '价格id',
  `total_money` decimal(10, 2) NULL DEFAULT NULL COMMENT '产生的总金额',
  `send_type` int(11) NULL DEFAULT 0 COMMENT '发送类型0普通短信1彩信2邮件',
  `subscribe_type` int(11) NULL DEFAULT 0 COMMENT '类型0立即发送1生日任务2计划任务',
  `status` int(11) NULL DEFAULT 0 COMMENT '执行状态0未开始1成功2执行中3失败4警告',
  `mms_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '彩信图片地址',
  `years` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '年',
  `months` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '月',
  `days` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '日',
  `consume_number` int(11) NULL DEFAULT 0 COMMENT '消费总条数',
  `executed_count` int(11) NULL DEFAULT 0 COMMENT '执行总次数',
  `success_total` int(11) NULL DEFAULT 0 COMMENT '成功总数',
  `error_total` int(11) NULL DEFAULT 0 COMMENT '失败总数',
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '邮件标题',
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '发送内容',
  `unusual_msg` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '异常结果',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC, `uid` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `sms_batch_lots_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 337 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '短信发送记录批次' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for sms_batch_sourcs
-- ----------------------------
DROP TABLE IF EXISTS `sms_batch_sourcs`;
CREATE TABLE `sms_batch_sourcs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `account_number` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '账号',
  `source_id` bigint(20) NULL DEFAULT NULL COMMENT '溯源id',
  `source_type` int(11) NULL DEFAULT NULL COMMENT '来源类型0无1联系人标签id 2用户分组id',
  `sms_batch_lots_id` bigint(20) NULL DEFAULT NULL COMMENT '批次id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sms_batch_lots_id`(`sms_batch_lots_id` ASC) USING BTREE,
  CONSTRAINT `sms_batch_sourcs_ibfk_1` FOREIGN KEY (`sms_batch_lots_id`) REFERENCES `sms_batch_lots` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 344 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '联系人来源' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for sms_bills
-- ----------------------------
DROP TABLE IF EXISTS `sms_bills`;
CREATE TABLE `sms_bills`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '发票编号',
  `years` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '年',
  `months` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '月',
  `total_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '总金额',
  `sms_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '短信总金额',
  `mms_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '彩信总金额',
  `email_price` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '邮件总金额',
  `total_sms` int(11) NULL DEFAULT 0 COMMENT '短信总条数',
  `total_mms` int(11) NULL DEFAULT 0 COMMENT '彩信总条数',
  `total_email` int(11) NULL DEFAULT 0 COMMENT '邮件总条数',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `years`(`years` ASC, `months` ASC, `uid` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `sms_bills_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '短信月统计' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for sms_channel_logs
-- ----------------------------
DROP TABLE IF EXISTS `sms_channel_logs`;
CREATE TABLE `sms_channel_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `to` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '手机号',
  `channel_type` int(11) NULL DEFAULT 1 COMMENT '来源渠道0平台1QuickPay2餐饮3轮盘游戏',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `uid` bigint(20) NOT NULL DEFAULT 0 COMMENT '用户id',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `to`(`to` ASC, `channel_type` ASC, `uid` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '渠道来源发送记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for sms_prices
-- ----------------------------
DROP TABLE IF EXISTS `sms_prices`;
CREATE TABLE `sms_prices`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `price` decimal(10, 2) NULL DEFAULT NULL COMMENT '价格',
  `type` int(11) NULL DEFAULT 1 COMMENT '类型0普通短信1彩信2邮件',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '短信价格配置' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for tag_books
-- ----------------------------
DROP TABLE IF EXISTS `tag_books`;
CREATE TABLE `tag_books`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tags_id` bigint(20) NULL DEFAULT NULL COMMENT '标签id',
  `target_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '目标所属类型数据库表名称',
  `target_id` bigint(20) NULL DEFAULT NULL COMMENT '所属表id',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `tags_id`(`tags_id` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `tag_books_ibfk_1` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `tag_books_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 7721 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '绑定标签' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for tags
-- ----------------------------
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标签名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '标签描述',
  `colour` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '颜色',
  `target_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '目标所属类型数据库表名称',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC, `name` ASC, `target_type` ASC) USING BTREE,
  CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '标签管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for templats
-- ----------------------------
DROP TABLE IF EXISTS `templats`;
CREATE TABLE `templats`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名称',
  `title` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '主题名称',
  `type` int(11) NULL DEFAULT 0 COMMENT '类型0普通短信1彩信2邮箱',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '内容',
  `status` int(11) NULL DEFAULT 1 COMMENT '状态0禁用1启用',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `use_total` int(11) NULL DEFAULT 0 COMMENT '使用次数',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC, `name` ASC, `type` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '模板管理' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for user_credentials
-- ----------------------------
DROP TABLE IF EXISTS `user_credentials`;
CREATE TABLE `user_credentials`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `identity_id` bigint(20) NULL DEFAULT NULL COMMENT '用户身份id',
  `secret_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '加密后的密码',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `identity_id`(`identity_id` ASC) USING BTREE,
  CONSTRAINT `user_credentials_ibfk_1` FOREIGN KEY (`identity_id`) REFERENCES `user_identities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 46 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '凭证：密码' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for user_datas
-- ----------------------------
DROP TABLE IF EXISTS `user_datas`;
CREATE TABLE `user_datas`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '公司名称',
  `last_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '姓',
  `first_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名',
  `country` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '国家',
  `address1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '地址1',
  `address2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '地址2',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '城市',
  `state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '州',
  `zip_code` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '邮编',
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `user_datas_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户资料' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for user_identities
-- ----------------------------
DROP TABLE IF EXISTS `user_identities`;
CREATE TABLE `user_identities`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `provider` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '账号类型:username,email,google,sms',
  `account_number` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '账号 用户名/邮箱/Google sub/手机号等',
  `user_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'user' COMMENT '身份类型:admin,user',
  `status` int(11) NULL DEFAULT 1 COMMENT '账号状态0停用1启用',
  `meta_json` json NULL COMMENT '可存第三方头像、昵称、unionid 等',
  `verified_at` timestamp NULL DEFAULT NULL COMMENT '验证通过时间',
  `linked_at` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `account_number`(`account_number` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `user_identities_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 46 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户身份映射' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '编号',
  `last_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '姓',
  `first_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '名',
  `phone` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '手机号',
  `picture` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '头像',
  `state` int(11) NULL DEFAULT 1 COMMENT '用户状态0禁用1正常',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT '删除时间',
  `extend_json` json NULL COMMENT '扩展信息json',
  `tables` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '店铺唯一标识',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 61 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户基础表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for wallet_topup_logs
-- ----------------------------
DROP TABLE IF EXISTS `wallet_topup_logs`;
CREATE TABLE `wallet_topup_logs`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '编号',
  `years` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '年',
  `months` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '月',
  `days` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '日',
  `cards_id` bigint(20) NULL DEFAULT NULL COMMENT '信用卡扣款id',
  `amount_cents` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '本次充值金额',
  `before_balance_cents` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '之前余额',
  `after_balance_cents` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '之后余额',
  `status` int(11) NULL DEFAULT NULL COMMENT '状态0无效1成功2失败',
  `response_data` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '响应结果',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`code` ASC, `uid` ASC) USING BTREE,
  INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `wallet_topup_logs_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '充值流水' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for wallets
-- ----------------------------
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `balance` decimal(10, 2) NULL DEFAULT 0.00 COMMENT '余额单位元',
  `account_sid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '钱包账号id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid`(`uid` ASC) USING BTREE,
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 59 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '钱包管理' ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
