---
description: Layout Padding Guidelines for Mobile Consistency
---

# Layout Padding Consistency

When building Vue pages (like `Show.vue` or `Index.vue`) using the `AuthenticatedLayout`, **DO NOT** add explicit horizontal padding (e.g., `px-4 sm:px-6 lg:px-8`) to the outermost wrapper `div` of the page content.

## Why?
`AuthenticatedLayout.vue` already provides a global wrapper with padding:
```vue
<main>
    <div class="px-2 py-4 sm:p-6">
        <slot />
    </div>
</main>
```

Adding `px-4` inside the page template results in doubled padding on mobile screens (`px-2` + `px-4`), which makes the content margins inconsistent with pages that don't add the extra padding (like `Index.vue`).

## Correct Example
```vue
<div class="max-w-7xl mx-auto space-y-6">
    <!-- content -->
</div>
```

## Incorrect Example (Do Not Use)
```vue
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- content -->
</div>
```
