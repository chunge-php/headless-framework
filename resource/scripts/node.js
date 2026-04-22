// resources/scripts/export-naughty-words.js
// --------------------------------------------------
// 自动从 npm 包 naughty-words 导出 各语言敏感词库
// 导出到：resources/sensitive/*.txt
// --------------------------------------------------

const fs = require('fs');
const path = require('path');
const words = require('naughty-words');

// 项目的敏感词目录（自动创建）
const targetDir = path.join(__dirname, '..', 'sensitive');

// 你想导出的语言（可扩展）
const LANGS = ['zh', 'en', 'ja', 'ko'];

// 确保敏感词目录存在
if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir, { recursive: true });
    console.log('[init] created directory:', targetDir);
}

// 遍历语言导出 txt 文件
LANGS.forEach((lang) => {
    const list = words[lang];

    if (!Array.isArray(list)) {
        console.warn(`[warn] language "${lang}" not found in naughty-words, skip`);
        return;
    }

    // 统一处理（去空格）
    const cleaned = list
        .map((w) => String(w).trim())
        .filter((w) => w.length > 0);

    if (!cleaned.length) {
        console.warn(`[warn] language "${lang}" has no words, skip`);
        return;
    }

    const filePath = path.join(targetDir, `${lang}.txt`);
    const content = cleaned.join('\n') + '\n';

    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`[ok] exported ${cleaned.length} words → ${filePath}`);
});

console.log('\nDone! All files output to:', targetDir);
