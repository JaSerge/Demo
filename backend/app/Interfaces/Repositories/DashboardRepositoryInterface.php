<?php

namespace App\Interfaces\Repositories;

interface DashboardRepositoryInterface
{
    public function getTabs($idOperator);

    public function getData();

    public function getOperatorData(?int $operator_id, string $locale);

    public function getAvgPorts(int $period);

    public function getChangeMsisdns(int $period);    
    
    public function getSuccessProcesses(int $period, string $locale);
}
