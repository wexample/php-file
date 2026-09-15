# php-file

Version: 1.1.0

`wexample/php-file` holds the file handling that owes nothing to a framework: reading a tree off the disk, deciding which paths a list of patterns takes, and putting a size into words. It requires PHP alone, so a command-line script, a worker or a Symfony bundle can all reach for it — ../symfony-file is the Symfony wiring built on top of this one.

## Installation

```bash
composer require wexample/php-file
```

## Reading a tree

src/Class/FileSystemItemScanner.php is built on a root and confined to it: a path leading outside, by `..` or through a symlink, comes back as nothing rather than as an error. It reads one level at a time, because a directory holding `node_modules` or `.git` is not something a caller can afford to receive whole.

```php
use Wexample\PhpFile\Class\FileSystemItemScanner;

$scanner = new FileSystemItemScanner('/srv/project');

foreach ($scanner->scanLevel('src') as $item) {
    echo $item[FileSystemItemScanner::KEY_NAME], ' ',
        $item[FileSystemItemScanner::KEY_TYPE]->value, PHP_EOL;
}

$one = $scanner->scanOne('src/Kernel.php'); // null outside the root
```

Each entry is a plain array keyed by the `KEY_*` constants — path, name, type, whether it has children, size, modification time and permissions. What becomes of it, a row or a listing or a checksum, is the caller's business.

src/Enum/FileSystemItemType.php is the `type` it carries: `FILE`, `DIRECTORY` or `LINK`. A link is told apart from what it points at, so a broken one still describes.

## Matching paths

src/Class/PathMatcher.php reads the vocabulary of a `.gitignore`: rules in order, the last one to speak wins, `!` gives back what the lines above took, `**` crosses directories where `*` stops at one.

```php
use Wexample\PhpFile\Class\PathMatcher;

$matcher = new PathMatcher(['src/**', '!src/vendor/', '*.md']);

$matcher->matches('src/Kernel.php');   // true
$matcher->matches('other/thing.php');  // false
$matcher->couldMatchUnder('src');      // true
```

Nothing said about a path is nothing taken: a list of rules is a list of what to keep. `couldMatchUnder()` answers off the patterns and never off the disk, which is what lets a tree skip a branch it has never opened.

## Sizes

src/Helper/FileSizeHelper.php goes both ways, and the two directions are kept together so a multiplier can never be changed on one side alone.

```php
use Wexample\PhpFile\Helper\FileSizeHelper;

FileSizeHelper::format(1536);    // '1.5 KB'
FileSizeHelper::parse('10MB');   // 10485760
FileSizeHelper::parse('10 Mo');  // 10485760
FileSizeHelper::parse('nonsense'); // null
```

Units are binary — a `KB` is 1024 — and read in French as well as English, since both spellings turn up in the configuration files this parses.

## Table of Contents

- [Installation](#installation)
- [Reading a tree](#reading-a-tree)
- [Matching paths](#matching-paths)
- [Sizes](#sizes)
- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

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

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
