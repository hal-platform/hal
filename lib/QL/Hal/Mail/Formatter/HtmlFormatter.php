<?php
# lib\QL\Hal\Mail\Formatter\HtmlFormatter.php

namespace QL\Hal\Mail\Formatter;

use Monolog\Logger;
use Monolog\Formatter\NormalizerFormatter;

/**
 *  HAL Notification HTML Email Formatter
 *
 *  @author Matt Colf <matthewcolf@quickenloans.com>
 */
class HtmlFormatter extends NormalizerFormatter
{
    const DATE_FORMAT = '';

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
     *  Constructor
     */
    public function __construct()
    {

    }

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
        //$th = htmlspecialchars($th, ENT_NOQUOTES, 'UTF-8');
        //$td = '<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;">'.htmlspecialchars($td, ENT_NOQUOTES, 'UTF-8').'</pre>';
        //$td = '<pre>'.htmlspecialchars($td, ENT_NOQUOTES, 'UTF-8').'</pre>';
        //return "<tr style=\"padding: 4px;spacing: 0;text-align: left;\">\n<th style=\"background: #cccccc\" width=\"100px\">$th:</th>\n<td style=\"padding: 4px;spacing: 0;text-align: left;background: #eeeeee\">".$td."</td>\n</tr>";

        $row = '<tr style="padding: 4px; spacing: 0; text-align: left;">%s%s</tr>';
        $header = '<th style="background: %s; color: #ffffff;" width="100px">%s</th>';
        $content = '<td style="padding: 4px; spacing: 0; text-align: left; background: #eeeeee">%s</td>';
        $pre = '<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;">%s</pre>';

        return sprintf(
            $row,
            sprintf(
                sprintf(
                    $header,
                    $this->colors[$level],
                    $th
                )
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
     *  Create a HTML h1 tag
     *
     *  @param  string  $title Text to be in the h1
     *  @param  integer $level Error level
     *  @return string
     */
    private function addTitle($title, $level)
    {
        $title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');

        return '<h1 style="background: '.$this->colors[$level].';color: #ffffff;padding: 5px;">'.$title.'</h1>';
    }
    /**
     * Formats a log record.
     *
     * @param  array $record A record to format
     * @return mixed The formatted record
     */
    public function format(array $record)
    {
        $output = '';
        //$output .= $this->addTitle($record['level_name'], $record['level']);
        $output .= '<table cellspacing="1" width="100%">';

        $output .= $this->addRow($record['level'], $record['level_name'], (string) $record['message']);
        //$output .= $this->addRow('Time', $record['datetime']->format('Y-m-d\TH:i:s.uO'));
        //$output .= $this->addRow('Channel', $record['channel']);
        if ($record['context']) {
            $output .= $this->addRow($record['level'], 'CONTEXT', $this->convertToString($record['context']));
        }
        if ($record['extra']) {
            $output .= $this->addRow($record['level'], 'EXTRA', $this->convertToString($record['extra']));
        }

        return $output.'</table>';
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
        if (null === $data || is_scalar($data)) {
            return (string) $data;
        }

        $data = $this->normalize($data);
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return str_replace('\\/', '/', json_encode($data));
    }
}
