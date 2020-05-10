<?php

namespace App\Services;

use Carbon\Carbon;

class CalculateIntervalVariationSeries
{
    /**
     * Calculation of data for the variational series of intervals,
     * as well as filling these intervals with the necessary data
     *
     * @param array $content
     * @return array
     */
    public function calculate(array $content): array
    {
        $result = [];

        foreach ($content as $name => $department) {

            $interval = [];

            $count = array_pop($department);

            $countInterval = ceil(1 + 3.322 * log10($count));//we determine the number of intervals for this department

            $step = (int)ceil((max($department) - min($department)) / $countInterval);// interval step

            $interval[0]['from'] = $department[0]; //set start first interval

            $interval[0]['to'] = $department[0] + $step; //set end first interval

            for ($x = 1; $x < $countInterval; $x++) { //generate interval with step

                $interval[$x]['from'] = $interval[$x - 1]['to'];

                $interval[$x]['to'] = $interval[$x]['from'] + $step;
            }

            $range = $this->fill($department, $interval);

            $range['sum'] = array_sum($range);

            $result[][$name] = $range;

        }

        return $result;

    }

    /**
     * Filling intervals with values
     *
     * @param array $department
     * @param array $interval
     * @return array
     */
    private function fill(array $department, array $interval): array
    {
        $range = [];

        foreach ($interval as $key => $int) {

            $endIntervals = end($interval);

            //if $int['from'] == 0 then the data output will be determined as 1 second and not as 1 day which is more logical

            $from = Carbon::now()->subDays($int['from'] != 0 ? $int['from'] : 1)->longAbsoluteDiffForHumans(Carbon::now(), $int['from'] > 365 ? 2 : 1);

            $to = Carbon::now()->subDays($int['to'])->longAbsoluteDiffForHumans(Carbon::now(), $int['to'] > 365 ? 2 : 1);

            $rangeDescription = $this->rangeDescription($from, $to);

            foreach ($department as $numberOfDays => $number) {

                if ($int['from'] <= $number && $number < $int['to']) {

                    $range[$rangeDescription][] = $number;

                    unset($department[$numberOfDays]);

                } else {

                    $range[$rangeDescription] = isset($range[$rangeDescription]) ? count($range[$rangeDescription]) : 0;

                    break;
                }
            }

            if ($endIntervals['to'] == $int['to']) { // To calculate the quantity in the last element in the created range

                $range[$rangeDescription] = count($range[$rangeDescription]);
            }
        }

        return $range;
    }

    /**
     * You can put some sort of additional logic for forming a legend for the chart
     *
     * @param string $from
     * @param string $to
     * @return string
     */
    protected function rangeDescription(string $from, string $to): string
    {
        return 'от ' . $from . ' до ' . $to;
    }

}
