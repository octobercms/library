<?php namespace October\Rain\Html;

use October\Rain\Support\Str;
use Illuminate\Routing\UrlGenerator;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use enshrined\svgSanitize\Sanitizer as SvgSanitizer;

/**
 * HtmlBuilder builds HTML elements
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class HtmlBuilder
{
    use \Illuminate\Support\Traits\Macroable;

    /**
     * url generator instance.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * __construct a new HTML builder instance.
     *
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @return void
     */
    public function __construct(?UrlGenerator $url = null)
    {
        $this->url = $url;
    }

    /**
     * entities converts an HTML string to entities.
     *
     * @param  string  $value
     * @return string
     */
    public function entities($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * decode converts entities to HTML characters.
     *
     * @param  string  $value
     * @return string
     */
    public function decode($value)
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * script generates a link to a JavaScript file.
     *
     * @param  string  $url
     * @param  array   $attributes
     * @param  bool    $secure
     * @return string
     */
    public function script($url, $attributes = [], $secure = null)
    {
        $attributes['src'] = $this->url->asset($url, $secure);

        return '<script'.$this->attributes($attributes).'></script>'.PHP_EOL;
    }

    /**
     * style generates a link to a CSS file.
     *
     * @param  string  $url
     * @param  array   $attributes
     * @param  bool    $secure
     * @return string
     */
    public function style($url, $attributes = [], $secure = null)
    {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];

        $attributes = $attributes + $defaults;

        $attributes['href'] = $this->url->asset($url, $secure);

        return '<link'.$this->attributes($attributes).'>'.PHP_EOL;
    }

    /**
     * image generates an HTML image element.
     *
     * @param  string  $url
     * @param  string  $alt
     * @param  array   $attributes
     * @param  bool    $secure
     * @return string
     */
    public function image($url, $alt = null, $attributes = [], $secure = null)
    {
        $attributes['alt'] = $alt;

        return '<img src="'.$this->url->asset($url, $secure).'"'.$this->attributes($attributes).'>';
    }

    /**
     * link generates a HTML link.
     *
     * @param  string  $url
     * @param  string  $title
     * @param  array   $attributes
     * @param  bool    $secure
     * @return string
     */
    public function link($url, $title = null, $attributes = [], $secure = null)
    {
        $url = $this->url->to($url, [], $secure);

        if (is_null($title) || $title === false) {
            $title = $url;
        }

        return '<a href="'.$url.'"'.$this->attributes($attributes).'>'.$this->entities($title).'</a>';
    }

    /**
     * secureLink generates a HTTPS HTML link.
     *
     * @param  string  $url
     * @param  string  $title
     * @param  array   $attributes
     * @return string
     */
    public function secureLink($url, $title = null, $attributes = [])
    {
        return $this->link($url, $title, $attributes, true);
    }

    /**
     * linkAsset generates a HTML link to an asset.
     *
     * @param  string  $url
     * @param  string  $title
     * @param  array   $attributes
     * @param  bool    $secure
     * @return string
     */
    public function linkAsset($url, $title = null, $attributes = [], $secure = null)
    {
        $url = $this->url->asset($url, $secure);

        return $this->link($url, $title ?: $url, $attributes, $secure);
    }

    /**
     * linkSecureAsset generates a HTTPS HTML link to an asset.
     *
     * @param  string  $url
     * @param  string  $title
     * @param  array   $attributes
     * @return string
     */
    public function linkSecureAsset($url, $title = null, $attributes = [])
    {
        return $this->linkAsset($url, $title, $attributes, true);
    }

    /**
     * linkRoute generates a HTML link to a named route.
     *
     * @param  string  $name
     * @param  string  $title
     * @param  array   $parameters
     * @param  array   $attributes
     * @return string
     */
    public function linkRoute($name, $title = null, $parameters = [], $attributes = [])
    {
        return $this->link($this->url->route($name, $parameters), $title, $attributes);
    }

    /**
     * linkAction generates a HTML link to a controller action.
     *
     * @param  string  $action
     * @param  string  $title
     * @param  array   $parameters
     * @param  array   $attributes
     * @return string
     */
    public function linkAction($action, $title = null, $parameters = [], $attributes = [])
    {
        return $this->link($this->url->action($action, $parameters), $title, $attributes);
    }

    /**
     * mailto generates a HTML link to an email address.
     *
     * @param  string  $email
     * @param  string  $title
     * @param  array   $attributes
     * @return string
     */
    public function mailto($email, $title = null, $attributes = [])
    {
        $email = $this->email($email);

        $title = $title ?: $email;

        $email = $this->obfuscate('mailto:') . $email;

        return '<a href="'.$email.'"'.$this->attributes($attributes).'>'.$this->entities($title).'</a>';
    }

    /**
     * email obfuscates an e-mail address to prevent spam-bots from sniffing it.
     *
     * @param  string  $email
     * @return string
     */
    public function email($email)
    {
        return str_replace('@', '&#64;', $this->obfuscate($email));
    }

    /**
     * ol generate an ordered list of items.
     *
     * @param  array   $list
     * @param  array   $attributes
     * @return string
     */
    public function ol($list, $attributes = [])
    {
        return $this->listing('ol', $list, $attributes);
    }

    /**
     * ul generates an un-ordered list of items.
     *
     * @param  array   $list
     * @param  array   $attributes
     * @return string
     */
    public function ul($list, $attributes = [])
    {
        return $this->listing('ul', $list, $attributes);
    }

    /**
     * listing HTML element.
     *
     * @param  string  $type
     * @param  array   $list
     * @param  array   $attributes
     * @return string
     */
    protected function listing($type, $list, $attributes = [])
    {
        $html = '';

        if (count($list) === 0) {
            return $html;
        }

        // Essentially we will just spin through the list and build the list of the HTML
        // elements from the array. We will also handled nested lists in case that is
        // present in the array. Then we will build out the final listing elements.
        foreach ($list as $key => $value) {
            $html .= $this->listingElement($key, $type, $value);
        }

        $attributes = $this->attributes($attributes);

        return "<{$type}{$attributes}>{$html}</{$type}>";
    }

    /**
     * listingElement creates the HTML for a listing element.
     *
     * @param  mixed    $key
     * @param  string  $type
     * @param  string  $value
     * @return string
     */
    protected function listingElement($key, $type, $value)
    {
        if (is_array($value)) {
            return $this->nestedListing($key, $type, $value);
        }

        return '<li>'.e($value).'</li>';
    }

    /**
     * nestedListing creates the HTML for a nested listing attribute.
     *
     * @param  mixed    $key
     * @param  string  $type
     * @param  string  $value
     * @return string
     */
    protected function nestedListing($key, $type, $value)
    {
        if (is_int($key)) {
            return $this->listing($type, $value);
        }

        return '<li>'.$key.$this->listing($type, $value).'</li>';
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @param  array  $attributes
     * @return string
     */
    public function attributes($attributes)
    {
        $html = [];

        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        foreach ((array) $attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);

            if (!is_null($element)) {
                $html[] = $element;
            }
        }

        return count($html) > 0 ? ' '.implode(' ', $html) : '';
    }

    /**
     * attributeElement builds a single attribute element.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) {
            $key = $value;
        }

        if (is_null($value)) {
            return;
        }

        if ($value === true) {
            return $key;
        }
        elseif (is_array($value)) {
            $value = substr(htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8'), 1, -1);
        }
        else {
            $value = e($value);
        }

        return $key.'="'.$value.'"';
    }

    /**
     * obfuscate a string to prevent spam-bots from sniffing it.
     * @param  string  $value
     * @return string
     */
    public function obfuscate($value)
    {
        $safe = '';

        foreach (str_split($value) as $letter) {
            if (ord($letter) > 128) {
                return $letter;
            }

            // To properly obfuscate the value, we will randomly convert each letter to
            // its entity or hexadecimal representation, keeping a bot from sniffing
            // the randomly obfuscated letters out of the string on the responses.
            switch (rand(1, 3)) {
                case 1:
                    $safe .= '&#'.ord($letter).';';
                    break;

                case 2:
                    $safe .= '&#x'.dechex(ord($letter)).';';
                    break;

                case 3:
                    $safe .= $letter;
            }
        }

        return $safe;
    }

    /**
     * strip removes HTML from a string, with allowed tags, e.g. <p>
     * @param $string
     * @param $allow
     * @return string
     */
    public static function strip($string, $allow = '')
    {
        return strip_tags(htmlspecialchars_decode($string), $allow);
    }

    /**
     * limit HTML with specific length with a proper tag handling.
     * @param string $html HTML string to limit
     * @param int $maxLength String length to truncate at
     * @param  string  $end
     * @return string
     */
    public static function limit($html, $maxLength = 100, $end = '...')
    {
        $isUtf8 = true;
        $printedLength = 0;
        $position = 0;
        $tags = [];

        $regex = $isUtf8
            ? '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*}'
            : '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}';

        $result = '';

        while ($printedLength < $maxLength && preg_match($regex, $html, $match, PREG_OFFSET_CAPTURE, $position)) {
            list($tag, $tagPosition) = $match[0];

            $str = substr($html, $position, $tagPosition - $position);
            if ($printedLength + strlen($str) > $maxLength) {
                $result .= substr($str, 0, $maxLength - $printedLength) . $end;
                $printedLength = $maxLength;
                break;
            }

            $result .= $str;
            $printedLength += strlen($str);
            if ($printedLength >= $maxLength) {
                $result .= $end;
                break;
            }

            if ($tag[0] === '&' || ord($tag[0]) >= 0x80) {
                $result .= $tag;
                $printedLength++;
            }
            else {
                $tagName = $match[1][0];
                if ($tag[1] === '/') {
                    $openingTag = array_pop($tags);
                    $result .= $tag;
                }
                elseif ($tag[strlen($tag) - 2] === '/') {
                    $result .= $tag;
                }
                else {
                    $result .= $tag;
                    $tags[] = $tagName;
                }
            }

            $position = $tagPosition + strlen($tag);
        }

        if ($printedLength < $maxLength && $position < strlen($html)) {
            $result .= substr($html, $position, $maxLength - $printedLength);
        }

        while (!empty($tags)) {
            $result .= sprintf('</%s>', array_pop($tags));
        }

        return $result;
    }

    /**
     * minify makes HTML more compact
     */
    public static function minify($html)
    {
        $search = [
            // Strip whitespaces after tags, except space
            '/\>[^\S ]+/s',
            // Strip whitespaces before tags, except space
            '/[^\S ]+\</s',
            // Shorten multiple whitespace sequences
            '/(\s)+/s',
            // Remove HTML comments
            '/<!--(.|\s)*?-->/'
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];

        return preg_replace($search, $replace, $html);
    }

    /**
     * clean HTML to prevent XSS attacks using DOM-based sanitization.
     */
    public static function clean(string $html): string
    {
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias();

        $sanitizer = new HtmlSanitizer($config);

        return $sanitizer->sanitize($html);
    }

    /**
     * cleanCss sanitizes CSS content to prevent injection attacks while preserving
     * valid CSS syntax. Unlike clean() which is designed for HTML, this method handles
     * CSS-specific threats: closing style tags, javascript: URLs, and legacy IE expressions.
     */
    public static function cleanCss(string $css): string
    {
        // Strip any HTML tags (prevents </style><script>...</script> injection)
        $css = strip_tags($css);

        // Remove CSS expressions and legacy IE behaviors
        $css = preg_replace('/expression\s*\(/i', '(', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = preg_replace('/-moz-binding\s*:/i', '', $css);

        // Remove javascript: and vbscript: from url() values
        $css = preg_replace('/url\s*\(\s*[\'"]?\s*(?:javascript|vbscript)\s*:/i', 'url(invalid:', $css);

        return $css;
    }

    /**
     * cleanVector sanitizes XML/SVG content to prevent XSS attacks using DOM-based sanitization.
     * Uses enshrined/svg-sanitize library which is ported from DOMPurify.
     */
    public static function cleanVector(string $html): string
    {
        $sanitizer = new SvgSanitizer();
        $sanitizer->minify(false);
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->removeXMLTag(true);

        $clean = $sanitizer->sanitize($html);

        return $clean !== false ? $clean : '';
    }

    /**
     * isValidColor determines if a given string is a valid CSS color value
     */
    public function isValidColor(string $value): bool
    {
        return Str::startsWith($value, [
            '#',
            'var(',
            'rgb(',
            'rgba(',
            'hsl('
        ]);
    }
}
