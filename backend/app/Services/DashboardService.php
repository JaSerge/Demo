<?php

namespace App\Services;

use App\Interfaces\Repositories\DashboardRepositoryInterface;
use App\Interfaces\Repositories\IUserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class DashboardService
{
    private $dashbordRepository;    
    protected $userRepository;    

    public function __construct(DashboardRepositoryInterface $dashbordRepository, IUserRepository $userRepository)
    {
        $this->dashbordRepository = $dashbordRepository;
        $this->userRepository = $userRepository;        
    }

    public function getTabs()
    {       
       $idOperator = $this->userRepository->getOperatorForUser()['items'][0]->id;       

       $result = $this->dashbordRepository->getTabs($idOperator);    
              
       return $result;
    }
   
   public function getData()
    {                
        $key = 'dashboardData';
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getData();
            Redis::set($key, json_encode($data), 'EX', 60);
        };    

        // Log::info('data ' . $key . ': ' . print_r(json_decode(Redis::get($key)),true));
        $result = json_decode(Redis::get($key));
        return $result ;
    }

    public function getOperatorData(?int $operator_id, string $locale)
    {                
        $key = 'dashboardOperatorData_' . ($operator_id === null ? 'null' : $operator_id) . '_' . $locale;;
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getOperatorData($operator_id, $locale);
            Redis::set($key, json_encode($data), 'EX', 60);
        };    
        
        $result = json_decode(Redis::get($key));
        return $result ;
    }

    public function getProcess(?int $operator_id)
    {                
        $key = 'dashboardProcess_' . ($operator_id === null ? 'null' : $operator_id);
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getProcess($operator_id);
            Redis::set($key, json_encode($data), 'EX', 60);
        };    
        
        $result = json_decode(Redis::get($key));
        return $result ;
    }    

    public function getMsisdnPorts(int $period)
    {                
        $key = 'dashboardMsisdnPorts_' . $period;
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getMsisdnPorts($period);
            Redis::set($key, json_encode($data), 'EX', 60);
        };    
        
        $result = json_decode(Redis::get($key));
        return $result ;
    }   

    public function getAvgPorts(int $period)
    {                
        $key = 'dashboardAvgPorts_' . $period;
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getAvgPorts($period);
            Redis::set($key, json_encode($data), 'EX', 60);
        };    
        
        $result = json_decode(Redis::get($key));
        return $result ;
    }  

    public function getSuccessProcesses(int $period, string $locale)
    {                
        $key = 'dashboardSuccessProcess_' . $period . '_' . $locale;
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getSuccessProcesses($period, $locale);
            Redis::set($key, json_encode($data), 'EX', 60);
        };    
        
        $result = json_decode(Redis::get($key));
        return $result ;
    }   

    public function getChangeMsisdns(int $period)
    {                
        $key = 'dashboardChangeMsisdn_' . $period;
        if (!Redis::exists($key)) {
            $data = $this->dashbordRepository->getChangeMsisdns($period);
            Redis::set($key, json_encode($data), 'EX', 60);
        };    
        
        $result = json_decode(Redis::get($key));
        return $result ;
    }       
    
}
