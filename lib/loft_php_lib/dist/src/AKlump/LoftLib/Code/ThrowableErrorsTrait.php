<?php


namespace AKlump\LoftLib\Code;

/**
 * Trait ThrowableErrorsTrait
 *
 * Allows you to throw errors as Exceptions.
 *
 * @code
 *   $path = "/some/dir/somewhere";
 *   try {
 *       static::throwErrors(function () use ($path) {
 *           mkdir($path, 0755, true);
 *       });
 *   } catch (\Exception $exception) {
 *       static::rethrow($exception, function ($message) use ($path) {
 *           return "$message: trying to ensure $path";
 *       });
 *   }
 * @endcode
 *
 * @package AKlump\LoftLib\Code
 */
trait ThrowableErrorsTrait {

    /**
     * Throw any errors the occur during $callback as Exceptions.
     *
     * The exception code will containt the error level value
     * @link http://php.net/manual/en/errorfunc.constants.php
     *
     * @param $callback
     *
     * @throws AKlump\LoftLib\Code\StandardPhpErrorException if an error occurs.
     */
    public static function throwErrors($callback)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $message = sprintf(static::throwErrorsGetMessageTemplate(), $errstr, $errfile, $errline);
            throw new StandardPhpErrorException($message, $errno);
        });
        $callback();
        restore_error_handler();
    }

    /**
     * Return a string template for composing the exception message
     *
     * Override as needed.
     *
     * @return string
     */
    protected static function throwErrorsGetMessageTemplate()
    {
        return '%s in %s on line %s';
    }

    /**
     * Rethrow an exception after modifying it's message (or code).
     *
     * @param \Exception $e
     * @param callable   $handleMessage Receives ($message, $exception) as arguments.
     */
    public static function rethrow(\Exception $e, callable $handleMessage)
    {
        $class = get_class($e);
        throw new $class($handleMessage($e->getMessage(), $e), $e->getCode());
    }
}
