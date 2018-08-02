<?php
/**
 * @file
 * Defines the LoftXmlElement class.
 *
 * This class does not use namespaces and therefor works php < 5.3
 *
 * @ingroup loft_xml
 * @{
 */

/**
 * Represents an extension of the simpleXmlElement class.
 *
 * @brief Enhances Php's simpleXmlElement with better cdata handling and
 * other fancy stuff.
 */
class LoftXmlElement extends simpleXMLElement
{
    const ATTR_KEY = '_attr';
    const CHILD_KEY = '_val';

    /**
     * Do not access this variable; use the set/getConfig methods instead.
     *
     * @var array
     * - autoEntities bool Set this to TRUE to convert <, >, & during addChild()
     * to their html entities.
     * - autoCData bool Set to TRUE to auto-wrap with CDATA.
     */
    public static $config = array();

    /**
     * Modifies a string by wrapping the CDATA when needed.
     *
     * @param  string &$value
     * @param  bool   $force Wrap the CDATA without analyzing the contents.
     *
     * @return  bool If a transformation took place.
     */
    public static function wrapCData(&$value, $force = false)
    {
        if (!($apply = $force) && $value && !self::isCData($value)) {

            // First convert everything to the allowed xml chars.
            $analyzed = $value;
            self::xmlChars($analyzed);
            $analyzed = preg_replace('/&lt;|&amp;|&gt;|&quot;|&apos/', '', $analyzed);

            // Then strip them out and look for &, which means we have other entities.
            if (strpos($analyzed, '&') !== false) {
                $apply = true;
            }

            // Test to see if we have balanced html tags...
            $regex = "/<\/?\w+((\s+\w+(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>/is";
            if (!$apply && preg_match($regex, $value)) {
                $apply = true;
            }
        }

        if (!empty($apply)) {
            $value = "<![CDATA[{$value}]]>";
        }

        return $apply;
    }

    /**
     * Tests a string to know if it's already wrapped in CData.
     *
     * @param  string $value
     *
     * @return boolean
     */
    public static function isCData($value)
    {
        return strpos($value, '<![CDATA[') !== false;
    }

    /**
     * Converts the 5 xml chars to their html entities.
     *
     * @param  string &$value
     *
     * @return bool If the string was altered. Note: &#039; is converted to
     *   &apos; and is NOT considered altered.
     */
    public static function xmlChars(&$value)
    {
        $original = $value = str_replace('&#039;', '&apos;', $value);
        $analyzed = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
        $analyzed = str_replace('&#039;', '&apos;', $analyzed);
        $analyzed = str_replace('&amp;apos;', '&apos;', $analyzed);
        if (($changed = $analyzed !== $original)) {
            $value = $analyzed;
        }

        return $changed;
    }

    public static function getConfig($name, $default = null)
    {
        $value = isset(self::$config[$name]) ? self::$config[$name] : $default;

        return $value;
    }

    public static function setConfig($name, $value)
    {
        self::$config[$name] = $value;
    }

    // /**
    //  * Renders to xml, counterpart to the CDATA issue
    //  */
    // public function asXML($filename = NULL) {
    //   switch (func_num_args()) {
    //     case 0:
    //       $string = parent::asXML();
    //       break;

    //     case 1:
    //       $string = parent::asXML($filename);
    //       break;
    //   }

    //   return $string;
    // }

    /**
     * Returns a string with the CDATA section removed.
     *
     * @param  string $value
     *
     * @return string
     */
    public static function stripCData($value)
    {
        if (preg_match("/<!\[CDATA\[(.*)\]\]>/s", $value, $matches)) {
            return isset($matches[1]) ? $matches[1] : $matches[0];
        }

        return $value;
    }

    /**
     * Check for a valid attribute name.
     *
     * @param string $name
     *
     * @return bool
     *
     * @link https://www.w3.org/TR/xml/#NT-Name
     */
    public static function isValidAttributeName($name)
    {
        return $name !== static::ATTR_KEY && !is_numeric($name) && preg_match('/^[a-z_:]/i', $name);
    }

    /**
     * Create an xml object from an array.
     *
     * @param array  $array
     *
     * @return \LoftXmlElement
     *
     * @code
     *   $obj = LoftXmlElement::fromArray('root', array(
     *       'title' => 'Great Escape',
     *       'subtitle' => array(
     *           '_val' => 'And Other Adventures',
     *           'style' => 'italics',
     *       ),
     *   ));
     * @endcode
     */
    public static function fromArray(array $array)
    {
        $check = $array;
        unset($check[static::ATTR_KEY]);
        if (count($check) !== 1) {
            throw new \InvalidArgumentException("Array must contain only one element, which is the XML root node");
        }
        $rootName = key($check);
        if (!is_string($rootName) || is_numeric($rootName)) {
            throw new \InvalidArgumentException("Array key must be a valid string suitable for an xml element name.");
        }

        //        $child = isset($array[$rootName]) ? $array[$rootName] : null;
        $child = $array;
        $xml = static::_fromArray($child, $rootName);

        return new LoftXmlElement($xml);
    }

    /**
     * Remove the <?xml version="*"?> declaration
     *
     * @param $xml
     *
     * @return string
     */
    public static function stripHeader($xml)
    {
        return trim(preg_replace('/<\?xml.+?>\s*/', '', $xml));
    }

    /**
     * Helper function to create an xml node.
     *
     * @param string $tagname
     * @param mixed  $value
     * @param array  $data
     *
     * @return string The xml string represented by $data
     */
    protected static function nodeCreator($value, $tagname, array $data = array())
    {
        if (empty($tagname)) {
            throw new \InvalidArgumentException("Empty tagname, cannot create node.");
        }
        $el = new LoftXmlElement("<$tagname>$value</$tagname>");
        if (isset($data[static::ATTR_KEY])) {
            foreach ($data[static::ATTR_KEY] as $name => $value) {
                if (static::isValidAttributeName($name)) {
                    $el->addAttribute($name, $value);
                }
            }
        }
        //
        //        if (empty($data) && $tagname === static::CHILD_KEY) {
        //            // compress '<_val>Somewhere</_val>' to 'Somewhere' for brevity.
        //            return $value;
        //        }

        return static::stripHeader($el->asXml());
    }

    /**
     * Helper function to convert an array to XML
     *
     * @param        $data
     * @param        $tagname
     * @param string $parentKey
     * @param null   &$level Used internally for recursion tracking.
     *
     * @return string
     */
    protected static function _fromArray($data, $tagname, $parentKey = null, &$level = null)
    {
        if (empty($tagname)) {
            throw new \InvalidArgumentException("\$tagname cannot be empty");
        }
        if (is_numeric($tagname)) {
            throw new \InvalidArgumentException("\$tagname cannot be numeric");
        }
        $parentKey = is_null($parentKey) ? $tagname : $parentKey;
        $level = isset($level) ? ++$level : 0;

        if (!is_array($data)) {
            if ($level === 0) {
                $child = static::nodeCreator($data, $tagname);
            }
            else {
                $child = $data;
            }
            --$level;
        }

        //
        // There are four potential layouts for arrays:
        // - numeric keys: all elements become the same xml tag
        // - 2 keys, _val && _attr: this is an xml node with attributes and at least on child
        // - non-numeric keys, that are not _val and _attr: these are children of a parent node.
        // - single, non-numeric key.
        else {

            // First case, the meta keys are used with attributes
            $meta = array(static::ATTR_KEY, static::CHILD_KEY);
            $metaKeys = array_flip($meta);
            $isNodeWithAttributes = count(array_intersect_key($data, $metaKeys)) > 0;
            if ($isNodeWithAttributes && count($data) > 2) {
                throw new \InvalidArgumentException("Only these meta keys are allowed: " . implode(', ', $meta));
            }

            // Second case numeric keys, all must be numeric, representing duplicates of the same tag.
            $isMultipleSameNodes = is_numeric(key($data));
            if ($isMultipleSameNodes) {
                array_walk($data, function ($value, $key) {
                    if (!is_numeric($key)) {
                        throw new \InvalidArgumentException("For numerically-keyed arrays, all keys must be numeric.");
                    }
                });

                if ($level === 0) {
                    throw new \InvalidArgumentException("First level of the array cannot contain numerical keys; XML can only have one root tag.");
                }
            }

            // Third case, each array key represents a child tag.
            $isSingleNode = !$isMultipleSameNodes && !$isNodeWithAttributes && count($data) === 1;
            $isMultipleDifferentNodes = !$isMultipleSameNodes && !$isNodeWithAttributes && count($data) > 1;

            if ($level === 0) {
                $child = $data[$parentKey];
                $mustWrap = !(isset($child['_val']) || isset($child['_attr']));
                if (is_array($child)) {
                    $child = static::_fromArray($child, $tagname, $parentKey, $level);
                }
                if ($mustWrap) {
                    $child = static::nodeCreator($child, $tagname);
                }
            }
            elseif ($isNodeWithAttributes) {
                $value = isset($data[static::CHILD_KEY]) ? $data[static::CHILD_KEY] : null;
                if (is_array($value)) {
                    $value = static::_fromArray($value, $tagname, static::CHILD_KEY, $level);
                }
                $child = static::nodeCreator($value, $tagname, $data);
            }
            elseif ($isMultipleSameNodes) {
                $child = '';
                $tagname = $parentKey;
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $value = static::_fromArray($value, $tagname, $key, $level);
                    }
                    $mustWrap = strpos($value, "<$tagname") !== 0;
                    $child .= $mustWrap ? static::nodeCreator($value, $tagname) : $value;
                }
            }
            // isMultipleDifferentNodes
            // isSingleNode
            else {
                $child = '';
                foreach ($data as $key => $value) {
                    $tagname = ($isMultipleDifferentNodes || $isSingleNode) ? $key : $tagname;
                    if (is_array($value)) {
                        $value = static::_fromArray($value, $tagname, $key, $level);
                    }
                    $mustWrap = strpos($value, "<$tagname") !== 0;
                    $child .= $mustWrap ? static::nodeCreator($value, $tagname) : $value;
                }
            }
        }

        --$level;

        return $child;
    }

    /**
     * Returns a string (or node value) wrapped in CDATA.
     *
     * @code
     *   $xml->addChild('title', '<![CDATA[<h1>My Book</h1>]]>');
     *   $xml->cdata($xml->title);
     * @endcode
     *
     * @param string|XmlFieldXmlElement $key
     *
     * @return string
     */
    public function cdata($key = null, $force = false)
    {
        $value = (string) $key;
        self::wrapCData($value, $force);

        return $value;
    }

    /**
     * Adds a child, wrapping it in CDATA if needed.
     *
     * @param string $name
     * @param string $value
     * @param string $namespace [description]
     *
     * @return \SimpleXMLElement
     */
    public function addCDataChild($name, $value, $namespace = null)
    {
        $this->wrapCData($value, true);

        return $this->addChild($name, $value, $namespace);
    }

    /**
     * Add a child element; this corrects CDATA issues
     *
     * @throws RuntimeException
     */
    public function addChild($name, $value = null, $namespace = null)
    {

        if ($this->isCData($value)

            // Automatic CDATA handling if enabled.
            || ($this->getConfig('autoCData', false) && $this->wrapCData($value))
        ) {
            // Stash a CDATA value for later...
            $valueAsCData = $value;
            $value = null;
        }

        // If not cdata then we need to look into escaping entities.
        elseif ($this->getConfig('autoEntities', false)) {
            $this->xmlChars($value);
        }

        $child = parent::addChild($name, $value, $namespace);

        if (isset($valueAsCData)) {
            $node = dom_import_simplexml($child);
            $no = $node->ownerDocument;
            $value = self::stripCData($valueAsCData);
            $node->appendChild($no->createCDATASection($value));
        }

        return $child;
    }

    /**
     * Extends to allow chaining of add attribute
     *
     * e.g. $xml->addAttribute('size', 'large')->addAttribute('color', 'blue')
     */
    public function addAttribute($name, $value = null, $namespace = null)
    {
        parent::addAttribute($name, $value, $namespace);

        return $this;
    }

    /**
     * Adds a time value as a child and properly formats the time.
     *
     * @param string $name
     * @param mixed  $value This is sent to DateObject class.
     * @param string $namespace
     *
     * @see  DateObject
     *
     * This function depends on the date module being enabled.
     *
     * @see  https://www.drupal.org/project/date
     *
     * @return \SimpleXMLElement
     */
    public function addDateChild($name, $value, $namespace = null)
    {
        $value = $value instanceof DateObject ? $value : new DateObject($value);

        return $this->addChild($name, $value->format('r'), $namespace);
    }

    /**
     * Allows a means of importing json or array data into XML which supports
     * attributes.
     *
     * @param                  $name
     * @param array            $data If $data contains the key $name... then it
     *                               will be used to set the value and
     *                               attributes of the child.  If $data[$key]
     *                               is a scalar, it's value is used and no
     *                               attributes will be set.  If it is an
     *                               array, then
     *                               $data[$key][static::ATTR_KEY] will
     *                               be used as the value.  The remaining
     *                               non-numeric keys will be take as attribute
     *                               names and the values of such set as the
     *                               attribute value.
     * @param string           $namespace
     *
     * @see  isValidAttributeName().
     *
     * Here is an example where there is on valid attribute, one value and two
     * ignored attributes:
     *
     * @code
     *   {
     *   "#value": "http:\/\/comp.com",
     *   "type": "mobile",
     *   "#ignore": 5,
     *   "6": "numerickeysignored"
     *   }
     * @endcode
     *   The XML would become the following:
     * @code
     *   <comp type="mobile">http://comp.com</comp>
     * @endcode
     *
     * @todo Tests supporting the namespace
     */
    public function addChildFromArray($name, $data, $namespace = null)
    {
        $value = $data;
        if (is_array($value) && is_numeric(key($value))) {
            foreach ($value as $item) {
                $this->addChildFromArray($name, $item);
            }

            return;
        }
        elseif (is_array($data)) {
            $value = isset($data[static::CHILD_KEY]) ? $data[static::CHILD_KEY] : null;
        }
        $child = $this->addChild($name, $value, $namespace);
        if (isset($data[static::ATTR_KEY]) && is_array($data[static::ATTR_KEY])) {
            foreach ($data[static::ATTR_KEY] as $key => $value) {
                if ($this->isValidAttributeName($key)) {
                    $child->addAttribute($key, $value);
                }
            }
        }
    }

    /**
     * Extract this element as an array.
     *
     * @return array|mixed
     *
     * @see addChildFromArray().
     * @see extractXmlAsArray().
     */
    public function asArray()
    {
        return $this->extractXmlAsArray($this);
    }

    /**
     * Recursive helper function to asArray()
     *
     * @param \SimpleXMLElement $xml
     * @param null              &$level Used internally for recursion tracking.
     *
     * @return array
     */
    protected function extractXmlAsArray(\SimpleXMLElement $xml, &$level = null)
    {
        $level = isset($level) ? ++$level : 0;
        $value = null;
        if ($hasChildren = count($xml->children())) {
            foreach ($xml->children() as $tagname => $child) {
                $value[$tagname][] = $this->extractXmlAsArray($child, $level);;
            }
            array_walk($value, function (&$item) {
                $item = count($item) === 1 ? reset($item) : $item;
            });
        }
        else {
            $value = (string) $xml;
            $value = trim($value);
            $value = is_numeric($value) ? $value * 1 : $value;
            $value = $value === '' ? null : $value;
        }
        $attributes = (array) $xml->attributes();
        if (isset($attributes['@attributes'])) {
            $attributes = $attributes['@attributes'];
            array_walk($attributes, function (&$attribute) {
                $attribute = is_numeric($attribute) ? $attribute * 1 : $attribute;
            });
        }
        if ($attributes) {
            $value = array_filter(array(
                static::CHILD_KEY => $value,
                static::ATTR_KEY  => $attributes,
            ));
        }
        if ($level === 0) {
            $value = array($xml->getName() => $value);
        }

        --$level;

        return $value;
    }
}
