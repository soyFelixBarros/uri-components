<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package    League\Uri
 * @subpackage League\Uri\Components
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright  2016 Ignace Nyamagana Butera
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    1.0.0
 * @link       https://github.com/thephpleague/uri-components
 */
namespace League\Uri\Components;

use Traversable;

/**
 * Value object representing a URI path component.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Components
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 */
class HierarchicalPath extends HierarchicalComponent implements PathInterface
{
    use PathInfo;

    /**
     * @inheritdoc
     */
    protected static $separator = '/';

    /**
     * @inheritdoc
     */
    public static function __set_state(array $properties)
    {
        return static::createFromSegments($properties['data'], $properties['isAbsolute']);
    }

    /**
     * return a new instance from an array or a traversable object
     *
     * @param Traversable|string[] $data The segments list
     * @param int                  $type one of the constant IS_ABSOLUTE or IS_RELATIVE
     *
     * @throws Exception If $data is invalid
     * @throws Exception If $type is not a recognized constant
     *
     * @return static
     */
    public static function createFromSegments($data, $type = self::IS_RELATIVE)
    {
        static $type_list = [self::IS_ABSOLUTE => 1, self::IS_RELATIVE => 1];

        if (!isset($type_list[$type])) {
            throw Exception::fromInvalidFlag($type);
        }

        $path = implode(static::$separator, static::validateIterator($data));
        if (static::IS_ABSOLUTE === $type) {
            $path = static::$separator.$path;
        }

        return new static($path);
    }

    /**
     * New Instance
     *
     * @param string|null $path
     */
    public function __construct($path = null)
    {
        if (null === $path) {
            $path = '';
        }

        $path = $this->validateString($path);
        $this->isAbsolute = static::IS_RELATIVE;
        if (static::$separator === substr($path, 0, 1)) {
            $this->isAbsolute = static::IS_ABSOLUTE;
            $path = substr($path, 1, strlen($path));
        }

        $append_delimiter = false;
        if (static::$separator === substr($path, -1, 1)) {
            $path = substr($path, 0, -1);
            $append_delimiter = true;
        }

        $this->data = $this->validate($path);
        if ($append_delimiter) {
            $this->data[] = '';
        }
    }

    /**
     * validate the submitted data
     *
     * @param string $data
     *
     * @return array
     */
    protected function validate($data)
    {
        $data = $this->filterEncodedPath($data);

        $filterSegment = function ($segment) {
            return isset($segment);
        };

        $data = $this->decodePath($data);

        return array_filter(explode(static::$separator, $data), $filterSegment);
    }

    /**
     * Return a new instance when needed
     *
     * @param array $data
     * @param int   $isAbsolute
     *
     * @return static
     */
    protected function newHierarchicalInstance(array $data, $isAbsolute)
    {
        return static::createFromSegments($data, $isAbsolute);
    }

    /**
     * Returns parent directory's path
     *
     * @return string
     */
    public function getDirname()
    {
        return str_replace(
            ['\\', "\0"],
            [static::$separator, '\\'],
            dirname(str_replace('\\', "\0", $this->__toString()))
        );
    }

    /**
     * Returns the path basename
     *
     * @return string
     */
    public function getBasename()
    {
        $data = $this->data;

        return (string) array_pop($data);
    }

    /**
     * Returns the basename extension
     *
     * @return string
     */
    public function getExtension()
    {
        list($basename, ) = explode(';', $this->getBasename(), 2);

        return pathinfo($basename, PATHINFO_EXTENSION);
    }

    /**
     * @inheritdoc
     */
    public function keys()
    {
        if (0 === func_num_args()) {
            return array_keys($this->data);
        }

        return array_keys(
            $this->data,
            $this->decodeComponent($this->validateString(func_get_arg(0))),
            true
        );
    }

    /**
     * Returns an array representation of the HierarchicalPath
     *
     * @return array
     */
    public function getSegments()
    {
        return $this->data;
    }

    /**
     * Retrieves a single path segment.
     *
     * Retrieves a single path segment. If the segment offset has not been set,
     * returns the default value provided.
     *
     * @param int   $offset  the segment offset
     * @param mixed $default Default value to return if the offset does not exist.
     *
     * @return mixed
     */
    public function getSegment($offset, $default = null)
    {
        if ($offset > -1 && isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        $nb_segments = count($this->data);
        if ($offset <= -1 && $nb_segments + $offset > -1) {
            return $this->data[$nb_segments + $offset];
        }

        return $default;
    }

    /**
     * Return the decoded string representation of the component
     *
     * @return string
     */
    protected function getDecoded()
    {
        $front_delimiter = '';
        if ($this->isAbsolute === static::IS_ABSOLUTE) {
            $front_delimiter = static::$separator;
        }

        return $front_delimiter.implode(static::$separator, $this->data);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return (string) $this->getContent();
    }

    /**
     * Returns an instance with the specified component prepended
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified component with the prepended data
     *
     * @param string $component the component to append
     *
     * @return static
     */
    public function prepend($component)
    {
        $new_segments = $this->filterComponent($component);
        if (!empty($new_segments) && '' === end($new_segments)) {
            array_pop($new_segments);
        }

        return static::createFromSegments(array_merge($new_segments, $this->data), $this->isAbsolute);
    }

    /**
     * Returns an instance with the specified component appended
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified component with the appended data
     *
     * @param string $component the component to append
     *
     * @return static
     */
    public function append($component)
    {
        $new_segments = $this->filterComponent($component);
        $data = $this->data;
        if (!empty($data) && '' === end($data)) {
            array_pop($data);
        }

        return static::createFromSegments(array_merge($data, $new_segments), $this->isAbsolute);
    }

    /**
     * Filter the component to append or prepend
     *
     * @param string $component
     *
     * @return array
     */
    protected function filterComponent($component)
    {
        $component = $this->validateString($component);
        if ('' != $component && '/' == $component[0]) {
            $component = substr($component, 1);
        }

        return $this->validate($component);
    }

    /**
     * Returns an instance with the specified parent directory's path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the extension basename modified.
     *
     * @param string $path the new parent directory path
     *
     * @return static
     */
    public function withDirname($path)
    {
        $path = $this->validateString($path);
        if ($path === $this->getDirname()) {
            return $this;
        }

        if ('/' !== mb_substr($path, -1, 1)) {
            $path = $path.'/';
        }

        return new static($path.$this->getBasename());
    }

    /**
     * Returns an instance with the specified basename.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the extension basename modified.
     *
     * @param string $path the new path basename
     *
     * @return static
     */
    public function withBasename($path)
    {
        $path = $this->validateString($path);
        if (false !== strpos($path, '/')) {
            throw new Exception('The submitted basename can not contain the path separator');
        }

        $data = $this->data;
        $basename = array_pop($data);
        if ($path == $basename) {
            return $this;
        }

        $data[] = $path;

        return static::createFromSegments($data, $this->isAbsolute);
    }

    /**
     * Returns an instance with the specified basename extension
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the extension basename modified.
     *
     * @param string $extension the new extension
     *                          can preceeded with or without the dot (.) character
     *
     * @return static
     */
    public function withExtension($extension)
    {
        $extension = $this->formatExtension($extension);
        $segments = $this->getSegments();
        $basename = array_pop($segments);
        $parts = explode(';', $basename, 2);
        $basenamePart = array_shift($parts);
        if ('' === $basenamePart || is_null($basenamePart)) {
            return $this;
        }

        $newBasename = $this->buildBasename($basenamePart, $extension, array_shift($parts));
        if ($basename === $newBasename) {
            return $this;
        }
        $segments[] = $newBasename;

        return $this->createFromSegments($segments, $this->isAbsolute);
    }

    /**
     * create a new basename with a new extension
     *
     * @param string $basenamePart  the basename file part
     * @param string $extension     the new extension to add
     * @param string $parameterPart the basename parameter part
     *
     * @return string
     */
    protected function buildBasename($basenamePart, $extension, $parameterPart)
    {
        $length = mb_strrpos($basenamePart, '.'.pathinfo($basenamePart, PATHINFO_EXTENSION), 'UTF-8');
        if (false !== $length) {
            $basenamePart = mb_substr($basenamePart, 0, $length, 'UTF-8');
        }

        $parameterPart = trim($parameterPart);
        if ('' !== $parameterPart) {
            $parameterPart = ";$parameterPart";
        }

        $extension = trim($extension);
        if ('' !== $extension) {
            $extension = ".$extension";
        }

        return $basenamePart.$extension.$parameterPart;
    }

    /**
     * validate and format the given extension
     *
     * @param string $extension the new extension to use
     *
     * @throws Exception If the extension is not valid
     *
     * @return string
     */
    protected function formatExtension($extension)
    {
        if (0 === strpos($extension, '.')) {
            throw new Exception('an extension sequence can not contain a leading `.` character');
        }

        if (strpos($extension, static::$separator)) {
            throw new Exception('an extension sequence can not contain a path delimiter');
        }

        return implode(static::$separator, $this->validate($extension));
    }
}