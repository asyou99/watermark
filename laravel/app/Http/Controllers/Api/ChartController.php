<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalculateIntervalVariationSeries;
use App\Services\FileContentService;
use Carbon\Carbon;

class ChartController extends Controller
{

    /**
     * @var FileContentService
     */
    private $fileService;
    /**
     * @var CalculateIntervalVariationSeries
     */
    private $series;

    public function __construct(FileContentService $fileService, CalculateIntervalVariationSeries $series)
    {
        $this->fileService = $fileService;

        $this->series = $series;

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        Carbon::setLocale('ru');

        $content = $this->fileService->getContent();

        $result = $this->series->calculate($content);

        return response()->json($result);
    }
}
