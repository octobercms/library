<?php namespace October\Rain\Support\Traits;

/**
 * KeyParser trait resolves key strings into namespace, group and item
 * Example: namespace::group.item
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
trait KeyParser
{
    /**
     * @var array keyParserCache is a cache of the parsed items
     */
    protected $keyParserCache = [];

    /**
     * setParsedKey value
     * @param  string  $key
     * @param  array   $parsed
     * @return void
     */
    public function setParsedKey($key, $parsed): void
    {
        $this->keyParserCache[$key] = $parsed;
    }

    /**
     * parseKey into namespace, group, and item
     */
    public function parseKey(string $key): array
    {
        // If we've already parsed the given key, we'll return the cached version we
        // already have, as this will save us some processing. We cache off every
        // key we parse so we can quickly return it on all subsequent requests.
        if (isset($this->keyParserCache[$key])) {
            return $this->keyParserCache[$key];
        }

        $segments = explode('.', $key);

        // If the key does not contain a double colon, it means the key is not in a
        // namespace, and is just a regular configuration item. Namespaces are a
        // tool for organizing configuration items for things such as modules.
        if (strpos($key, '::') === false) {
            $parsed = $this->keyParserParseBasicSegments($segments);
        }
        else {
            $parsed = $this->keyParserParseSegments($key);
        }

        // Once we have the parsed array of this key's elements, such as its groups
        // and namespace, we will cache each array inside a simple list that has
        // the key and the parsed array for quick look-ups for later requests.
        return $this->keyParserCache[$key] = $parsed;
    }

    /**
     * keyParserParseBasicSegments as an array
     */
    protected function keyParserParseBasicSegments(array $segments): array
    {
        // The first segment in a basic array will always be the group, so we can go
        // ahead and grab that segment. If there is only one total segment we are
        // just pulling an entire group out of the array and not a single item.
        $group = $segments[0];

        if (count($segments) === 1) {
            return [null, $group, null];
        }

        // If there is more than one segment in this group, it means we are pulling
        // a specific item out of a groups and will need to return the item name
        // as well as the group so we know which item to pull from the arrays.
        $item = implode('.', array_slice($segments, 1));

        return [null, $group, $item];
    }

    /**
     * keyParserParseSegments from a string
     */
    protected function keyParserParseSegments(string $key): array
    {
        list($namespace, $item) = explode('::', $key);

        // First we'll just explode the first segment to get the namespace and group
        // since the item should be in the remaining segments. Once we have these
        // two pieces of data we can proceed with parsing out the item's value.
        $itemSegments = explode('.', $item);

        $groupAndItem = array_slice($this->keyParserParseBasicSegments($itemSegments), 1);

        return array_merge([$namespace], $groupAndItem);
    }
}
