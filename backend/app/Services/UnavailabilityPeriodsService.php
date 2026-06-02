<?php

namespace App\Services;

use App\Interfaces\Repositories\IUnavailabilityPeriodsRepository;
use App\Models\OperatorUnavailabilityPeriod;
use App\Exceptions\UnavailablePeriods\FailUpdateUnPeriodException;
use App\Exceptions\UnavailablePeriods\FailCloseUnPeriodException;
use App\Models\Operators;
use Log;

class UnavailabilityPeriodsService
{
    protected $logService;
    protected $unavailPeriodsRepo;

    public function __construct(IUnavailabilityPeriodsRepository $unavailPeriodsRepo, LogService $logService)
    {
        $this->logService = $logService;
        $this->unavailPeriodsRepo = $unavailPeriodsRepo;
    }  

    public function paginate(array $data): array
    {
        $result = $this->unavailPeriodsRepo->paginate($data);
        $this->logService->logAction(null,  LogActions::PAGINATION, OperatorUnavailabilityPeriod::class);
        return $result;
    }

    public function getAvailableOperators()
    {
        $result = $this->unavailPeriodsRepo->getAvailableOperators();
        $this->logService->logAction(null,  LogActions::VIEW, Operators::class);
        return $result;
    }

    public function getUnPeriod($id)
    {
        $unPeriod = $this->unavailPeriodsRepo->getUnPeriod($id);        
        $this->logService->logAction($id, LogActions::VIEW, OperatorUnavailabilityPeriod::class);
        return $unPeriod;
    }    

    public function edit(?int $id, array $data): int    
    {        
        $action = isset($id) ? LogActions::UPDATE : LogActions::CREATE;
        
        ['id' => $savedId, 'code' => $code] = $this->unavailPeriodsRepo->edit($id, $data);

        if ($code !== 0) {
            throw new FailUpdateUnPeriodException($code);
        }

        $this->logService->logAction(
            $savedId ?: $id,
            $action,
            OperatorUnavailabilityPeriod::class
        );

        return $savedId;
    }

    public function close(?int $id)
    {                                
        ['code' => $code] = $this->unavailPeriodsRepo->close($id);

        if ($code !== 0) {
            throw new FailCloseUnPeriodException($code);
        }

        $this->logService->logAction($id, LogActions::DELETE, OperatorUnavailabilityPeriod::class);
        
    }    
}
