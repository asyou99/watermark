<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileContentService
{
    /**
     * @var string
     */
    private $directory;

    public function __construct()
    {
        $this->directory = $this->getDirectory();

    }

    /**
     * Iterate directory with file, get they json content with validate and logging errors
     * Calculate day diff for every worker per department and count worker for Interval Variation Series
     *
     * @return array
     */
    public function getContent(): array
    {
        $rdi = new RecursiveDirectoryIterator($this->directory);

        $rdir = new RecursiveIteratorIterator($rdi, true);

        $data = [];

        foreach ($rdir as $key => $file) {

            if (!is_file($file)) continue;//check exist file

            $content = file_get_contents($file);

            $dataDecode = json_decode($content, true);

            $valid = $this->validateJson(json_last_error(), $content);// validate and logging

            if (!$valid) continue;

            $count = count($dataDecode);

            foreach ($dataDecode as $worker) {

                $start = Carbon::parse($worker['start']);

                $end = Carbon::parse($worker['end']);

                $diffDays[] = $end->diffInDays($start);//number of days worked by an employee

            }

            sort($diffDays);//sort for further correct determination of the place in the interval

            $name = $rdi->getBasename('.json');//name of department

            $data[$name] = $diffDays;

            $data[$name]['count'] = $count;
        }

        return $data;
    }

    /**
     * Check on json error and if she isset logging
     *
     * @param $error
     * @param $content
     * @return bool
     */
    private function validateJson($error, $content): bool
    {
        $isValid = false;

        $message = '';

        switch ($error) {
            case JSON_ERROR_NONE:
                $isValid = true;
                break;

            case JSON_ERROR_DEPTH:
                $message = 'Maximum stack depth exceeded';
                break;

            case JSON_ERROR_STATE_MISMATCH:
                $message = 'Underflow or the modes mismatch';
                break;

            case JSON_ERROR_CTRL_CHAR:
                $message = 'Unexpected control character found';
                break;

            case JSON_ERROR_SYNTAX:
                $message = 'Syntax error, malformed JSON';
                break;

            case JSON_ERROR_UTF8:
                $message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;

            default:
                $message = 'Unknown error';
                break;
        }

        if (!empty($message)) {

            Log::error('Error json parse: ' . $message, [$content]);

        }

        return $isValid;
    }

    /**
     * Set direcroty with file to parse
     *
     * @return string
     */
    protected function getDirectory(): string
    {
        return storage_path('app/public/jsons');
    }

}
