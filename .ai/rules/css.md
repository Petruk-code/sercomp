---
paths:
  - 'resources/css/**'
---

# Css

## Indigo/Violet brand design tokens in app.css only
Brand identity lives entirely in `resources/css/app.css` as shadcn/Tailwind v4 design tokens (oklch). Primary/ring/charts/sidebar-primary use the Indigo/Violet brand (`oklch(0.511 0.262 276.966)` light, `oklch(0.673 0.182 276.935)` dark). Never hardcode brand colors in components or pages — always reference tokens (`bg-primary`, `text-muted-foreground`, etc.) so dark mode and theme changes keep working. Keep neutral backgrounds/cards so brand pops. `--radius` is 0.75rem.
