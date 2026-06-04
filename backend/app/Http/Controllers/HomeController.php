<?php
namespace App\Http\Controllers;

use App;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getTabs()
    {
        $items = $this->dashboardService->getTabs();
        return $items;
    }

    public function getData(Request $request)
    {        
        $items = $this->dashboardService->getData();
        return $items;
    }

    public function getOperatorData(Request $request)
    {
        $operator_id = $request->operator_id;
        $operator_id = is_numeric($operator_id) ? (int)$operator_id : null;

        $locale = $request->locale;

        $items = $this->dashboardService->getOperatorData($operator_id, $locale);
        return $items;
    }

    public function getProcess(Request $request)
    {
        $operator_id = $request->operator_id;
        $operator_id = is_numeric($operator_id) ? (int)$operator_id : null;

        $items = $this->dashboardService->getProcess($operator_id);
        return $items;
    }

    public function getMsisdnPorts(Request $request)
    {
        $period = $request->period;        

        $items = $this->dashboardService->getMsisdnPorts($period);
        return $items;
    }

    public function getSuccessProcesses(Request $request, string $locale)
    {
        $period = $request->period;        
        $locale = $request->locale;

        $items = $this->dashboardService->getSuccessProcesses($period, $locale);
        return $items;
    }    

    public function getChangeMsisdns(Request $request)
    {
        $period = $request->period;        

        $items = $this->dashboardService->getChangeMsisdns($period);
        return $items;
    }

    public function getAvgPorts(Request $request)
    {
        $period = $request->period;        

        $items = $this->dashboardService->getAvgPorts($period);
        return $items;
    }    
    
}