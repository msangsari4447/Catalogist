مسیر کلی Catalogist تا Launch

مسیر کلی: از داده WooCommerce تا Catalog قابل ساخت، قابل نمایش، قابل چاپ و قابل انتشار

WooCommerce → Query → Filter/Sort/Selection → Catalog Item → Template → Rendering → Print → Preview → HTML Output → Elementor Integration → Release Hardening → Launch

این همان زنجیره‌ای است که اسناد پروژه تعریف کرده‌اند؛ ضمن اینکه Core باید مستقل از Elementor بماند و خروجی اولیه MVP، HTML + Browser Print است.

Roadmap
Stage	عنوان	Short Description
4	Filter Engine	اضافه‌کردن فیلترهای قابل ترکیب روی خروجی Product Query بدون بازسازی Query Engine
5	Sorting & Selection	تکمیل ترتیب‌دهی و مکانیزم انتخاب محصولات/Variationها
6	Catalog Configuration	تبدیل Catalog از یک CPT پایه به یک Catalog با تنظیمات ساختاری واقعی
7	Catalog Item Engine	نرمال‌سازی Product/Variation به یک Catalog Item قابل مصرف برای لایه‌های بعد
8	Template Engine	ساخت Template مستقل از Elementor برای Header / Product Loop / Footer
9	Rendering Engine	تبدیل Catalog Context و Catalog Item به HTML واقعی
10	Print Engine	آماده‌سازی خروجی برای A4 و Browser Print با Print CSS
11	Preview Engine	نمایش Preview با همان Data / Context / Renderer واقعی
12	Output Engine	ارائه خروجی HTML قابل استفاده و اتصال Output به Preview/Print
13	Elementor Integration	ساخت Adapter/Widgets و اتصال Elementor بدون وابسته‌کردن Core به آن
14	Release Hardening	تست نهایی، امنیت، Performance، Compatibility، Error Handling و آماده‌سازی Release
15	Launch	بسته‌بندی، مستندسازی، نسخه Release و آماده‌سازی استفاده واقعی

این ترتیب از این جهت منطقی است که prompt.txt صراحتاً جریان Product Query → Filtering → Sorting/Selection → Catalog Items → Template → Rendering → Layout/Print → Preview → Output را تعریف کرده و برای Template، Elementor، Print، Preview و Output مرزبندی جداگانه گذاشته است.


Stage 4 — Filter Engine
Goal

ایجاد یک لایه Filtering مستقل که روی خروجی ProductQueryEngine کار کند.

Scope
فیلترهای ضروری و واقعی موردنیاز Catalog
ترکیب چند Filter
حفظ ProductQueryEngine به‌عنوان مسئول Query
تعریف رفتار دقیق فیلترها
تست Unit/Integration
Out of Scope
Sorting جدید
Manual Selection UI
Catalog Item
Template
Elementor
Rendering
Dependencies
Stage 2 — Product Query Engine
Stage 3 — Variation Engine
Architecture Decision

Filter نباید Query Engine را Duplicate کند. فیلتر باید به‌صورت یک لایه مستقل روی pipeline موجود قرار بگیرد.

Acceptance Criteria
Filtering واقعی روی Product IDs موجود کار کند.
ترکیب فیلترها deterministic باشد.
رفتار برای ورودی نامعتبر مشخص و امن باشد.
هیچ Regression در Stage 2 و 3 ایجاد نشود.
Tests
Unit
Integration با WooCommerce
Edge Cases
Regression
Security

Validation، Sanitization و جلوگیری از Query ناامن.

Exit Criteria

Filter Engine مستقل، تست‌شده و آماده مصرف Stage بعد باشد.

Stage 5 — Sorting & Selection
Goal

ایجاد Selection pipeline برای تعیین اینکه چه Product/Variationهایی و با چه ترتیبی وارد Catalog شوند.

Scope
Sortingهای ضروری
ترتیب deterministic
Selection
اتصال Selection به Variation modes موجود
پشتیبانی از Manual Selection در حدی که برای pipeline اصلی لازم است
Out of Scope
UI کامل مدیریت Catalog
Catalog Item
Template
Rendering
Dependencies
Stage 4
Stage 3
Architecture Decision

Sorting و Selection باید بعد از Query/Filter قرار بگیرند و مسئولیتشان از Query Engine جدا بماند.

Acceptance Criteria

Pipeline:

Query → Filter → Sort → Select

باید قابل پیش‌بینی و تست‌پذیر باشد.

Tests
Sorting edge cases
Selection
Product/Variation
Regression
Security

Input validation و جلوگیری از selection خارج از داده معتبر.

Exit Criteria

لیست نهایی Catalog Items بالقوه با ترتیب و selection مشخص تولید شود.

Stage 6 — Catalog Configuration
Goal

تبدیل Catalog CPT موجود به یک configuration container واقعی.

Scope

Catalog باید بتواند به‌صورت ساختاری تنظیمات خود را نگه دارد، از جمله در حد موردنیاز:

Query
Filters
Selection
Sorting
Variation behavior
Template
Layout/Print settings
Output settings
Out of Scope
Rendering
Elementor implementation
PDF
سیستم کامل Template
Dependencies
Stage 4
Stage 5
Stage 1 Catalog foundation
Architecture Decision

Configuration باید ساختاری ذخیره شود؛ نه HTML تولیدشده. WordPress-native storage اولویت دارد: CPT/Post Meta/Options.

Acceptance Criteria
Catalog قابل Create / Save / Load باشد.
Configuration پایدار و versionable باشد.
invalid configuration fail safely کند.
Admin behavior واقعی باشد.
Tests
Persistence
Validation
Permissions
Regression
Runtime
Security

Capability checks، nonce، sanitization، validation و permission boundaries.

Exit Criteria

Catalog بتواند configuration واقعی pipeline را نگه دارد.

Stage 7 — Catalog Item Engine
Goal

ایجاد representation استاندارد Catalog Item برای Product و Variation.

Scope
نرمال‌سازی Product
نرمال‌سازی Variation
ایجاد Context استاندارد
حذف وابستگی مستقیم لایه‌های بعدی به جزئیات WooCommerce
Out of Scope
Template
Rendering
Print
Elementor
Dependencies
Stage 3
Stage 5
Stage 6
Architecture Decision

مسیر باید تبدیل شود به:

WooCommerce Product/Variation → Catalog Item → Context

این دقیقاً مطابق معماری هدف prompt.txt است.

Acceptance Criteria

Catalog Item برای Simple Product و Variation قابل تولید باشد و داده لازم برای Rendering را به‌شکل normalized فراهم کند.

Tests
Simple product
Variable product
Variation
Deleted/invalid entities
Edge cases
Security

Safe data access و عدم افشای اطلاعات غیرلازم.

Exit Criteria

Catalog Item یک contract پایدار برای Template/Renderer باشد.

Stage 8 — Template Engine
Goal

ایجاد Template Engine مستقل از Elementor.

Scope

مدل Template شامل:

Header → Product Loop → Product Card → Footer

و مصرف Catalog Context.

Out of Scope
Elementor Editor
Print
Preview
PDF
Dependencies
Stage 7
Architecture Decision

Template نباید business logic مربوط به Elementor باشد.

Acceptance Criteria
Template قابل تعریف و ذخیره باشد.
Template بتواند Catalog Context را مصرف کند.
Template مستقل از Elementor اجرا شود.
Tests
Template creation/loading
Context binding
Missing template
invalid template
regression
Security

Capability، validation، escaping و safe persistence.

Exit Criteria

یک Template واقعی و تست‌شده آماده مصرف Renderer باشد.

Stage 9 — Rendering Engine
Goal

تبدیل Context به HTML واقعی.

Scope
Renderer
Product Card rendering
Header/Footer
HTML structure
escaping
RTL/LTR presentation concerns
Out of Scope
Print-specific behavior
Elementor rendering
PDF
Browser Preview UI
Dependencies
Stage 7
Stage 8
Architecture Decision

Data → Context → Renderer → HTML

و نباید WooCommerce access، Elementor logic، Print logic و Output logic در یک component مخلوط شوند.

Acceptance Criteria

یک Catalog واقعی بتواند HTML تولید کند.

Tests
Render simple product
variation
missing image
invalid data
escaping
RTL/LTR
Security

Output escaping و جلوگیری از unsafe HTML injection.

Exit Criteria

HTML Renderer مستقل و قابل استفاده برای Print/Preview/Output.

Stage 10 — Print Engine
Goal

تبدیل HTML Rendering به خروجی چاپ حرفه‌ای A4.

Scope
A4
margins
page dimensions
print CSS
page breaks
product-card break prevention
header/footer behavior
RTL/LTR
Browser Print

این موارد صریحاً در معماری Print تعریف شده‌اند.

Out of Scope
PDF library
PDF generation
Elementor
Dependencies
Stage 9
Architecture Decision

Print باید از Web Presentation جدا باشد و از CSS print mechanisms استفاده کند.

Acceptance Criteria

Catalog در Browser Print به شکل قابل قبول A4 چاپ شود.

Tests
A4 layout
page breaks
long catalogs
RTL
browser runtime
Security

Safe output و URL/resource handling.

Exit Criteria

Browser Print برای Catalog واقعی usable باشد.

Stage 11 — Preview Engine
Goal

ساخت Preview واقعی بر مبنای همان Rendering Pipeline.

Scope
Preview endpoint/view
استفاده از همان Data
همان Context
همان Renderer
Out of Scope
Renderer دوم
PDF
Elementor Editor
Architecture Decision

طبق قرارداد پروژه:

Same Data → Same Context → Same Renderer → Preview / Print / Output

و نباید Preview یک سیستم Rendering مستقل بسازد.

Acceptance Criteria

آنچه Preview نشان می‌دهد با Rendering واقعی یکسان باشد.

Tests
Preview simple catalog
variations
errors
permissions
runtime
Security

Authorization، nonce در صورت نیاز، escaping و endpoint security.

Exit Criteria

Preview قابل اتکا برای بررسی Catalog قبل از Print/Output باشد.

Stage 12 — Output Engine
Goal

تکمیل pipeline نهایی برای خروجی HTML و Browser Print.

Scope
HTML output
output routing
ارتباط Preview/Print/Output
public/admin access behavior در حد نیاز MVP
Out of Scope
PDF
QR
Providerهای آینده مگر نیاز واقعی ایجاد کند
Architecture Decision

خروجی اولیه رسمی پروژه HTML + Browser Print است و PDF نباید hard dependency Core MVP باشد.

Acceptance Criteria

Catalog از ابتدا تا خروجی بدون شکستن pipeline کار کند.

Tests
Full pipeline
HTML output
print output
permissions
invalid config
missing product/template
Exit Criteria

یک Catalog کامل از Query تا Output قابل استفاده باشد.

Stage 13 — Elementor Integration
Goal

اضافه‌کردن Elementor به‌عنوان Adapter اختیاری.

Scope
Elementor integration boundary
Widgets در صورت نیاز واقعی
Dynamic Tags در صورت نیاز واقعی
Template integration
Controls / editor integration در حد MVP
Out of Scope
انتقال business logic به Elementor
وابسته‌کردن Core به Elementor
Dependencies

Stages 7–12

Architecture Decision

Catalogist Core ← Elementor Adapter

نه برعکس. Core باید حتی بدون Elementor سالم بماند.

Acceptance Criteria

Catalogist با Elementor فعال، غیرفعال و نصب‌نشده رفتار صحیح داشته باشد.

Tests
Elementor active
Elementor inactive
Elementor unavailable
integration regression
Security

Capabilities، nonce، validation و escaping مخصوص Editor/Admin.

Exit Criteria

Elementor صرفاً integration layer باقی بماند.

Stage 14 — Release Hardening
Goal

تبدیل سیستم تکمیل‌شده به یک Release Candidate واقعی.

Scope
Full regression
Security audit
Performance testing
Compatibility
Error handling
RTL/LTR
WooCommerce dependency behavior
Elementor optional behavior
500–1000 product performance assessment
documentation cleanup

Performance target رسمی پروژه حدود 500–1000 محصول است و پروژه صراحتاً از N+1، repeated object loading و محاسبات زائد پرهیز می‌کند.

Out of Scope
Feature جدید
معماری جدید
PDF مگر جداگانه مصوب شود
QR/custom-field integrations جدید
Acceptance Criteria

هیچ blocker شناخته‌شده‌ای باقی نماند و تمام Stage Gates قبلی برقرار باشند.

Tests
Full automated suite
Integration
Runtime
Security
Performance
compatibility
Exit Criteria

Release Candidate = قابل انتشار

Stage 15 — Launch
Goal

انتقال پروژه از وضعیت Development/RC به Release واقعی.

Scope
تعیین version
Release package
final documentation
installation/activation validation
upgrade validation
clean installation validation
release notes
final Git tag/checkpoint
Out of Scope
Feature development
refactoring غیرضروری
Acceptance Criteria
نصب جدید موفق
Activation موفق
WooCommerce integration سالم
Catalog creation تا Output سالم
Upgrade path بررسی‌شده
Documentation با implementation واقعی منطبق باشد
Exit Criteria

Catalogist = Release رسمی قابل استفاده

یک نکته مهم درباره Featureهای بعد از MVP

طبق prompt.txt این‌ها در معماری پروژه وجود دارند، اما نباید برای Launch اولیه به زور داخل Core MVP قرار بگیرند:

PDF، QR Codes، Custom Fields providerها.

خود سند برای PDF صریحاً می‌گوید ابتدا HTML + Browser Print هدف است و PDF فقط در صورت نیاز واقعی و با provider/abstraction اضافه شود. برای QR و Custom Fields هم همین رویکرد progressive را مشخص کرده است.

بنابراین مسیر واقعی که من پیشنهاد می‌کنم:

Stage 4 Filter → Stage 5 Sort/Select → Stage 6 Catalog Configuration → Stage 7 Catalog Item → Stage 8 Template → Stage 9 Rendering → Stage 10 Print → Stage 11 Preview → Stage 12 Output → Stage 13 Elementor → Stage 14 Hardening → Stage 15 Launch

این مسیر با زنجیره معماری رسمی پروژه منطبق است و مهم‌تر از آن، هر Stage یک مرز مشخص دارد و قرار نیست Stage بعدی زودتر از نیازش ساخته شود.

نکته قطعی: قراردادهای بالا را به‌عنوان Roadmap Contract پیشنهادی مبتنی بر اسناد فعلی نوشته‌ام؛ چون خود repository در سه فایل بررسی‌شده، شماره Stageهای 4 تا 15 را به‌صورت رسمی تعیین نکرده است.