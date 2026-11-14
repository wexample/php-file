<?php

declare(strict_types=1);

namespace Wexample\PhpFile\Tests\Unit\Class;

use PHPUnit\Framework\TestCase;
use Wexample\PhpFile\Class\PathMatcher;

/**
 * The `.gitignore` vocabulary, corner by corner: a name at any depth, `**`
 * across directories, `/` anchoring, `/` at the end for directories alone,
 * `!` giving back, the last rule winning, and what a branch could still hold.
 */
class PathMatcherTest extends TestCase
{
    public function testARuleWithoutSlashNamesAnythingAtAnyDepth(): void
    {
        $matcher = new PathMatcher(['*.php']);

        $this->assertTrue($matcher->matches('index.php'));
        $this->assertTrue($matcher->matches('src/Class/A.php'));
        $this->assertFalse($matcher->matches('README.md'));
        $this->assertFalse($matcher->matches('src/php'));
    }

    public function testAStarStopsAtASegmentAndAQuestionMarkIsOneCharacter(): void
    {
        $matcher = new PathMatcher(['/src/*.php', 'file?.txt']);

        $this->assertTrue($matcher->matches('src/A.php'));
        $this->assertFalse($matcher->matches('src/Class/A.php'));
        $this->assertTrue($matcher->matches('docs/file1.txt'));
        $this->assertFalse($matcher->matches('docs/file10.txt'));
        $this->assertFalse($matcher->matches('docs/file.txt'));
    }

    public function testOtherCharactersAreReadLiterally(): void
    {
        $matcher = new PathMatcher(['a+b.(1).txt']);

        $this->assertTrue($matcher->matches('a+b.(1).txt'));
        $this->assertFalse($matcher->matches('aab.(1).txt'));
        $this->assertFalse($matcher->matches('a+bx(1).txt'));
    }

    public function testDoubleStarCrossesDirectories(): void
    {
        $matcher = new PathMatcher(['docs/**/*.md']);

        $this->assertTrue($matcher->matches('docs/a.md'));
        $this->assertTrue($matcher->matches('docs/x/y/a.md'));
        $this->assertFalse($matcher->matches('other/a.md'));
        $this->assertFalse($matcher->matches('docs/a.txt'));
    }

    public function testATrailingDoubleStarTakesWhatIsInsideAndNotTheDirectory(): void
    {
        $matcher = new PathMatcher(['src/**']);

        $this->assertFalse($matcher->matches('src', true));
        $this->assertTrue($matcher->matches('src/a.php'));
        $this->assertTrue($matcher->matches('src/deep/er/a.php'));
    }

    public function testALeadingSlashAnchorsToTheRoot(): void
    {
        $anchored = new PathMatcher(['/build']);
        $anywhere = new PathMatcher(['build']);

        $this->assertTrue($anchored->matches('build', true));
        $this->assertFalse($anchored->matches('src/build', true));
        $this->assertTrue($anywhere->matches('src/build', true));
    }

    public function testATrailingSlashIsAboutDirectoriesAlone(): void
    {
        $matcher = new PathMatcher(['cache/']);

        $this->assertTrue($matcher->matches('cache', true));
        $this->assertTrue($matcher->matches('var/cache', true));
        $this->assertFalse($matcher->matches('cache'));
    }

    public function testNegationGivesBackAndTheLastRuleWins(): void
    {
        $matcher = new PathMatcher(['src/**', '!src/vendor/**', '!*.lock']);

        $this->assertTrue($matcher->matches('src/a.php'));
        $this->assertFalse($matcher->matches('src/vendor/lib/x.php'));
        $this->assertFalse($matcher->matches('src/composer.lock'));

        $this->assertTrue((new PathMatcher(['!a.txt', 'a.txt']))->matches('a.txt'));
        $this->assertFalse((new PathMatcher(['a.txt', '!a.txt']))->matches('a.txt'));
    }

    public function testCommentsAndBlankLinesSayNothing(): void
    {
        $matcher = new PathMatcher(['# *.php', '', '   ', '/', '!']);

        $this->assertFalse($matcher->matches('index.php'));
        $this->assertFalse($matcher->matches('# *.php'));
        $this->assertFalse($matcher->couldMatchUnder(''));
    }

    public function testNothingNamedIsNothingTaken(): void
    {
        $this->assertFalse((new PathMatcher([]))->matches('anything'));
    }

    public function testCouldMatchUnderFollowsWhatTheRulesStillHold(): void
    {
        $matcher = new PathMatcher(['/src/app/*.php']);

        $this->assertTrue($matcher->couldMatchUnder(''));
        $this->assertTrue($matcher->couldMatchUnder('src'));
        $this->assertTrue($matcher->couldMatchUnder('src/app'));
        $this->assertFalse($matcher->couldMatchUnder('src/app/deep'));
        $this->assertFalse($matcher->couldMatchUnder('lib'));
    }

    public function testCouldMatchUnderIsClosedByANegationCoveringTheWholeBranch(): void
    {
        $matcher = new PathMatcher(['src/**', '!src/vendor/**', '!src/app/*.lock']);

        $this->assertTrue($matcher->couldMatchUnder('src'));
        $this->assertFalse($matcher->couldMatchUnder('src/vendor'));
        $this->assertFalse($matcher->couldMatchUnder('src/vendor/lib'));
        // Only part of the branch is given back: the rest may still be taken.
        $this->assertTrue($matcher->couldMatchUnder('src/app'));
    }

    public function testANameAtAnyDepthKeepsEveryBranchOpen(): void
    {
        $matcher = new PathMatcher(['*.md']);

        $this->assertTrue($matcher->couldMatchUnder('any/where/at/all'));
    }

    public function testASameSegmentInSeveralRulesReadsTheSame(): void
    {
        $matcher = new PathMatcher(['/docs/*.md', 'notes/*.md', '!docs/draft.md']);

        $this->assertTrue($matcher->matches('docs/a.md'));
        $this->assertTrue($matcher->matches('notes/b.md'));
        $this->assertFalse($matcher->matches('docs/draft.md'));
        $this->assertFalse($matcher->matches('notes/b.txt'));
    }
}
