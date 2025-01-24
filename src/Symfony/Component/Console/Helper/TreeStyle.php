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
        if (!isset(self::STYLES[$name])) {
            throw new \InvalidArgumentException(\sprintf('Invalid style name "%s". Available styles: "%s".', $name, implode(', ', array_keys(self::STYLES))));
        }

        return new self(...self::STYLES[$name]);
    }

    public function getPrefixEndHasNext(): string
    {
        return $this->prefixEndHasNext;
    }

    public function getPrefixEndLast(): string
    {
        return $this->prefixEndLast;
    }

    public function getPrefixLeft(): string
    {
        return $this->prefixLeft;
    }

    public function getPrefixMidLast(): string
    {
        return $this->prefixMidLast;
    }

    public function getPrefixMidHasNext(): string
    {
        return $this->prefixMidHasNext;
    }

    public function getPrefixRight(): string
    {
        return $this->prefixRight;
    }
}
