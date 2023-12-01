<?php

namespace App\Http\Controllers\Traits;

use Spatie\Analytics\TypeCaster;

trait AnalyticsTrait
{

    function formatResponse($response, array $dimensions = [], array $metrics = [])
    {

        $result = collect();
        $typeCaster = resolve(TypeCaster::class);
        foreach ($response->getRows() as $row) {

            $rowResult = [];

            foreach ($row->getDimensionValues() as $i => $dimensionValue) {

                $rowResult[$dimensions[$i]] =
                    $typeCaster->castValue($dimensions[$i], $dimensionValue->getValue());
            }

            foreach ($row->getMetricValues() as $i => $metricValue) {
                $rowResult[$metrics[$i]] =
                    $typeCaster->castValue($metrics[$i], $metricValue->getValue());
            }

            $result->push($rowResult);
        }
        return $result;
    }
}
