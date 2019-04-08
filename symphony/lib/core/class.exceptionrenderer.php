<?php

class ExceptionRenderer
{
    /**
     * The render function will take an Throwable and output a HTML page
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  An HTML string
     */
    public static function render($e)
    {
        $lines = null;

        foreach (self::getNearbyLines($e->getLine(), $e->getFile()) as $line => $string) {
            $lines .= sprintf(
                '<li%s><strong>%d</strong> <code>%s</code></li>',
                (($line+1) == $e->getLine() ? ' class="error"' : null),
                ++$line,
                str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($string))
            );
        }

        $trace = null;

        foreach ($e->getTrace() as $t) {
            $trace .= sprintf(
                '<li><code><em>[%s:%d]</em></code></li><li><code>&#160;&#160;&#160;&#160;%s%s%s();</code></li>',
                (isset($t['file']) ? $t['file'] : null),
                (isset($t['line']) ? $t['line'] : null),
                (isset($t['class']) ? $t['class'] : null),
                (isset($t['type']) ? $t['type'] : null),
                $t['function']
            );
        }

        $queries = null;

        if (is_object(Symphony::Database())) {
            $debug = Symphony::Database()->getLogs();

            if (!empty($debug)) {
                foreach ($debug as $query) {
                    $queries .= sprintf(
                        '<li><em>[%01.4f]</em><code> %s;</code> </li>',
                        (isset($query['execution_time']) ? $query['execution_time'] : null),
                        htmlspecialchars($query['query'])
                    );
                }
            }
        }

        return self::renderHtml(
            'fatalerror.generic',
            ($e instanceof ErrorException ? ErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error'),
            $e->getMessage() .
                ($e->getPrevious()
                    ? '<br />' . __('Previous exception: ') . $e->getPrevious()->getMessage()
                    : ''
                ),
            $e->getFile(),
            $e->getLine(),
            $lines,
            $trace,
            $queries
        );
    }

    /**
     * Retrieves a window of lines before and after the line where the error
     * occurred so that a developer can help debug the exception
     *
     * @param integer $line
     *  The line where the error occurred.
     * @param string $file
     *  The file that holds the logic that caused the error.
     * @param integer $window
     *  The number of lines either side of the line where the error occurred
     *  to show
     * @return array
     */
    protected static function getNearbyLines($line, $file, $window = 5)
    {
        if (!file_exists($file)) {
            return array();
        }

        return array_slice(file($file), ($line - 1) - $window, $window * 2, true);
    }

    /**
     * Returns the path to the error-template by looking at the `WORKSPACE/template/`
     * directory, then at the `TEMPLATES`  directory for the convention `*.tpl`. If
     * the template is not found, `false` is returned
     *
     * @since Symphony 2.3
     * @param string $name
     *  Name of the template
     * @return string|false
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getTemplate($name)
    {
        $format = '%s/%s.tpl';

        if (!ExceptionHandler::$enabled) {
            if (!file_exists($template = sprintf($format, TEMPLATE, 'fatalerror.disabled'))) {
                return false;
            }
            return $template;
        }
        else if (file_exists($template = sprintf($format, WORKSPACE . '/template', $name))) {
            return $template;
        }
        else if (file_exists($template = sprintf($format, TEMPLATE, $name))) {
            return $template;
        }
        else {
            return false;
        }
    }

    /**
     * This function will fetch the desired `$template`, and output the
     * Throwable in a user friendly way.
     *
     * @since Symphony 2.4
     * @since Symphony 2.6.4 the method is protected
     * @param string $template
     *  The template name, which should correspond to something in the TEMPLATE
     *  directory, eg `fatalerror.fatal`.
     *
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     *
     * @param string $heading
     * @param string $message
     * @param string $file
     * @param string $line
     * @param string $lines
     * @param string $trace
     * @param string $queries
     * @return string
     *  The HTML of the formatted error message.
     */
    public static function renderHtml($template, $heading, $message, $file = null, $line = null, $lines = null, $trace = null, $queries = null)
    {
        $html = sprintf(
            file_get_contents(self::getTemplate($template)),
            $heading,
            !ExceptionHandler::$enabled ? 'Something unexpected occurred.' : General::unwrapCDATA($message),
            !ExceptionHandler::$enabled ? '' : $file,
            !ExceptionHandler::$enabled ? '' : $line,
            !ExceptionHandler::$enabled ? null : $lines,
            !ExceptionHandler::$enabled ? null : $trace,
            !ExceptionHandler::$enabled ? null : $queries
        );

        $html = str_replace('{ASSETS_URL}', ASSETS_URL, $html);
        $html = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
        $html = str_replace('{URL}', URL, $html);
        $html = str_replace('{PHP}', PHP_VERSION, $html);
        $html = str_replace(
            '{MYSQL}',
            !Symphony::Database() ? 'N/A' : Symphony::Database()->getVersion(),
            $html
        );

        return $html;
    }
}
