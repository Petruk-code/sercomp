---
paths:
  - 'app/Support/CsvStream.php,app/Support/CsvImporter.php'
---

# Support Support

## CSV: streamDownload + snake_case header keys + formula escaping
CSV downloads must go through CsvStream::export() which uses response()->streamDownload (Symfony StreamedResponse has no ->header()), emits a UTF-8 BOM, and prefixes =/+/-/@ cells with ' to block formula injection. CsvStream::rows() normalizes CSV headers to snake_case (lowercase, spaces->underscores, strip non-word chars) so normalize fns read harga_beli/contact_person keys; "Harga Beli" would become hargabeli if you strip chars before replacing spaces. Imports use CsvImporter::run(model, normalize, findExisting) which streams line-by-line and skips invalid rows. Existing export/import routes must be declared BEFORE their Route::resource() sibling or {product}/{customer}/{supplier} swallows them.
