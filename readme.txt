=== Xish AI SEO Meta ===
Contributors: xish
Tags: seo, ai, meta, openai, gpt
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

一键调用大语言模型为单篇文章生成 SEO 标题、描述、焦点关键词与社交分享文案。

== Description ==

Xish AI SEO Meta 帮你用 AI 快速写出更利于搜索排名的 SEO 元数据。在文章/页面编辑侧栏点击「用 AI 生成」，即可基于标题与正文产出：

= 免费版功能 =

* SEO 标题（建议不超过 60 字符）
* SEO 描述（建议不超过 160 字符）
* 焦点关键词（自动提炼 5 个以内）
* 社交分享文案

免费版使用你自己的 OpenAI 兼容 API Key，开箱即可完整使用单篇生成，无需升级。

= 升级到 Pro =

Pro 版解锁：批量生成、中英文多语言、历史文章回填、一键写入 Rank Math / Yoast。详情见插件设置页的「升级到 Pro」链接，或访问官网 https://xish139251.github.io/xish-ai-seo-meta-landing/。

== Installation ==

1. 上传插件文件到 `/wp-content/plugins/xish-ai-seo-meta` 目录，或直接通过后台安装。
2. 在「插件」中激活 Xish AI SEO Meta。
3. 进入「设置 → AI SEO Meta」，填写你的 API Key、端点与模型名称。
4. 编辑任意文章/页面，在右侧边栏点击「用 AI 生成」。

== Frequently Asked Questions ==

= 免费版需要付费吗？ =

不需要。免费版使用你自己的 API Key，仅向你配置的端点发起请求。

= 支持哪些模型？ =

任何兼容 OpenAI Chat Completions 接口的模型均可，默认 gpt-4o-mini。

= API Key 安全吗？ =

密钥仅存储于你自己的站点数据库，插件只会把它发送到你在设置中填写的端点。

== Screenshots ==

1. 文章编辑页的 AI 生成按钮与结果回填。
2. 设置页：填写 API Key、端点与模型。

== Changelog ==

= 1.0.0 =

* 初始版本：单篇 AI 生成 SEO 标题、描述、关键词与社交文案。
* 内置设置页与合规的 Pro 升级入口。

== Upgrade Notice ==

= 1.0.0 =

首个稳定版本，推荐安装。
