## Architecture

The package declares no dependency but PHP itself, and that is its whole point: it is where file code lands once it stops needing a framework. Anything reaching for a container, an entity manager or a Twig environment belongs in ../symfony-file instead — the rule is easy to check, since adding such a thing to composer.json is the moment it breaks.

Three kinds, each in its own directory under `src/`, as the suite's PHP design rules ask:

- `Class/` — the things built and held. src/Class/FileSystemItemScanner.php carries a root, src/Class/PathMatcher.php carries parsed rules.
- `Enum/` — src/Enum/FileSystemItemType.php, the three kinds of entry.
- `Helper/` — src/Helper/FileSizeHelper.php, static and stateless.

A file moved between those directories has its namespace moved with it, or PSR-4 stops finding it.

### What is answered, and what is not

src/Class/FileSystemItemScanner.php answers plain arrays keyed by its own `KEY_*` constants, never rows and never objects. That is deliberate: an index, a listing and a checksum want the same stat and disagree on everything else, so the shape is left to whoever asked. `symfony-file` turns those arrays into a Doctrine entity; a script would print them.

Containment is resolved rather than forbidden: `toContainedAbsolutePath()` runs `realpath()` on the root plus the relative path and answers null for anything landing outside. Reading *into* a symlink leading out is refused by that; describing the link itself is not, since it belongs to the directory holding it.

src/Class/PathMatcher.php exists here rather than in a front end because the same vocabulary has to answer two questions — what a tree draws, and what a mass action applies to. Two implementations of a syntax with this many corners is two implementations that disagree.

### Consumers

../symfony-file and ../symfony-helpers both depend on this package. The second only for src/Helper/FileSizeHelper.php, behind two deprecated delegations kept so that callers of `FileHelper::formatBytes()` and `FileHelper::convertToBytes()` keep working while they move over.
