# Pre-Milestone 8 Report: Preview Engine

**Date:** 2026-08-26
**Status:** Architectural Analysis — Ready for Review

---

## 1. Goal of Milestone 8

Design and implement a professional catalog preview experience that:

1. Uses the existing M7 Print Engine infrastructure
2. Produces a preview as close as reasonably possible to actual printed output
3. Provides A4 paper simulation in the browser
4. Supports portrait/landscape orientation
5. Allows users to preview before printing
6. Integrates cleanly into the existing admin workflow

**Critical Constraints:**
- M8 must NOT implement PDF generation
- M8 must NOT replace the existing Print Engine
- M8 must NOT duplicate rendering logic
- M8 must build on top of existing architecture

---

## 2. Current Rendering Pipeline (Verified)

The actual current data flow:

```
WooCommerce
     ↓
Product Query Engine (ProductRepositoryInterface)
     ↓
Filter Engine (ProductFilters)
     ↓
Catalog Item Processor (CatalogProcessor)
     ↓
Template Engine (TemplateEngineInterface)
     ↓
Print Engine (PrintEngineInterface) ← M7
     ↓
HTML + CSS Output
```

**Preview Integration Point:**

Preview should integrate **after** PrintEngine generates HTML, adding a visualization layer:

```
PrintEngine.generatePrintHTML()
     ↓
PreviewEngine.renderPreview()
     ↓
Admin Preview Container / Modal
```

This ensures preview sees exactly what print will produce — no divergence.

---

## 3. Existing Print Engine Analysis

### 3.1 PrintEngineInterface

```php
interface PrintEngineInterface {
    public function generatePrintHTML(Catalog $catalog, array $catalogItems, ?array $printSettings = null): string;
    public function generatePrintCSS(array $settings): string;
    public function generatePrintPreviewURL(int $catalogId, ?array $printSettings = null): string;
}
```

**Reuse Potential:** 100%. Preview will call `generatePrintHTML()` directly.

### 3.2 PrintEngine Implementation

Key methods verified:

| Method | Responsibility | Reuse by Preview |
|--------|----------------|------------------|
| `generatePrintHTML()` | Renders catalog via TemplateEngine, injects print CSS/attributes | **Direct reuse** |
| `generatePrintCSS()` | Generates `@page`, margins, columns, break rules | **Direct reuse** |
| `buildPrintSettings()` | Merges defaults, catalog settings, overrides | **Direct reuse** |
| `injectPrintAttributes()` | Adds `data-print-mode`, `data-orientation`, etc. | Inherited via HTML |
| `injectPrintCSS()` | Injects `<style media="print">` | Inherited via HTML |

**No duplication required.** Preview wraps the output.

### 3.3 Print CSS (`assets/css/print.css`)

Already contains:
- `@media print` rules for A4
- Column layouts (1-4 columns)
- Page-break protection
- Cover page styling
- RTL support

**CSS Reuse Strategy:**
- Preview will apply the same CSS but outside `@media print` wrapper
- A4 dimensions simulated via CSS width/height on preview container
- Print CSS acts as the base; preview CSS adds visual chrome

### 3.4 Print Templates

- `templates/default/cover.php` — Cover page
- `templates/default/catalog.php` — Main catalog wrapper with `data-print-mode`
- `templates/default/header.php`, `footer.php` — Header/footer
- `templates/default/product-loop.php`, `product-card.php` — Product rendering

**Template Reuse:** 100%. Preview renders the same templates.

### 3.5 Shortcode Integration

Existing: `[catalogist id="123" print="1" orientation="landscape" columns="3"]`

Preview can use this same mechanism via URL parameters:
- `?catalogist_print=1&catalog_id=123`
- Optional `print_settings` (base64-encoded JSON)

---

## 4. Preview Architecture Options

### Option A: Browser-based Preview (Container with CSS Simulation)

**How it works:**
1. PrintEngine generates HTML
2. PreviewEngine wraps HTML in a container with A4-simulation CSS
3. Container styled to look like paper (white background, shadow, margins)
4. Rendered in admin modal or dedicated preview page

**Pros:**
- Uses exact same HTML/CSS as print
- Zero duplication of rendering logic
- Fast — no iframe overhead
- Easy CSS isolation via scoped class

**Cons:**
- Browser CSS rendering ≠ actual print engine
- Page breaks visualized approximately, not exactly
- Multi-page preview requires JavaScript calculation

**Accuracy:** 85-90% (layout, margins, columns accurate; page breaks approximate)

---

### Option B: Dedicated Preview Renderer

**How it works:**
1. Create separate PreviewRenderer class
2. Builds its own context and rendering pipeline
3. Generates preview-specific HTML

**Pros:**
- Fine-grained control over preview output
- Can add preview-specific features

**Cons:**
- **Violates architecture principle** — duplicates rendering
- Maintenance burden — two rendering paths
- Divergence risk between preview and actual print

**Verdict:** **REJECTED**. Violates core architecture.

---

### Option C: iframe Print Preview

**How it works:**
1. Generate print HTML
2. Load into isolated iframe
3. Apply print CSS in screen context
4. Optional: trigger actual browser print preview

**Pros:**
- Better CSS isolation (iframe sandbox)
- Can use `@media print` CSS directly
- Closer to actual browser print behavior

**Cons:**
- iframe overhead
- Cross-origin considerations for assets
- Communication complexity for controls
- Still cannot perfectly match browser pagination

**Accuracy:** 90-95% (closer due to iframe isolation)

---

### Comparison Matrix

| Criterion | Option A (Container) | Option B (Renderer) | Option C (iframe) |
|-----------|---------------------|---------------------|-------------------|
| Architecture consistency | ✅ Excellent | ❌ Violates DRY | ✅ Good |
| Print accuracy | 85-90% | 70-80% (diverges) | 90-95% |
| CSS isolation | Good (scoped) | Good | Excellent |
| Performance | ✅ Fast | Medium | Medium |
| Maintainability | ✅ High | ❌ Low | Medium |
| Security | ✅ Simple | Simple | Medium (sandbox) |
| RTL support | ✅ Inherited | Must reimplement | ✅ Inherited |
| A4 simulation | CSS-based | Custom | CSS-based |
| Multi-page | JS calculation | Custom | JS calculation |
| Future PDF compat | ✅ High | Low | ✅ High |

---

### **Recommendation: Option A (Browser-based Container Preview)**

**Why:**
1. Maximum architecture consistency — zero rendering duplication
2. Simplest implementation — wraps existing PrintEngine output
3. Best performance — no iframe overhead
4. Best maintainability — single source of truth for rendering
5. Adequate accuracy (85-90%) for preview purposes
6. Scales well to future PDF integration

**Trade-off acknowledgment:**
- Page break visualization will be approximate
- This is acceptable for preview; actual print reveals true pagination
- Users understand browser preview ≠ exact print output

---

## 5. Preview UX Design

### 5.1 Required for M8

| Feature | Description | Priority |
|---------|-------------|----------|
| A4 paper simulation | White paper container with shadow, correct aspect ratio | **MUST** |
| Portrait/Landscape switch | Toggle button to switch orientation | **MUST** |
| Page size indicator | Shows current page size (A4, Letter, etc.) | **MUST** |
| Column count display | Shows current column configuration | **MUST** |
| Print button | Triggers browser print dialog | **MUST** |
| Close/Back action | Returns to catalog edit | **MUST** |
| Loading state | Spinner while rendering | **MUST** |
| Error state | Graceful error message | **MUST** |
| Empty catalog state | Message when no products | **MUST** |

### 5.2 Nice-to-have (Future Milestones)

| Feature | Description | Priority |
|---------|-------------|----------|
| Zoom controls | 50%, 75%, 100%, 150% zoom | SHOULD |
| Page navigation | Navigate between pages | SHOULD |
| Page count | "Page 1 of N" display | SHOULD |
| Fullscreen mode | Expand preview to full window | SHOULD |
| Responsive scaling | Scale paper to fit viewport | SHOULD |
| Settings panel sidebar | Adjust settings live | SHOULD |
| Keyboard shortcuts | P for print, Esc to close | SHOULD |

### 5.3 Out of Scope

- PDF generation
- Drag-and-drop layout editing
- Template editing in preview
- Real-time collaborative preview

---

## 6. Integration Points

### 6.1 Admin Integration (Primary)

**Entry Point:** Catalog edit screen

**Implementation:**
1. Add "Preview" button to catalog edit screen (post.php?post=ID&action=edit)
2. Button opens modal or redirects to dedicated preview page
3. Preview renders the catalog using current settings

**Recommendation:** Dedicated preview page (not modal)

**Why:**
- Full-screen preview better for A4 visualization
- Browser print dialog works better from dedicated page
- Cleaner URL for sharing/linking
- Simpler implementation than modal

**URL Structure:**
```
/wp-admin/admin.php?page=catalogist-preview&catalog_id=123
```

### 6.2 Shortcode Integration (Secondary)

Existing shortcode already supports preview:
```
[catalogist id="123" print="1"]
```

This renders the same HTML. Preview page simply uses this mechanism.

### 6.3 REST API / AJAX

**Is REST/AJAX required?** **No.**

**Rationale:**
- Preview renders entire catalog at once
- No interactive data loading needed
- Direct page load is simpler and more reliable
- REST would add complexity without benefit

**Future consideration:**
- If live settings adjustment is implemented (SHOULD HAVE), AJAX may be needed
- Not required for M8 MUST HAVE features

### 6.4 Existing JavaScript Architecture

Current JS (`assets/js/admin.js`):
- Basic notice dismissal
- AJAX helper utility

**M8 JavaScript Requirements:**
- New preview module for:
  - Orientation toggle
  - Print trigger
  - Close/back action
  - Optional: zoom controls

**Implementation:** Vanilla JavaScript, no framework dependency

---

## 7. Page Simulation Strategy

### 7.1 The Core Challenge

Browser HTML rendering cannot perfectly reproduce browser print pagination because:
- Print pagination depends on browser print engine
- CSS `@page` rules are only hints
- Actual page breaks determined at print time
- Browser zoom, DPI, and paper settings affect output

### 7.2 Recommended Approach: CSS Approximation

**What CSS can simulate accurately:**
- ✅ A4 dimensions (210mm × 297mm)
- ✅ Portrait/landscape aspect ratio
- ✅ Margins (visualized as padding)
- ✅ Column layout (CSS multi-column)
- ✅ Cover page separation
- ✅ Header/footer positioning

**What CSS cannot simulate accurately:**
- ❌ Exact page break positions
- ❌ Exact page count
- ❌ Print-specific font rendering

### 7.3 Implementation

```css
/* Preview container simulating A4 */
.catalogist-preview-paper {
    width: 210mm;
    min-height: 297mm;
    padding: 20mm; /* Margins */
    background: white;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    margin: 20px auto;
}

.catalogist-preview-paper.landscape {
    width: 297mm;
    min-height: 210mm;
}

/* Preview wrapper for scaling */
.catalogist-preview-wrapper {
    transform-origin: top center;
    /* JavaScript applies scale() to fit viewport */
}
```

### 7.4 Multi-Page Handling

**M8 Approach:** Single continuous page

**Why:**
- Simpler implementation
- No JavaScript pagination calculation
- Users can scroll to see all content
- "Print" button reveals actual pagination

**Future Enhancement (SHOULD HAVE):**
- JavaScript-based page break detection
- Display as multiple stacked paper containers
- Approximate, not guaranteed accurate

---

## 8. CSS Architecture

### 8.1 Existing CSS

- `assets/css/print.css` — Print stylesheet with `@media print`

### 8.2 Recommended Strategy: Split + Extend

```
assets/css/
├── print.css          # @media print rules (existing)
├── preview.css        # Preview-specific chrome (NEW)
└── print-shared.css   # Shared base styles (NEW, extracted)
```

**Rationale:**
1. Extract non-`@media print` rules from print.css to print-shared.css
2. print.css imports print-shared.css and adds `@media print` wrapper
3. preview.css imports print-shared.css and adds preview chrome

**Alternative (Simpler for M8):**
- Keep print.css unchanged
- preview.css adds preview chrome only
- Both stylesheets apply; `@media print` gates print.css on screen

**Recommendation:** Alternative approach for M8 simplicity

### 8.3 Preview CSS Structure

```css
/* assets/css/preview.css */

/* Preview page layout */
.catalogist-preview-page {
    background: #f0f0f1;
    min-height: 100vh;
    padding: 40px 20px;
}

/* Paper simulation */
.catalogist-preview-paper {
    width: 210mm;
    min-height: 297mm;
    background: white;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    margin: 0 auto;
    padding: 20mm;
}

/* Preview controls */
.catalogist-preview-controls {
    position: fixed;
    top: 32px;
    right: 20px;
    background: white;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

/* Responsive scaling */
@media (max-width: 900px) {
    .catalogist-preview-paper {
        transform: scale(0.7);
        transform-origin: top center;
    }
}
```

---

## 9. JavaScript Architecture

### 9.1 M8 Requirements

| Feature | JS Required? | Complexity |
|---------|--------------|------------|
| Orientation toggle | Yes | Low |
| Print trigger | Yes | Low (`window.print()`) |
| Close/back | Yes | Low |
| Zoom controls | No (SHOULD HAVE) | Medium |
| Page navigation | No (SHOULD HAVE) | Medium |
| Responsive scaling | Yes | Low |

### 9.2 Proposed Structure

```javascript
/* assets/js/preview.js */

(function ($) {
    'use strict';

    var CatalogistPreview = {
        init: function () {
            this.orientation = 'portrait';
            this.bindEvents();
        },

        bindEvents: function () {
            $('#orientation-toggle').on('click', this.toggleOrientation.bind(this));
            $('#print-button').on('click', this.triggerPrint);
            $('#close-preview').on('click', this.closePreview);
        },

        toggleOrientation: function () {
            var $paper = $('.catalogist-preview-paper');
            $paper.toggleClass('landscape');
            this.orientation = (this.orientation === 'portrait') ? 'landscape' : 'portrait';
        },

        triggerPrint: function (e) {
            e.preventDefault();
            window.print();
        },

        closePreview: function (e) {
            e.preventDefault();
            window.history.back();
        }
    };

    $(document).ready(function () {
        CatalogistPreview.init();
    });

})(jQuery);
```

### 9.3 iframe Communication

**Not required for M8.** Direct page rendering is simpler.

---

## 10. Security Analysis

### 10.1 Capability Checks

| Action | Required Capability |
|--------|---------------------|
| View preview | `edit_posts` (same as viewing draft catalogs) |
| Print | Same as view preview |
| Access catalog data | Validated via CatalogRepository |

### 10.2 Nonce Requirements

| Action | Nonce Needed? |
|--------|---------------|
| View preview page | No (read-only display) |
| Print | No (browser action) |
| AJAX settings update | Yes (if implemented in future) |

### 10.3 XSS Risks

| Risk | Mitigation |
|------|------------|
| User-supplied catalog title | Already escaped in templates (`esc_html()`) |
| User-supplied content | Already escaped via `wp_kses_post()` |
| Print settings in URL | Sanitized via `sanitize_text_field()` |

### 10.4 Data Access

- Preview page validates `catalog_id` against user capabilities
- Draft/private catalogs require `edit_posts` capability (already implemented in shortcode)
- No additional security mechanisms needed

---

## 11. Performance Analysis

### 11.1 DOM Size Estimates

| Catalog Size | Product Cards | Est. DOM Nodes | Impact |
|--------------|---------------|----------------|--------|
| 10 products | 10 | ~500 | Negligible |
| 100 products | 100 | ~5,000 | Low |
| 500 products | 500 | ~25,000 | Medium |
| 1000 products | 1000 | ~50,000 | High |

### 11.2 Mitigation Strategies

| Strategy | Implementation | M8 Priority |
|----------|----------------|-------------|
| Lazy load images | `loading="lazy"` attribute | **MUST** |
| Render on page load | Standard request | **MUST** |
| Virtual scrolling | Not needed for preview | Future |
| Pagination | Single-page preview is acceptable | N/A |

### 11.3 Image Loading

- Use `loading="lazy"` for product images
- Preview shows images as they load
- Print dialog ensures all images loaded

### 11.4 Caching

- No special caching needed for M8
- Existing WordPress object cache applies
- PrintEngine already efficient (single render pass)

---

## 12. Mobile / Responsive Behavior

### 12.1 Design Principle

**Paper remains A4. Viewport scales the paper.**

The actual print paper does not change size. The preview scales to fit the viewport.

### 12.2 Breakpoints

| Viewport | Behavior |
|----------|----------|
| Desktop (>1200px) | Full A4 at 100%, centered |
| Tablet (768-1200px) | A4 scaled to 70%, centered |
| Mobile (<768px) | A4 scaled to 50%, or message: "Preview best viewed on desktop" |

### 12.3 Touch Support

| Feature | Mobile Support |
|---------|----------------|
| Orientation toggle | Yes |
| Print button | Yes (triggers print dialog) |
| Zoom/pinch | Browser default |

---

## 13. Browser Compatibility

### 13.1 CSS Print Rules

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| `@page` size | ✅ | ✅ | ⚠️ Partial | ✅ |
| `@page` margin | ✅ | ✅ | ✅ | ✅ |
| `break-inside` | ✅ | ✅ | ⚠️ `-webkit-break-inside` | ✅ |
| `page-break-inside` | ✅ | ✅ | ✅ | ✅ |
| CSS columns | ✅ | ✅ | ✅ | ✅ |

### 13.2 Preview Accuracy vs. Print Accuracy

| Aspect | Preview Accuracy | Actual Print Accuracy |
|--------|------------------|----------------------|
| A4 dimensions | 100% (CSS controlled) | 100% |
| Margins | 100% | 100% |
| Columns | 95% | 95-100% |
| Page breaks | 70-80% (approximate) | 100% (browser calculated) |
| Fonts | 90% | 100% (print engine) |

**User expectation management:**
- UI should state: "Preview approximates print output. Actual pagination may differ."
- This is standard behavior for web-based print previews

---

## 14. Architecture Boundaries

### 14.1 Must Preserve

| Boundary | Requirement |
|----------|-------------|
| Elementor optional | Preview works without Elementor |
| Template Engine authority | Preview uses TemplateEngine output, never bypasses |
| Print Engine authority | Preview wraps PrintEngine output, never replaces |
| No PDF dependency | M8 is HTML + CSS only |
| No duplicated product retrieval | Preview uses existing CatalogProcessor pipeline |
| No business logic in templates | Maintained via existing template structure |

### 14.2 Dependencies

| Component | Dependency | Status |
|-----------|------------|--------|
| PreviewEngine | PrintEngineInterface | New |
| Preview page | WordPress admin APIs | Existing |
| Preview JS | jQuery (optional) | Existing in admin |
| Preview CSS | print.css | Existing |

---

## 15. Proposed File Structure

### 15.1 New Files

| File | Responsibility | Why Needed |
|------|----------------|------------|
| `src/Preview/PreviewEngine.php` | Orchestrates preview rendering, wraps PrintEngine | Single entry point for preview logic |
| `src/Preview/PreviewEngineInterface.php` | Interface for preview engine | Dependency inversion, testability |
| `src/Preview/PreviewServiceProvider.php` | Container registration | Follows existing pattern |
| `src/Admin/PreviewPage.php` | Renders admin preview page | Admin UI integration |
| `assets/css/preview.css` | Preview chrome styles | A4 simulation, controls UI |
| `assets/js/preview.js` | Preview interactions | Orientation toggle, print, close |
| `tests/Unit/Preview/PreviewEngineTest.php` | Unit tests | Verify preview generation |

### 15.2 Modified Files

| File | Changes | Why |
|------|---------|-----|
| `src/Admin/AdminServiceProvider.php` | Register PreviewPage | Wire preview into admin |
| `src/Admin/Menu.php` | Add preview submenu or keep hidden | Entry point |
| `src/Core/Assets.php` | Enqueue preview.css, preview.js | Asset loading |
| `src/Core/Plugin.php` | Register PreviewServiceProvider | Container wiring |

### 15.3 File Count

- **New files:** 7
- **Modified files:** 4
- **Total changes:** 11 files

---

## 16. Dependencies

### 16.1 New Dependencies Required?

**No.**

### 16.2 Rationale

| Requirement | Solution |
|-------------|----------|
| A4 simulation | CSS (no library) |
| Orientation toggle | Vanilla JS or jQuery (existing) |
| Print trigger | `window.print()` (native) |
| Preview rendering | PrintEngine (existing) |
| Admin UI | WordPress APIs (existing) |

### 16.3 Third-Party Libraries

**None recommended.** The scope is achievable with vanilla JavaScript and CSS.

---

## 17. Testing Strategy

### 17.1 Unit Tests

| Test | Description |
|------|-------------|
| `PreviewEngine::renderPreview()` | Returns HTML with wrapper |
| `PreviewEngine::generatePreviewURL()` | Returns correct admin URL |
| Interface compliance | PreviewEngine implements PreviewEngineInterface |
| Dependency injection | PreviewEngine receives PrintEngineInterface |

### 17.2 Integration Tests

| Test | Description |
|------|-------------|
| Preview page access | Requires `edit_posts` capability |
| Preview renders catalog | Valid catalog ID produces HTML |
| Preview with settings | Orientation, columns override correctly |
| Empty catalog | Graceful message displayed |
| Non-existent catalog | Error message displayed |

### 17.3 Browser/Manual Tests

| Test | Description | Priority |
|------|-------------|----------|
| A4 portrait | Preview displays correct dimensions | **MUST** |
| A4 landscape | Orientation toggle works | **MUST** |
| 1-4 columns | Column layouts display correctly | **MUST** |
| Margins | Margins visible as padding | **MUST** |
| Cover page | Cover renders before catalog | **MUST** |
| Multi-page catalog | Scroll to see all content | **MUST** |
| RTL | RTL layout displays correctly | **MUST** |
| 100+ products | Performance acceptable | **MUST** |
| Print button | Triggers browser print dialog | **MUST** |
| Close button | Returns to previous page | **MUST** |
| Responsive scaling | Mobile displays scaled preview | **MUST** |
| Empty catalog | Message displayed | **MUST** |
| Error state | Error message for invalid catalog | **MUST** |

---

## 18. Risks

### 18.1 Risk Matrix

| Risk | Probability | Impact | Level | Mitigation |
|------|-------------|--------|-------|------------|
| Preview pagination ≠ actual print | High | Medium | **Medium** | User expectation management: "Preview approximates print" |
| Large catalog performance | Medium | Medium | **Medium** | Lazy load images; acceptable for preview use case |
| Browser CSS differences | Medium | Low | **Low** | Test on major browsers; graceful degradation |
| Mobile preview unusable | Low | Low | **Low** | Desktop recommended message; scaling fallback |
| Security bypass via preview URL | Low | High | **Medium** | Capability checks on preview page access |
| CSS conflicts with admin theme | Medium | Low | **Low** | Scoped CSS class prefix `.catalogist-preview-` |

### 18.2 Highest Risk: Page Break Divergence

**Risk:** Preview pagination does not match actual browser print pagination.

**Probability:** High (inherent to web rendering)

**Impact:** Medium (users expect accuracy)

**Mitigation:**
1. Clear UI messaging: "Preview approximates print output. Use Print button for accurate pagination."
2. This is standard behavior for web print previews (Google Docs, etc.)
3. Users understand browser preview ≠ PDF proof

**Verdict:** Acceptable risk. Perfect pagination requires PDF generation (M9/M10).

---

## 19. Milestone 8 Scope

### 19.1 MUST HAVE

1. ✅ A4 paper simulation (CSS container with correct dimensions)
2. ✅ Portrait/landscape orientation toggle
3. ✅ Print button (triggers browser print dialog)
4. ✅ Close/back button
5. ✅ Loading state
6. ✅ Error state (invalid catalog, empty catalog)
7. ✅ Preview page accessible from admin
8. ✅ Capability checks for preview access
9. ✅ RTL support (inherited from templates)
10. ✅ Basic responsive scaling

### 19.2 SHOULD HAVE (Future)

1. Zoom controls (50%, 75%, 100%, 150%)
2. Page navigation (scroll indicator)
3. Page count display
4. Fullscreen mode
5. Settings panel sidebar (live adjustment)
6. Keyboard shortcuts (P for print, Esc to close)

### 19.3 OUT OF SCOPE

| Feature | Reason |
|---------|--------|
| PDF generation | M9/M10 scope |
| PDF libraries | M9/M10 scope |
| Advanced visual editor | Future consideration |
| Drag-and-drop layout editing | Future consideration |
| Template builder redesign | Out of scope |
| Major Template Engine refactor | Architecture constraint |
| Security/performance audit | M10 scope |
| REST API for preview | Not needed for M8 |

---

## 20. Final Recommendation

### 20.1 Recommended Architecture

**Option A: Browser-based Container Preview**

```
Admin → Preview Page → PreviewEngine → PrintEngine → HTML
                                     ↓
                              CSS Wrapper (A4 simulation)
                                     ↓
                              Rendered Preview
```

### 20.2 Why Preferred

1. **Architecture consistency:** Zero rendering duplication
2. **Simplicity:** Wraps existing PrintEngine output
3. **Performance:** No iframe overhead
4. **Maintainability:** Single source of truth
5. **Future-ready:** Scales to PDF integration

### 20.3 Integration Point

**Dedicated admin preview page:**
```
/wp-admin/admin.php?page=catalogist-preview&catalog_id=123
```

Accessed via "Preview" button on catalog edit screen.

### 20.4 Files Expected to Change

**New (7 files):**
- `src/Preview/PreviewEngineInterface.php`
- `src/Preview/PreviewEngine.php`
- `src/Preview/PreviewServiceProvider.php`
- `src/Admin/PreviewPage.php`
- `assets/css/preview.css`
- `assets/js/preview.js`
- `tests/Unit/Preview/PreviewEngineTest.php`

**Modified (4 files):**
- `src/Admin/AdminServiceProvider.php`
- `src/Admin/Menu.php`
- `src/Core/Assets.php`
- `src/Core/Plugin.php`

### 20.5 Dependencies

**None.** All functionality achievable with:
- WordPress admin APIs
- Vanilla JavaScript (or jQuery, already in admin)
- CSS
- Existing PrintEngine

### 20.6 Main Risks

1. **Preview pagination ≠ actual print** (Medium) — Acceptable, mitigated by messaging
2. **Large catalog performance** (Medium) — Mitigated by lazy loading
3. **Mobile usability** (Low) — Desktop recommended, scaling fallback

### 20.7 Testing Plan

1. **Unit tests:** PreviewEngine interface compliance, URL generation
2. **Integration tests:** Capability checks, catalog rendering
3. **Manual tests:** 13 test cases covering orientation, columns, RTL, print action, responsive behavior

### 20.8 Definition of Done

- [ ] PreviewEngine implements PreviewEngineInterface
- [ ] Preview page renders A4-simulated output
- [ ] Orientation toggle works (portrait ↔ landscape)
- [ ] Print button triggers browser print dialog
- [ ] Close button returns to catalog edit
- [ ] Capability checks prevent unauthorized access
- [ ] Error states handled gracefully
- [ ] RTL support verified
- [ ] Responsive scaling implemented
- [ ] All unit tests pass
- [ ] Manual browser testing complete on Chrome, Firefox, Safari, Edge
- [ ] No PHP syntax errors
- [ ] No security regressions

### 20.9 Recommended Implementation Order

1. Create `PreviewEngineInterface` and `PreviewEngine` (wraps PrintEngine)
2. Create `PreviewServiceProvider` for container registration
3. Create `PreviewPage` admin class
4. Wire into `AdminServiceProvider` and `Menu`
5. Create `preview.css` for A4 simulation
6. Create `preview.js` for interactions
7. Enqueue assets in `Assets.php`
8. Write unit tests
9. Manual browser testing
10. Create post-milestone report

---

## 21. Readiness Assessment

| Criterion | Status |
|-----------|--------|
| Architecture defined | ✅ Complete |
| Files identified | ✅ Complete |
| Dependencies verified | ✅ No new dependencies |
| Risks assessed | ✅ Medium risk acceptable |
| Scope bounded | ✅ MUST HAVE defined |
| Testing plan ready | ✅ Complete |
| Integration point clear | ✅ Admin preview page |

**Project Status:** Ready for implementation upon explicit user approval.

---

**End of Pre-Milestone 8 Report**
