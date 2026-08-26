# 预里程碑7报告 — Print Engine（打印引擎）

**日期：** 2026-08-25
**里程碑：** 7
**状态：** 架构提案

---

## 1. 本里程碑目标

实现可打印的PDF/HTML输出引擎，支持：

- A4纸张尺寸
- 纵向/横向方向切换
- 可配置边距（上/下/左/右）
- 1–4列布局
- 接近真实打印效果的预览
- 基于`@media print`的标准打印CSS
- `break-inside: avoid`及`page-break-inside: avoid`防止产品卡片被截断
- 页眉、页脚、封面、章节、表格、产品卡片的页面断点行为可控
- 通过现有Template Engine渲染，复用已有上下文

---

## 2. 现有架构分析

### 2.1 核心数据流

```
WooCommerce
     ↓
Product Query Engine (ProductRepository)
     ↓
Variation Engine (VariationService)
     ↓
Catalog Processor (CatalogProcessor → CatalogItem[])
     ↓
Template Engine (TemplateEngine)
     ↓
TemplateLoader → TemplateContextBuilder → TemplateRenderer
     ↓
HTML输出
     ↓
[Print Engine ← 本里程碑新增]
     ↓
Print CSS / HTML打印 / PDF导出
```

### 2.2 现有模板系统

**TemplateEngine** (`src/Template/TemplateEngine.php`):
- `renderCatalog(Catalog, array $catalogItems, ?array $settings)` — 渲染整个目录
- `renderItem(Catalog, CatalogItem, ?array $settings)` — 渲染单个产品
- 已通过`TemplateContextBuilder`将`layout`和`print`设置传入上下文

**TemplateContextBuilder** (`src/Template/TemplateContextBuilder.php`):
- `normalizeLayoutSettings()`: 默认`columns=2`, `page_size='A4'`, `orientation='portrait'`
- `normalizePrintSettings()`: 默认`margins=[top:20, right:20, bottom:20, left:20]`
- `build()` 返回上下文中已包含`layout`、`columns`、`page_size`、`orientation`、`print`、`margins`、`show_header`、`show_footer`

**FileTemplateRenderer** (`src/Template/Renderer/FileTemplateRenderer.php`):
- `renderSection(string $section, array $context)` — 查找`{templateSlug}/{section}.php`
- 支持主题覆盖 → 插件默认 → 内置fallback的三级回退链

**默认模板** (`templates/default/`):
- `catalog.php` — 根模板，调用header + product-loop + footer
- `product-card.php` — 产品卡片
- `product-loop.php` — 产品循环（带`catalogist-columns-{N}`类）
- `variation-table.php` — 变体表格
- `header.php` / `footer.php` — 页眉页脚

### 2.3 现有设置体系

**CatalogSettings** (`src/Catalog/CatalogSettings.php`):
- `get_default_print_settings()`:
  ```php
  [
      'page_size' => 'a4',
      'orientation' => 'portrait',
      'margins' => ['top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15],
      'columns' => 2,
  ]
  ```
- `get_default_layout_settings()`:
  ```php
  [
      'columns' => 2,
      'card_style' => 'default',
      'show_image' => true,
      'show_price' => true,
      'show_sku' => true,
      'show_excerpt' => false,
  ]
  ```

**Catalog实体** (`src/Catalog/Catalog.php`):
- 存储`layout_settings`和`print_settings`为原始数组
- 无`PrintSettings`值对象

### 2.4 输出路径

目前输出仅有两条路径：
1. **短代码** `[catalogist id="123" template="default" columns="2"]` → `renderCatalog()` → HTML
2. **函数** `render_catalog(int $catalogId, ?array $settings)` → `renderCatalog()` → HTML
3. **Elementor** → 同上路径

---

## 3. 打印引擎架构设计

### 3.1 架构原则

- **不引入PDF库依赖**：M7只做HTML+Print，PDF导出留给M9
- **复用现有Template Engine**：打印和网页使用同一渲染管道，仅通过CSS区分
- **Print CSS独立文件**：放在`assets/css/print.css`
- **可选的打印模板目录**：`templates/print/`和`templates/default/`并存

### 3.2 打印输出管道

```
Catalog → CatalogProcessor → TemplateEngine → HTML
                                                  ↓
                                         Print CSS注入
                                                  ↓
                                        @media print
                                                  ↓
                                        浏览器打印对话框
```

### 3.3 分层设计

```
PrintEngine (入口层)
  ├── PrintCSSGenerator (CSS生成器)
  ├── PrintContextBuilder (复用TemplateContextBuilder)
  └── PrintRenderer (渲染打印HTML)
        ├── FilePrintTemplateRenderer (文件模板)
        └── BuiltInPrintTemplates (内置模板)
```

### 3.4 关键设计决策

#### 决策1：打印模板是否独立于网页模板？

**推荐方案：使用同一模板，通过CSS区分**

- 优点：模板不重复，维护成本低
- 实现：在现有`templates/default/catalog.php`基础上，通过`data-mode="print"`属性标识打印模式
- Print CSS通过`<style>`标签内联注入，或通过新文件`assets/css/print.css`加载

#### 决策2：是否需要`PrintSettings`值对象？

**推荐方案：先不创建，沿用数组**

- 理由：M7目标简洁，避免过度设计
- 如果后续发现需要更多验证逻辑，再提取为值对象

#### 决策3：PDF导出何时引入？

**推荐方案：M7不引入PDF**

- M9再做PDF导出（可考虑mPDF或浏览器Print to PDF方案）
- M7仅保证打印CSS质量，让浏览器原生打印功能正常工作

---

## 4. 打印配置项

### 4.1 新增配置项

在`CatalogSettings::get_default_print_settings()`中补充：

```php
[
    'page_size' => 'a4',           // a4, a3, letter, legal
    'orientation' => 'portrait',   // portrait, landscape
    'margins' => [
        'top' => 15,
        'right' => 15,
        'bottom' => 15,
        'left' => 15,
    ],
    'columns' => 2,                // 1-4
    'show_header' => true,
    'show_footer' => true,
    'show_cover' => false,         // 封面页开关
    'page_break_after_cover' => true,
    'page_break_after_header' => false,
    'page_break_before_footer' => false,
    'avoid_break_inside' => [
        'product-card',
        'variation-table',
        'section',
    ],
]
```

### 4.2 设置存储

- 现有`Catalog`实体已存储`print_settings`为数组，直接复用
- 无需新增数据库字段

---

## 5. A4布局与页边距

### 5.1 A4尺寸

- A4: 210mm × 297mm
- 边距默认15mm（约0.59英寸）
- 可用内容宽度 = 210 - 左 - 右（纵向）或 297 - 左 - 右（横向）

### 5.2 CSS实现

```css
/* print.css 核心规则 */
@page {
    size: A4 portrait;
    margin: 15mm;
}

/* 横向覆盖 */
[data-orientation="landscape"] @page {
    size: A4 landscape;
}

/* 边距覆盖 */
[data-margins="20,20,20,20"] {
    @page { margin: 20mm; }
}
```

### 5.3 列布局计算

```
可用宽度 = A4宽度 - 左margin - 右margin
单列宽度 = 可用宽度 / columns
卡片宽度 = 单列宽度 - 列间距
```

| 方向 | 可用宽度 | 1列 | 2列 | 3列 | 4列 |
|------|---------|-----|-----|-----|-----|
| 纵向A4 | 180mm | 180mm | 85mm | 55mm | 37mm |
| 横向A4 | 267mm | 267mm | 128mm | 84mm | 61mm |

---

## 6. 页面断点控制

### 6.1 断点CSS规则

```css
/* 防止元素被截断 */
.catalogist-product-card,
.catalogist-variation-table,
.catalogist-section {
    break-inside: avoid;
    page-break-inside: avoid; /* 兼容性 */
}

/* 封面页后强制分页 */
.catalogist-cover + .catalogist-catalog {
    break-before: page;
    page-break-before: always;
}

/* 页眉后强制分页 */
.catalogist-header + .catalogist-product-loop {
    break-after: page;
}
```

### 6.2 模板中的断点标记

在`templates/default/catalog.php`中增加断点数据属性：

```php
<div class="catalogist-catalog"
     data-print-mode="true"
     data-orientation="<?php echo esc_attr( $orientation ); ?>"
     data-margins="<?php echo esc_attr( json_encode( $margins ) ); ?>"
     data-columns="<?php echo esc_attr( $columns ); ?>">
```

### 6.3 封面页支持

新增`templates/default/cover.php`：

```php
<div class="catalogist-cover" data-print-section="cover">
    <h1><?php echo esc_html( $catalog->get_title() ); ?></h1>
    <p><?php echo esc_html( $catalog->get_subtitle() ?? '' ); ?></p>
    <p class="catalogist-cover-meta">
        <?php echo esc_html( sprintf(
            '%d 件商品 | %s | %s',
            count( $items ),
            $orientation === 'landscape' ? '横向' : '纵向',
            date_i18n( 'Y年n月j日' )
        ) ); ?>
    </p>
</div>
```

---

## 7. 打印CSS规范

### 7.1 文件位置

`assets/css/print.css` — 独立打印样式表

### 7.2 核心规范

```css
/* 基础重置 */
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* 分页控制 */
    .catalogist-product-card { break-inside: avoid; }
    .catalogist-variation-table { break-inside: avoid; }
    .catalogist-product-loop { break-inside: avoid; }

    /* 隐藏非打印元素 */
    .no-print,
    .catalogist-print-controls { display: none !important; }

    /* 链接显示URL */
    a[href]::after {
        content: " (" attr(href) ")";
        font-size: 0.8em;
        color: #666;
    }

    /* 图片自适应 */
    img {
        max-width: 100% !important;
        height: auto !important;
        object-fit: contain;
    }

    /* 字体大小调整 */
    body {
        font-size: 10pt;
        line-height: 1.4;
    }

    /* 表格紧凑 */
    .catalogist-variation-table {
        font-size: 9pt;
        width: 100%;
    }
}
```

### 7.3 列布局CSS

```css
@media print {
    .catalogist-catalog[data-columns="1"] .catalogist-product-loop {
        grid-template-columns: 1fr;
    }
    .catalogist-catalog[data-columns="2"] .catalogist-product-loop {
        grid-template-columns: 1fr 1fr;
    }
    .catalogist-catalog[data-columns="3"] .catalogist-product-loop {
        grid-template-columns: 1fr 1fr 1fr;
    }
    .catalogist-catalog[data-columns="4"] .catalogist-product-loop {
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    /* 横向时调整 */
    .catalogist-catalog[data-orientation="landscape"] .catalogist-product-loop {
        gap: 8mm;
    }
}
```

---

## 8. Template Engine集成

### 8.1 现有集成点

`TemplateContextBuilder::build()` 已经返回打印相关上下文变量：

```php
$context = [
    'catalog'       => $catalog,
    'items'         => $catalogItems,
    'template_id'   => $template->get_id(),
    'template_name' => $template->get_name(),
    'layout'        => $layout,         // 包含columns, card_style等
    'columns'       => $columns,
    'page_size'     => $page_size,      // 'A4'
    'orientation'   => $orientation,    // 'portrait'/'landscape'
    'print'         => $print,          // 包含margins等
    'margins'       => $margins,
    'show_header'   => $show_header,
    'show_footer'   => $show_footer,
];
```

### 8.2 新增PrintEngine入口

```php
// src/Print/PrintEngine.php
namespace Catalogist\Print;

use Catalogist\Catalog\Catalog;
use Catalogist\Template\TemplateEngineInterface;

class PrintEngine {
    private TemplateEngineInterface $template_engine;
    private PrintCSSGenerator $css_generator;

    public function __construct(
        TemplateEngineInterface $template_engine,
        PrintCSSGenerator $css_generator
    ) {
        $this->template_engine = $template_engine;
        $this->css_generator = $css_generator;
    }

    /**
     * 生成打印HTML
     */
    public function generatePrintHTML(
        Catalog $catalog,
        array $catalogItems,
        ?array $printSettings = null
    ): string {
        // 复用TemplateEngine的上下文构建
        $context = $this->template_engine->getContextBuilder()->build(
            $catalog,
            $catalogItems,
            null,
            $printSettings
        );

        // 注入打印CSS
        $printCSS = $this->css_generator->generate( $context );

        // 渲染catalog模板（带data-print-mode属性）
        $html = $this->template_engine->renderCatalog( $catalog, $catalogItems, $printSettings );

        // 在<head>中注入CSS
        return $this->injectPrintCSS( $html, $printCSS );
    }

    /**
     * 生成打印预览URL（新窗口打开）
     */
    public function generatePrintPreviewURL(
        int $catalogId,
        ?array $printSettings = null
    ): string {
        // 返回一个临时URL，用户可在新窗口打开并打印
        return add_query_arg(
            [
                'catalogist_print' => 1,
                'catalog_id' => $catalogId,
                'settings' => base64_encode( wp_json_encode( $printSettings ) ),
            ],
            home_url( '/' )
        );
    }
}
```

### 8.3 复用现有TemplateEngine

- `PrintEngine`不独立渲染，而是通过`TemplateEngineInterface::renderCatalog()`获取HTML
- 仅负责注入打印CSS和设置`data-*`属性
- 模板文件无需修改，只需CSS区分

---

## 9. Elementor兼容性

### 9.1 不影响Elementor集成

- Elementor Widget和Dynamic Tag继续使用现有渲染管道
- 打印功能作为独立入口，不与Elementor冲突

### 9.2 新增Elementor动态标签（可选）

M7可考虑新增一个打印预览动态标签：

```php
// src/Elementor/DynamicTags/CatalogPrintPreviewDynamicTag.php
class CatalogPrintPreviewDynamicTag extends ProductDynamicTagBase {
    public function render(): string {
        $catalog_id = $this->resolve_catalog_id();
        if ( !$catalog_id ) return '';
        $container = catalogist_get_container();
        $print_engine = $container->get( PrintEngine::class );
        $catalog = $container->get( CatalogRepositoryInterface::class )->find( $catalog_id );
        return $print_engine->generatePrintPreviewURL( $catalog_id );
    }
}
```

### 9.3 安全边界

- Print Engine不引用任何Elementor类
- Elementor不引用Print Engine类
- 两者通过容器解耦

---

## 10. 短代码与输出

### 10.1 新增打印属性

`template-shortcode.php`中新增打印相关属性：

```php
$atts = shortcode_atts(
    [
        'id' => '',
        'template' => 'default',
        'columns' => 2,
        'print' => 0,              // 新增：是否打印模式
        'orientation' => 'portrait',
        'page_size' => 'a4',
    ],
    $atts,
    'catalogist'
);
```

### 10.2 打印模式触发

当`print=1`时，返回经过`PrintEngine`处理的HTML：

```php
if ( ! empty( $atts['print'] ) ) {
    $print_engine = $container->get( PrintEngine::class );
    $print_settings = [
        'orientation' => $atts['orientation'],
        'page_size' => $atts['page_size'],
        'margins' => ['top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15],
    ];
    return $print_engine->generatePrintHTML( $catalog, $catalogItems, $print_settings );
}
```

### 10.3 短代码用法示例

```
[catalogist id="123" print="1" orientation="landscape" columns="2"]
```

### 10.4 新增全局函数

```php
// src/Template/template-functions.php
function render_catalog_print(
    int $catalogId,
    ?array $printSettings = null
): string {
    $container = catalogist_get_container();
    if ( !$container ) return '';
    $print_engine = $container->get( PrintEngine::class );
    return $print_engine->generatePrintPreviewHTML( $catalogId, $printSettings );
}
```

---

## 11. 文件结构变更

### 11.1 新增文件

```
src/
└── Print/
    ├── PrintEngine.php              # 打印引擎入口
    ├── PrintCSSGenerator.php        # 打印CSS生成器
    ├── PrintContextBuilder.php      # 打印上下文构建（可复用）
    └── PrintServiceProvider.php     # 服务提供者

templates/
└── default/
    ├── cover.php                    # 封面模板
    └── (现有模板不变)

assets/
└── css/
    └── print.css                    # 打印样式表
```

### 11.2 修改文件

```
src/Template/template-shortcode.php  # 新增print参数
src/Template/template-functions.php  # 新增render_catalog_print()
src/Core/Plugin.php                  # 注册PrintServiceProvider
src/Core/Assets.php                  # 加载print.css
```

### 11.3 无需修改的文件

- `src/Catalog/Catalog.php` — 已有`get_print_settings()`
- `src/Template/TemplateContextBuilder.php` — 已处理print设置
- `src/Template/TemplateEngine.php` — 无需修改
- `src/Template/Renderer/FileTemplateRenderer.php` — 无需修改

---

## 12. 安全性考虑

### 12.1 输入验证

- `print_settings`来自用户设置或短代码参数
- 所有参数通过`CatalogSettings`默认值白名单过滤
- `page_size`仅允许`['a4', 'a3', 'letter', 'legal']`
- `orientation`仅允许`['portrait', 'landscape']`
- `columns`限制在`[1, 4]`范围内

### 12.2 输出转义

- 打印HTML通过`TemplateEngine`渲染，已有完整转义
- `PrintCSSGenerator`生成的CSS不包含用户可控内容

### 12.3 能力检查

- 打印功能与现有目录查看权限一致
- 无需新增capability

---

## 13. 性能考虑

### 13.1 缓存策略

- 打印HTML缓存：使用WordPress Transient API
- 缓存键：`catalogist_print_{catalog_id}_{settings_hash}`
- 缓存时间：1小时（目录更新时自动清除）

### 13.2 避免重复查询

- `PrintEngine::generatePrintHTML()`接收已构建的`$catalogItems`
- 不重复调用`CatalogProcessor`和`ProductRepository`
- 与短代码共享数据管道

### 13.3 CSS优化

- `print.css`仅在打印模式或`@media print`时加载
- 非打印模式不加载，不影响页面性能

---

## 14. 向后兼容性

### 14.1 现有功能不受影响

- 所有M1-M6代码不变
- `TemplateEngineInterface`无变化
- 短代码现有参数正常工作
- Elementor Widget和Dynamic Tag正常工作

### 14.2 渐进增强

- 打印功能是可选的
- 不设置`print=1`时，行为与现在完全一致
- 新模板文件（`cover.php`）仅在启用时加载

### 14.3 数据库迁移

- 无需数据库迁移
- 使用现有`print_settings` post meta字段
- 旧目录的`print_settings`为空数组时，使用默认值

---

## 15. 测试计划

### 15.1 单元测试

```
tests/
└── Unit/
    └── Print/
        ├── PrintEngineTest.php
        ├── PrintCSSGeneratorTest.php
        └── PrintServiceProviderTest.php
```

**测试覆盖：**
- PrintEngine实例化
- CSS生成器输出正确`@page`规则
- `generatePrintHTML()`返回包含打印CSS的HTML
- 打印预览URL生成正确
- 不同page_size和orientation的CSS生成
- 断点CSS规则正确注入

### 15.2 集成测试

```
tests/
└── Integration/
    └── Print/
        └── PrintEngineIntegrationTest.php
```

**测试覆盖：**
- 完整管道：Catalog → Processor → PrintEngine → HTML
- 封面页渲染
- 页眉页脚打印模式渲染
- 多列布局打印CSS生成
- 短代码打印参数解析

### 15.3 测试数据

复用现有`Mock_Catalog`和`Mock_WC_Product`，无需新增mock类。

---

## 16. 风险与应对

| 风险 | 影响 | 应对 |
|------|------|------|
| 浏览器打印CSS兼容性差异 | 中等 | 使用标准CSS分页属性，多浏览器测试 |
| 图片在打印时变形 | 低 | CSS `max-width: 100%` + `object-fit: contain` |
| 复杂表格分页问题 | 中 | `break-inside: avoid` + 表格`page-break-inside: avoid` |
| Print CSS与网页CSS冲突 | 低 | 严格使用`@media print`作用域 |
| 打印模板文件缺失 | 低 | 回退到内置fallback模板 |

---

## 17. 实施阶段

### 阶段一：基础架构（预计2天）

- [ ] 创建`src/Print/PrintEngine.php`
- [ ] 创建`src/Print/PrintCSSGenerator.php`
- [ ] 创建`src/Print/PrintServiceProvider.php`
- [ ] 注册服务到容器

### 阶段二：CSS生成器（预计1天）

- [ ] 实现`@page`规则生成
- [ ] 实现列布局CSS生成
- [ ] 实现断点CSS规则生成
- [ ] 单元测试覆盖

### 阶段三：模板集成（预计1天）

- [ ] 创建`templates/default/cover.php`
- [ ] 修改`templates/default/catalog.php`添加打印数据属性
- [ ] 修改`template-shortcode.php`添加print参数
- [ ] 新增`render_catalog_print()`全局函数

### 阶段四：Assets与缓存（预计0.5天）

- [ ] 创建`assets/css/print.css`
- [ ] 修改`Core/Assets.php`加载打印CSS
- [ ] 实现打印HTML缓存

### 阶段五：测试与验证（预计1天）

- [ ] 编写单元测试
- [ ] 编写集成测试
- [ ] 手动浏览器打印测试
- [ ] RTL兼容性测试

---

## 18. 完成标准

M7被认为是完成当且仅当：

1. ✅ `PrintEngine`类实现并注册到容器
2. ✅ `assets/css/print.css`存在并正确加载
3. ✅ 短代码支持`print="1"`参数并返回打印HTML
4. ✅ 打印HTML包含正确的`@page`规则和分页CSS
5. ✅ 产品卡片在打印时不会被截断（`break-inside: avoid`）
6. ✅ 封面页支持并强制分页
7. ✅ 所有现有M1-M6功能不受影响
8. ✅ 单元测试通过率≥90%
9. ✅ 在Chrome、Firefox、Safari中手动打印测试通过

---

## 19. 最终结论

### 推荐方案

**采用"CSS隔离打印"方案**，不引入PDF库，不创建独立打印模板，复用现有Template Engine渲染管道。

### 理由

1. **架构一致性**：打印和网页使用同一数据管道，避免重复逻辑
2. **零依赖**：不引入新的第三方库
3. **渐进增强**：打印功能是现有功能的扩展，不影响现有功能
4. **维护成本低**：只需维护一套模板和CSS
5. **符合规范**：使用标准CSS分页属性，浏览器兼容性最好

### 后续里程碑展望

- **M8 Preview**：基于M7的打印HTML生成打印预览界面
- **M9 Output**：引入PDF导出（mPDF或浏览器Print to PDF）
- **M10 Security/Performance**：打印缓存优化、安全加固

---

**报告完成日期：** 2026-08-25
**下一步：** 等待批准后开始实施Milestone 7
