<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric as V1betaMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Vormkracht10\Analytics\Facades\Analytics as VormAnalytics;
use Vormkracht10\Analytics\Period as VormPeriod;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Spatie\Analytics\AnalyticsClient;
use Spatie\Analytics\TypeCaster;
use App\Http\Controllers\Traits\AnalyticsTrait;

use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Illuminate\Http\Response;
use Spatie\Analytics\OrderBy;
use Illuminate\Pagination\LengthAwarePaginator;

class AnalyticsController extends Controller
{
    use AnalyticsTrait;
    public function index(Request $request, AnalyticsClient $analyticsClient)
    {
        $check = false;
        $data = array(1, 7, 14, 30);

        $activeUsers = VormAnalytics::activeUsers();
        $pageviews = VormAnalytics::totalViews(VormPeriod::days(1));
        $linkView =  Cache::remember('linkView_daliy', strtotime(Carbon::tomorrow()) - strtotime(Carbon::now()), function () {
            return Analytics::fetchMostVisitedPages(Period::days(1), $maxResults = 15);
        });

        $response_country =  $analyticsClient->getAnalyticsService()->runRealtimeReport(
            array(
                'property' => 'properties/' . env('ANALYTICS_PROPERTY_ID'),
                'dimensions' =>  [new Dimension([
                    'name' => 'country'
                ])],
                'metrics' =>   [
                    new V1betaMetric(
                        [
                            'name' => 'activeUsers',
                        ]
                    )
                ]
            )
        );

        $pagePath = Analytics::get(
            Period::days(1),
            ["screenPageViews"],
            ['pagePath', 'pageTitle'],
        );

        $response_device = $analyticsClient->getAnalyticsService()->runRealtimeReport(
            array(
                'property' => 'properties/' . env('ANALYTICS_PROPERTY_ID'),
                'dimensions' =>  [new Dimension([
                    'name' => 'deviceCategory'
                ])],
                'metrics' =>   [
                    new V1betaMetric(
                        [
                            'name' => 'activeUsers',
                        ]
                    )
                ]
            )
        );

        // $response_page_path = Analytics::get(Period::days(1), ["activeUsers"], ["pagePath"]);

        // dd($response_page_path);
        $country = $this->formatResponse($response_country, ['country'], ['count']);
        $device = $this->formatResponse($response_device, ['deviceCategory'], ["count"]);
        $data_analytics = Analytics::get(Period::days(30), ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate"], ["date"]);
        $data = [
            "live_users" => $activeUsers,
            "pageViews" => $pageviews,
            "linkView" => $linkView,
            "countrys" => $country,
            "devices" => $device,
            "pagePath" => $pagePath,
            "data_analytics" => $data_analytics
        ];
        return response()->json($data, Response::HTTP_OK);
    }
    public function getByDay($day)
    {
        $day_now = Carbon::now();

        $activeUsers = VormAnalytics::activeUsers();
        $check = true;
        $pageviews = VormAnalytics::totalViews(VormPeriod::days($day));
        $linkView =  Analytics::fetchMostVisitedPages(Period::days($day), $maxResults = 15);
        $country =
            Analytics::get(
                Period::days($day),
                ["totalUsers"],
                ["country"],
            );
        $device =
            Analytics::get(
                Period::days($day),
                ["totalUsers"],
                ["deviceCategory"],
            );
        $pagePath = Analytics::get(
            Period::days($day),
            ["screenPageViews"],
            ['pagePath', "pageTitle"],
        );
        $data_analytics = Analytics::get(Period::days(30), ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate"], ["date"]);
        $data = [
            "live_users" => $activeUsers,
            "pageViews" => $pageviews,
            "linkView" => $linkView,
            "countrys" => $country,
            "devices" => $device,
            "pagePath" => $pagePath,
            "data_analytics" => $data_analytics
        ];
        return response()->json($data, Response::HTTP_OK);
    }
    public function analytics($date)
    {
        $activeUsers = VormAnalytics::activeUsers();
        $day_now = Carbon::now();

        $data_analytics = Analytics::get(Period::days($date), ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate"], ["date"]);

        $dimensionFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'pageTitle',
                'string_filter' => new StringFilter([
                    'match_type' => MatchType::CONTAINS,
                    'value' => "The SOL City VR Showroom",
                ]),
            ]),
        ]);


        $analyticsData_project = Analytics::get(
            Period::days($date),
            ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate"],
            ['pageTitle'],
            20,
            [],
            0,
            $dimensionFilter
        );

        $analyticsData_project_date =  Analytics::get(
            Period::days($date),
            ["sessions", "bounceRate", "screenPageViews"],
            ['pageTitle', 'date'],
            20,
            [],
            0,
            $dimensionFilter
        );
        $country =
            Analytics::get(
                Period::days($date),
                ["totalUsers"],
                ["country", "pageTitle",],
                0,
                [
                    OrderBy::dimension('pageTitle', false),
                    OrderBy::metric('totalUsers', true),
                ],
                0,
                $dimensionFilter
            );


        $device = Analytics::get(
            Period::days($date),
            ["totalUsers"],
            ["deviceCategory", "pageTitle",],
            0,
            [
                OrderBy::dimension('pageTitle', false),
                OrderBy::metric('totalUsers', true),
            ],
            0,
            $dimensionFilter
        );

        $pagePath  = Analytics::get(
            Period::days($date),
            ["screenPageViews", "screenPageViewsPerSession", "totalUsers"],
            ["pagePathPlusQueryString", "pageTitle"],
            0,
            [
                OrderBy::dimension('pagePathPlusQueryString', false),
                OrderBy::metric('screenPageViews', true),
            ],
            0,
            $dimensionFilter
        );
        $data = [
            "live_users" => $activeUsers,
            "countrys" => $country,
            "devices" => $device,
            "pagePath" => $pagePath,
            "data_analytics" => $data_analytics,
            "analyticsData_project" => $analyticsData_project,
            "analyticsData_project_date" => $analyticsData_project_date

        ];
        return response()->json($data, Response::HTTP_OK);
    }

    public function queryDashboard($date, $name)
    {
        $check = false;
        $project = $name;
        $day_now = Carbon::now();
        $count = 0;
        $data_analytics = Analytics::get(Period::days($date), ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate"], []);

        $activeUsers = VormAnalytics::activeUsers();

        $dimensionFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'pageTitle',
                'string_filter' => new StringFilter([
                    'match_type' => MatchType::CONTAINS,
                    'value' => $project . " VR Showroom",
                ]),
            ]),
        ]);

        //$title = "ga:pageTitle==The SOL City VR Showroom";
        $analyticsData_project = Analytics::get(
            Period::days($date),
            ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate", "screenPageViews"],
            ['pageTitle'],
            20,
            [],
            0,
            $dimensionFilter
        );

        $analyticsData_project_date =  Analytics::get(
            Period::days($date),
            ["sessions", "bounceRate", "screenPageViews", "newUsers"],
            ['pageTitle', 'date'],
            20,
            [],
            0,
            $dimensionFilter
        );
        $country =
            Analytics::get(
                Period::days($date),
                ["totalUsers"],
                ["country", "pageTitle",],
                0,
                [
                    OrderBy::dimension('pageTitle', false),
                    OrderBy::metric('totalUsers', true),
                ],
                0,
                $dimensionFilter
            );


        $device = Analytics::get(
            Period::days($date),
            ["totalUsers"],
            ["deviceCategory", "pageTitle",],
            0,
            [
                OrderBy::dimension('pageTitle', false),
                OrderBy::metric('totalUsers', true),
            ],
            0,
            $dimensionFilter
        );

        $pagePath  = Analytics::get(
            Period::days($date),
            ["screenPageViews", "screenPageViewsPerSession", "totalUsers"],
            ["pagePathPlusQueryString", "pageTitle"],
            0,
            [
                OrderBy::dimension('pagePathPlusQueryString', false),
                OrderBy::metric('screenPageViews', true),
            ],
            0,
            $dimensionFilter
        );

        $data = [
            "live_users" => $activeUsers,
            "countrys" => $country,
            "devices" => $device,
            "pagePath" => $pagePath,
            "data_analytics" => $data_analytics,
            "analyticsData_project" => $analyticsData_project,
            "analyticsData_project_date" => $analyticsData_project_date

        ];

        return response()->json($data, Response::HTTP_OK);
    }

    public function queryDashboardSale($date, $name)
    {
        $check = false;
        $project = $name;
        $day_now = Carbon::now();
        $count = 0;
        $data_analytics = Analytics::get(Period::days($date), ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate"], []);
        $activeUsers = VormAnalytics::activeUsers();


        $dimensionFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'pageTitle',
                'string_filter' => new StringFilter([
                    'match_type' => MatchType::CONTAINS,
                    'value' => $project . " VR Showroom",
                ]),
            ]),
        ]);

        //$title = "ga:pageTitle==The SOL City VR Showroom";
        $analyticsData_project = Analytics::get(
            Period::days($date),
            ["newUsers", "sessions", "bounceRate", "averageSessionDuration", "engagementRate", "screenPageViews"],
            ['pageTitle'],
            20,
            [],
            0,
            $dimensionFilter
        );

        $analyticsData_project_date =  Analytics::get(
            Period::days($date),
            ["sessions", "bounceRate", "screenPageViews", "newUsers"],
            ['pageTitle', 'date'],
            20,
            [],
            0,
            $dimensionFilter
        );
        $country =
            Analytics::get(
                Period::days($date),
                ["totalUsers"],
                ["country", "pageTitle",],
                0,
                [
                    OrderBy::dimension('pageTitle', false),
                    OrderBy::metric('totalUsers', true),
                ],
                0,
                $dimensionFilter
            );


        $device = Analytics::get(
            Period::days($date),
            ["totalUsers"],
            ["deviceCategory", "pageTitle",],
            0,
            [
                OrderBy::dimension('pageTitle', false),
                OrderBy::metric('totalUsers', true),
            ],
            0,
            $dimensionFilter
        );

        $pagePath  = Analytics::get(
            Period::days($date),
            ["screenPageViews", "screenPageViewsPerSession", "totalUsers"],
            ["pagePathPlusQueryString", "pageTitle"],
            0,
            [
                OrderBy::dimension('pagePathPlusQueryString', false),
                OrderBy::metric('screenPageViews', true),
            ],
            0,
            $dimensionFilter
        );

        $data = [
            "live_users" => $activeUsers,
            "countrys" => $country,
            "devices" => $device,
            "pagePath" => $pagePath,
            "data_analytics" => $data_analytics,
            "analyticsData_project" => $analyticsData_project,
            "analyticsData_project_date" => $analyticsData_project_date

        ];
        return response()->json($data, Response::HTTP_OK);
    }

    public function sale_viewer(Request $request, $name)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $dimensionFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'pageTitle',
                'string_filter' => new StringFilter([
                    'match_type' => MatchType::CONTAINS,
                    'value' => $name . " VR Showroom",
                ]),
            ]),
        ]);

        $sale_viewer =
            Analytics::get(
                Period::days(100),
                ["averageSessionDuration", "bounceRate", "screenPageViews", "newUsers", "sessions", "totalUsers"],
                ['pagePath', 'pageTitle'],
                10000,
                [
                    OrderBy::dimension('pageTitle', false),
                    OrderBy::metric('screenPageViews', true),
                ],
                0,
                $dimensionFilter

            );

        return $sale_viewer;
    }
}
