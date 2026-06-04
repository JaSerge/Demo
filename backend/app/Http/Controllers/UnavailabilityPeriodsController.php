<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnavailabilityPeriods\Create;
use App\Interfaces\Repositories\IUnavailabilityPeriodsRepository;
use App\Services\UnavailabilityPeriodsService;
use Vendor\ApiCore\Constants\LogActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class UnavailabilityPeriodsController extends Controller
{

    protected $unavailabilityPeriodsService;
    protected $unavailabilityPeriodsRepo;    

    public function __construct(UnavailabilityPeriodsService $unavailabilityPeriodsService, IUnavailabilityPeriodsRepository $unavailabilityPeriodsRepo)
    {
        $this->unavailabilityPeriodsService = $unavailabilityPeriodsService;
        $this->unavailabilityPeriodsRepo = $unavailabilityPeriodsRepo;        
    }

    public function getUnavailabilityPeriods(Request $request)
    {

        $data = $request->all();
        [$items, $count] = $this->unavailabilityPeriodsService->paginate($data);        

        return response()->json([
            "items" => $items,
            "count" => $count
        ]);
    }

    public function getAvailableOperators()
    {
        [$items, $count] = $this->unavailabilityPeriodsService->getAvailableOperators();

        return response()->json([
            "items" => $items,
            "count" => $count
        ]);
    }

    public function create(Create $request)
    {
        $data = $request->all();       
        $this->unavailabilityPeriodsService->edit(null, $data);
        return $this->makeAlert(__('unavailabilityPeriods.alerts.createdSuccessfully'));
    }

    public function update(Request $request, $id)
    {        
        $data = $request->all();
        $this->unavailabilityPeriodsService->edit((int) $id, $data);
        return $this->makeAlert(__('unavailabilityPeriods.alerts.updatedSuccessfully'));
    }    

    public function close($id)
    {                
        $this->unavailabilityPeriodsService->close((int) $id);
        return $this->makeAlert(__('unavailabilityPeriods.alerts.closedSuccessfully'));
    }  

    public function getUnPeriod($id)    
    {
        $data = $this->unavailabilityPeriodsService->getUnPeriod($id);

        return $data['item'] ?? [];
    }    

 
}
