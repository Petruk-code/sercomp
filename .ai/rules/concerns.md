---
paths:
  - app/Concerns/BelongsToStore.php
---

# Concerns

## Trait BelongsToStore: scope + auto-fill store_id
Model multi-cabang memakai trait App\Concerns\BelongsToStore (global scope 'store' + hook creating auto-isi store_id dari StoreContext). Konteks null = scope nonaktif — jangan ubah perilaku ini; command terjadwal, halaman publik tracking, dan test lama bergantung padanya. Tabel anak (sale_items dll.) TIDAK punya store_id — ikut induknya; purchase_returns di-scope lewat relasi purchase. Categories & invoice_sequences tetap global. Migrasi menyisipkan store default "Cabang Utama" sehingga setiap test RefreshDatabase selalu punya 1 store bawaan.
