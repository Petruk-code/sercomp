---
paths:
  - 'app/Providers/**'
---

# Providers

## Error page Inertia via handleExceptionsUsing
Daftarkan Inertia::handleExceptionsUsing di AppServiceProvider::boot() dan render component 'error' (file resources/js/pages/error.tsx) dengan prop status untuk kode [403,404,419,429,500,503], lalu ->withSharedData(). Di app.tsx, layout 'error' dikembalikan null agar halaman standalone (tidak butuh auth). Ini membuat abort(403) & 404 merender halaman Inertia, bukan HTML.
