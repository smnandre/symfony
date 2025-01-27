<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

/**
 * Configures the output of the Tree helper.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class TreeStyle
{
    private const STYLE_DEFAULT = 'default';
    private const STYLE_BOX = 'box';
    private const STYLE_COMPACT = 'compact';
    private const STYLE_FRAME = 'frame';
    private const STYLE_LIGHT = 'light';
    private const STYLE_MINIMAL = 'minimal';
    private const STYLE_ROUNDED = 'rounded';

    private const STYLES = [
        self::STYLE_DEFAULT => [
            '├── ',
            '└── ',
            '',
            '│   ',
            '└── ',
            '',
        ],
        self::STYLE_BOX => [
            '|-- ',
            '`-- ',
            '',
            '|   ',
            '`-- ',
            '',
        ],
        self::STYLE_COMPACT => [
            '|- ',
            '\- ',
            '',
            '| ',
            '\- ',
            '',
        ],
        self::STYLE_FRAME => [
            '╠═ ',
            '╚═ ',
            '',
            '╟─ ',
            '╙─ ',
            '',
        ],
        self::STYLE_LIGHT => [
            '|-- ',
            '`-- ',
            '',
            '|   ',
            '`-- ',
            '',
        ],
        self::STYLE_MINIMAL => [
            ' ',
            '. ',
            '',
            '. ',
            '. ',
            '',
        ],
        self::STYLE_ROUNDED => [
            '├─ ',
            '╰─ ',
            '',
            '│  ',
            '╭─ ',
            '',
        ],
    ];

    public function __construct(
        private readonly string $prefixEndHasNext,
        private readonly string $prefixEndLast,
        private readonly string $prefixLeft,
        private readonly string $prefixMidHasNext,
        private readonly string $prefixMidLast,
        private readonly string $prefixRight,
    ) {
    }

    public static function box(): self
    {
        return self::style(self::STYLE_BOX);
    }

    public static function compact(): self
    {
        return self::style(self::STYLE_COMPACT);
    }

    public static function default(): self
    {
        return self::style(self::STYLE_DEFAULT);
    }

    public static function frame(): self
    {
        return self::style(self::STYLE_FRAME);
    }

    public static function light(): self
    {
        return new self(...self::STYLES[self::STYLE_LIGHT]);
    }

    public static function minimal(): self
    {
        return self::style(self::STYLE_MINIMAL);
    }

    public static function rounded(): self
    {
        return self::style(self::STYLE_ROUNDED);
    }

    private static function style(string $name): self
    {
        return new self(...self::STYLES[$name]);
    }

    /**
     * @internal
     */
    public function applyPrefixes(\RecursiveTreeIterator $iterator): void
    {
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_LEFT, $this->prefixLeft);
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, $this->prefixMidHasNext);
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_LAST, $this->prefixMidLast);
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_HAS_NEXT, $this->prefixEndHasNext);
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_LAST, $this->prefixEndLast);
        $iterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_RIGHT, $this->prefixRight);
    }
}
