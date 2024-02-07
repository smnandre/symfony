#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\VarExporter\VarExporter;

Builder::cleanTarget();
Builder::buildEmojiList();
Builder::buildEmojiGroups();
$emojisCodePoints = Builder::getEmojisCodePoints();
Builder::saveRules(Builder::buildRules($emojisCodePoints));
Builder::saveRules(Builder::buildStripRules($emojisCodePoints));
Builder::saveRules(Builder::buildGitHubRules($emojisCodePoints));
Builder::saveRules(Builder::buildSlackRules($emojisCodePoints));

final class Builder
{
    private const TARGET_DIR = __DIR__.'/../data/';

    /**
     * Fetches the emojis from the latest `emoji-test.txt` file.
     *
     * Per default:
     *  - all emojis are included (including 'minimally-qualified' and 'unqualified' ones)
     *  - every emoji containing a 'Zero Width Joiner' is also included without.
     *
     * When $rgiStrict is set to true, only the "Recommended for General Interchange" emojis are included.
     */
    public static function getEmojisCodePoints(bool $rgiStrict = false, bool $grouped = false): array
    {
        $lines = file(__DIR__.'/vendor/emoji-test.txt');

        $group = null;
        $subgroup = null;
        $emojisCodePoints = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '# group: ')) {
                $group = substr($line, 9);
                $subgroup = null;
            }
            if (str_starts_with($line, '# subgroup: ')) {
                $subgroup = substr($line, 12);
            }
            if (!$line || str_starts_with($line, '#')) {
                continue;
            }

            // 263A FE0F    ; fully-qualified     # ☺️ E0.6 smiling face
            preg_match('{^(?<codePoints>[\w ]+) +; (?<status>[\w-]+) +# (?<emoji>.+) E\d+\.\d+ ?(?<name>.+)$}Uu', $line, $matches);
            if (!$matches) {
                throw new DomainException("Could not parse line: \"$line\".");
            }
            if ($rgiStrict && 'fully-qualified' !== $matches['status']) {
                continue;
            }

            $codePoints = strtolower(trim($matches['codePoints']));
            if ($grouped) {
                $emojisCodePoints[$group][$subgroup][$codePoints] = $matches['emoji'];
            } else {
                $emojisCodePoints[$codePoints] = $matches['emoji'];
            }
            if ($rgiStrict) {
                continue;
            }

            // We also add a version without the "Zero Width Joiner"
            $codePoints = str_replace('200d ', '', $codePoints);
            if ($grouped) {
                $emojisCodePoints[$group][$subgroup][$codePoints] = $matches['emoji'];
            } else {
                $emojisCodePoints[$codePoints] = $matches['emoji'];
            }
        }

        return $emojisCodePoints;
    }

    public static function buildRules(array $emojisCodePoints): Generator
    {
        $files = (new Finder())
            ->files()
            ->in([
                __DIR__.'/vendor/unicode-org/cldr/common/annotationsDerived',
                __DIR__.'/vendor/unicode-org/cldr/common/annotations',
            ])
            ->name('*.xml');

        $ignored = [];
        $mapsByLocale = [];

        foreach ($files as $file) {
            $locale = $file->getBasename('.xml');

            $mapsByLocale[$locale] ??= [];

            $document = new DOMDocument();
            $document->loadXML(file_get_contents($file));
            $xpath = new DOMXPath($document);
            $results = $xpath->query('.//annotation[@type="tts"]');

            foreach ($results as $result) {
                $emoji = $result->getAttribute('cp');
                $name = $result->textContent;
                // Ignoring the hierarchical metadata instructions
                // (real value will be filled by the parent locale)
                if (str_contains($name, '↑↑')) {
                    continue;
                }
                $parts = preg_split('//u', $emoji, -1, \PREG_SPLIT_NO_EMPTY);
                $emojiCodePoints = implode(' ', array_map('dechex', array_map('mb_ord', $parts)));
                if (!array_key_exists($emojiCodePoints, $emojisCodePoints)) {
                    $ignored[] = [
                        'locale' => $locale,
                        'emoji' => $emoji,
                        'name' => $name,
                    ];
                    continue;
                }
                if (!self::testEmoji($emoji, $locale, $emojiCodePoints)) {
                    continue;
                }
                $codePointsCount = mb_strlen($emoji);
                $mapsByLocale[$locale][$codePointsCount][$emoji] = $name;
            }
        }

        ksort($mapsByLocale);

        foreach ($mapsByLocale as $locale => $localeMaps) {
            $parentLocale = $locale;

            while (false !== $i = strrpos($parentLocale, '_')) {
                $parentLocale = substr($parentLocale, 0, $i);
                $parentMaps = $mapsByLocale[$parentLocale] ?? [];
                foreach ($parentMaps as $codePointsCount => $parentMap) {
                    // Ensuring the result map contains all the emojis from the parent map
                    // if not already defined by the current locale
                    $localeMaps[$codePointsCount] = [...$parentMap, ...$localeMaps[$codePointsCount] ?? []];
                }
            }

            // Skip locales without any emoji
            if ($localeRules = self::createRules($localeMaps)) {
                yield strtolower("emoji-$locale") => $localeRules;
            }
        }
    }

    public static function buildGitHubRules(array $emojisCodePoints): iterable
    {
        $emojis = json_decode(file_get_contents(__DIR__.'/vendor/github-emojis.json'), true);

        $ignored = [];
        $maps = [];

        foreach ($emojis as $shortCode => $url) {
            $emojiCodePoints = str_replace('-', ' ', strtolower(basename(parse_url($url, \PHP_URL_PATH), '.png')));
            if (!array_key_exists($emojiCodePoints, $emojisCodePoints)) {
                $ignored[] = [
                    'emojiCodePoints' => $emojiCodePoints,
                    'shortCode' => $shortCode,
                ];
                continue;
            }
            $emoji = $emojisCodePoints[$emojiCodePoints];
            if (!self::testEmoji($emoji, 'github', $emojiCodePoints)) {
                continue;
            }
            $codePointsCount = mb_strlen($emoji);
            $maps[$codePointsCount][$emoji] = ":$shortCode:";
        }

        $maps = self::createRules($maps);

        return ['emoji-github' => $maps, 'github-emoji' => array_flip($maps)];
    }

    public static function buildSlackRules(array $emojisCodePoints): iterable
    {
        $emojis = json_decode(file_get_contents(__DIR__.'/vendor/slack-emojis.json'), true);

        $ignored = [];
        $emojiSlackMaps = [];
        $slackEmojiMaps = [];

        foreach ($emojis as $data) {
            $emojiCodePoints = str_replace('-', ' ', strtolower($data['unified']));
            $shortCode = $data['short_name'];
            $shortCodes = $data['short_names'];
            $shortCodes = array_map(fn($v) => ":$v:", $shortCodes);

            if (!array_key_exists($emojiCodePoints, $emojisCodePoints)) {
                $ignored[] = [
                    'emojiCodePoints' => $emojiCodePoints,
                    'shortCode' => $shortCode,
                ];
                continue;
            }
            $emoji = $emojisCodePoints[$emojiCodePoints];
            if (!self::testEmoji($emoji, 'slack', $emojiCodePoints)) {
                continue;
            }
            $codePointsCount = mb_strlen($emoji);
            $emojiSlackMaps[$codePointsCount][$emoji] = ":$shortCode:";
            foreach ($shortCodes as $short_name) {
                $slackEmojiMaps[$codePointsCount][$short_name] = $emoji;
            }
        }

        return ['emoji-slack' => self::createRules($emojiSlackMaps), 'slack-emoji' => self::createRules($slackEmojiMaps)];
    }

    public static function buildStripRules(array $emojisCodePoints): iterable
    {
        $maps = [];
        foreach ($emojisCodePoints as $codePoints => $emoji) {
            if (!self::testEmoji($emoji, 'strip', $codePoints)) {
                continue;
            }
            $codePointsCount = mb_strlen($emoji);
            $maps[$codePointsCount][$emoji] = '';
        }

        return ['emoji-strip' => self::createRules($maps)];
    }

    public static function cleanTarget(): void
    {
        $fs = new Filesystem();
        $fs->remove(self::TARGET_DIR);
        $fs->mkdir(self::TARGET_DIR);
    }

    public static function buildEmojiList(): void
    {
        $emojis = array_values(self::getEmojisCodePoints(true,));
        file_put_contents(self::TARGET_DIR."/emojis.php", "<?php\n\nreturn ".VarExporter::export($emojis).";\n");
    }

    public static function buildEmojiGroups(): void
    {
        $emojiGroups = self::getEmojisCodePoints(true, true);
        $emojiGroups = array_map(fn($v) => array_map(fn($v) => array_values($v), $v), $emojiGroups);
        file_put_contents(self::TARGET_DIR.'/emoji_groups.php', "<?php\n\nreturn ".VarExporter::export($emojiGroups).";\n");
    }

    public static function saveRules(iterable $rulesByLocale): void
    {
        $firstChars = [];
        foreach ($rulesByLocale as $filename => $rules) {
            file_put_contents(self::TARGET_DIR."/$filename.php", "<?php\n\nreturn ".VarExporter::export($rules).";\n");

            foreach ($rules as $k => $v) {
                if (!str_starts_with($filename, 'emoji-')) {
                    continue;
                }
                for ($i = 0; ord($k[$i]) < 128 || "\xC2" === $k[$i]; ++$i) {
                }
                for ($j = $i; isset($k[$j]) && !isset($firstChars[$k[$j]]); ++$j) {
                }
                $c = $k[$j] ?? $k[$i];
                $firstChars[$c] = $c;
            }
        }

        sort($firstChars);

        $quickCheck = '"'.str_replace('%', '\\x', rawurlencode(implode('', $firstChars))).'"';
        $file = dirname(__DIR__, 2).'/EmojiTransliterator.php';
        file_put_contents($file, preg_replace('/QUICK_CHECK = .*;/m', "QUICK_CHECK = {$quickCheck};", file_get_contents($file)));
    }

    private static function testEmoji(string $emoji, string $locale, string $codePoints): bool
    {
        if (!Transliterator::createFromRules("\\$emoji > test ;")) {
            printf('Could not create transliterator for "%s" in "%s" locale. Code Point: "%s". Error: "%s".'."\n", $emoji, $locale, $codePoints, intl_get_error_message());

            return false;
        }

        return true;
    }

    private static function createRules(array $maps): array
    {
        // We must sort the maps by the number of code points, because the order really matters:
        // 🫶🏼 must be before 🫶
        krsort($maps);
        $maps = array_merge(...$maps);

        return $maps;
    }
}
