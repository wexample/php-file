# Sapiens — an encrypted zip with a generated password

Opened: 2026-10-01
Updated: 2026-10-01
Author: agent:sapiens

## Context

Asked by the Sapiens app (`HOME_HABILIS/local/sapiens`). Sapiens exports personal health data as a CSV inside a zip encrypted with AES-256, opened with a random password shown once to the user. The code lives in `src/Service/PatientExportService.php`. Only the packing is generic: plain PHP, no Symfony. Handing the file over is `symfony-file`'s part (brief `sapiens-one-time-download.md` there).

## Expected

- From one or several entries (a name and a content, or a source path), build a zip in which **every entry** is encrypted with `ZipArchive::EM_AES_256`, written to a given path or returned as a string.
- A **password generator**: configurable length, defaulting to 20 characters, from an alphabet without ambiguous characters (no `0/O`, `1/l/I`), drawn with `random_int`. It returns the password and never stores it. Sapiens uses `ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789`, about 110 bits.
- **Availability check**: the `zip` extension with AES support (`defined('ZipArchive::EM_AES_256')`). Without it, a named exception, never a fallback to an unencrypted zip. Sapiens hit this: its image had no `zip` extension.
- The password never appears in an exception message.

## Tests

- The archive cannot be read without the password, and every entry can be read with it.
- Several entries are all encrypted.
- Without AES support, the named exception is thrown and no file is written.
- Generated passwords have the requested length and alphabet, and differ from one call to the next.
