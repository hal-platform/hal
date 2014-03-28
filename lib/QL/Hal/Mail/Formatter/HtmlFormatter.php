<?php
# lib\QL\Hal\Mail\Formatter\HtmlFormatter.php

namespace QL\Hal\Mail\Formatter;

use DateTime;
use Exception;
use Traversable;
use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;

/**
 *  HAL Notification HTML Email Formatter
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HtmlFormatter implements FormatterInterface
{
    /**
     *  Date output format
     */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     *  Maximum number of lines to normalize an array to
     */
    const LINE_LIMIT = 100000;

    /**
     *  Log level colors
     *
     *  @var array
     */
    private $colors = array(
        Logger::DEBUG     => '#cccccc',
        Logger::INFO      => '#468847',
        Logger::NOTICE    => '#3a87ad',
        Logger::WARNING   => '#c09853',
        Logger::ERROR     => '#f0ad4e',
        Logger::CRITICAL  => '#FF7708',
        Logger::ALERT     => '#C12A19',
        Logger::EMERGENCY => '#000000',
    );

    /**
     *  An array regex expressions to strip from output
     *
     *  Normally used to suppress terminal colors and other bullshit
     *
     *  @var array
     */
    private $strippers = array(
        '/\\033\[[^\W]{2,3}/'
    );

    /**
     *  Creates an HTML table row
     *
     *  @param int $level
     *  @param string $th Row header content
     *  @param string $td Row standard cell content
     *  @return string
     */
    private function addRow($level, $th, $td = ' ')
    {
        $row = '<tr style="padding: 4px; spacing: 0; text-align: left;">%s%s</tr>';
        $header = '<th style="background: %s; color: #ffffff;" width="100px">%s</th>';
        $content = '<td style="padding: 4px; spacing: 0; text-align: left; background: #eeeeee">%s</td>';
        $pre = '<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;">%s</pre>';

        return sprintf(
            $row,
            sprintf(
                $header,
                $this->colors[$level],
                htmlspecialchars($th, ENT_NOQUOTES, 'UTF-8')
            ),
            sprintf(
                $content,
                sprintf(
                    $pre,
                    htmlspecialchars($td, ENT_NOQUOTES, 'UTF-8')
                )
            )
        );
    }

    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $level = $record['level'];

        $message = $this->addRow($level, $record['level_name'], (string) $record['message']);
        $context = ($record['context']) ? $this->addRow($level, 'CONTEXT', $this->convertToString($record['context'])) : '';
        $extra = ($record['extra']) ? $this->addRow($level, 'EXTRA', $this->convertToString($record['extra'])) : '';

        return "<table cellspacing='1' width='100%'>".$message.$context.$extra."</table>";
    }

    /**
     *  Formats a set of log records.
     *
     *  @param  array $records A set of records to format
     *  @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    /**
     *  Convert data of various types to a string
     *
     *  @param mixed $data
     *  @return mixed|string
     */
    protected function convertToString($data)
    {
        if (is_string($data)) {
            return $this->strip($data);
        }

        if (null === $data || is_scalar($data)) {
            return (string) $data;
        }

        return json_encode(
            $this->normalize($data),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     *  Normalize data of various types for easier string encoding
     *
     *  @param $data
     *  @return array|string
     */
    protected function normalize($data)
    {
        if (is_string($data)) {
            return $this->strip($data);
        }

        if (null === $data || is_scalar($data)) {
            return $data;
        }

        if (is_array($data) || $data instanceof Traversable) {
            $normalized = array();

            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ >= self::LINE_LIMIT) {
                    $normalized['...'] = sprintf('Over % lines, aborting normalization.', self::LINE_LIMIT);
                    break;
                }
                $normalized[$key] = $this->normalize($value);
            }

            return $normalized;
        }

        if ($data instanceof DateTime) {
            return $data->format(self::DATE_FORMAT);
        }

        if (is_object($data)) {
            if ($data instanceof Exception) {
                return $this->normalizeException($data);
            }

            return sprintf("[object] (%s: %s)", get_class($data), $this->toJson($data, true));
        }

        if (is_resource($data)) {
            return '[resource]';
        }

        return '[unknown('.gettype($data).')]';
    }

    /**
     *  Normalize exception objects
     *
     * @param Exception $e
     * @return array
     */
    protected function normalizeException(Exception $e)
    {
        $data = array(
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        );

        $trace = $e->getTrace();
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $data['trace'][] = $frame['file'].':'.$frame['line'];
            } else {
                $data['trace'][] = json_encode($frame);
            }
        }

        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous);
        }

        return $data;
    }

    /**
     *  Convert mixed data to JSON
     *
     * @param $data
     * @param bool $ignoreErrors
     * @return string
     */
    protected function toJson($data, $ignoreErrors = false)
    {
        if ($ignoreErrors) {
            return @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     *  Strip terms from output
     *
     *  @param $data
     *  @return mixed
     */
    protected function strip($data)
    {
        foreach ($this->strippers as $stripper) {
            $data = preg_replace($stripper, '', $data);
        }

        return $data;
    }
}
