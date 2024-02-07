<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Emoji;

use Symfony\Component\Emoji\Util\GzipStreamWrapper;

/**
 * @author Simon André <smn.andre@gmail.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class Emojis
{
    public const GROUP_SMILEY_EMOTION = 'Smileys & Emotion';
    public const GROUP_PEOPLE_BODY = 'People & Body';
    public const GROUP_ANIMAL_NATURE = 'Animals & Nature';
    public const GROUP_FOOD_DRINK = 'Food & Drink';
    public const GROUP_TRAVEL_PLACE = 'Travel & Places';
    public const GROUP_ACTIVITIES = 'Activities';
    public const GROUP_OBJECTS = 'Objects';
    public const GROUP_SYMBOLS = 'Symbols';
    public const GROUP_FLAGS = 'Flags';

    private static array $subGroups;

    /**
     * Checks if an emoji exists.
     */
    public static function exists(string $emoji): bool
    {
        return in_array($emoji, self::getEmojis(), true);
    }

    public static function getCountryFlags(): array
    {
        return self::getGroup(self::GROUP_FLAGS)['country-flag'];
    }

    /**
     * Returns all available emojis.
     *
     * @return array<string>
     */
    public static function getEmojis(): array
    {
        $dataFile = __DIR__.'/Resources/data/emojis.php';
        $emojis = is_file($dataFile) ? require $dataFile : GzipStreamWrapper::require($dataFile.'.gz');

        return $emojis;
    }

    public static function getGroups(): array
    {
        return [
            self::GROUP_SMILEY_EMOTION,
            self::GROUP_PEOPLE_BODY,
            self::GROUP_ANIMAL_NATURE,
            self::GROUP_FOOD_DRINK,
            self::GROUP_TRAVEL_PLACE,
            self::GROUP_ACTIVITIES,
            self::GROUP_OBJECTS,
            self::GROUP_SYMBOLS,
            self::GROUP_FLAGS,
        ];
    }

    /**
     * @param string $group
     * @return array
     */
    public static function getEmojiSubgroups(string $group): array
    {
        if (!in_array($group, self::getGroups(), true)) {
            throw new \InvalidArgumentException(sprintf('The group "%s" does not exist.', $group));
        }

        return self::$subGroups[$group] ??= array_keys(self::getGroup($group));
    }

    private static function getGroup(string $group): array
    {
        return self::loadGroups()[$group] ?? throw new \InvalidArgumentException(sprintf('The group "%s" does not exist.', $group));
    }

    private static function loadGroups(): array
    {
        $dataFile = __DIR__.'/Resources/data/emoji_groups.php';
        $groups = is_file($dataFile) ? require $dataFile : GzipStreamWrapper::require($dataFile.'.gz');

        // Build the groups/subgroups
        self::$subGroups ??= array_map(array_keys(...), $groups);

        return $groups;
    }
}
