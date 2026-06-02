<?php

namespace App\Interfaces\Repositories;

interface IUnavailabilityPeriodsRepository
{
    public function paginate(array $data): array;

    public function getUnPeriod(int $id): array;

    public function getAvailableOperators();

    public function edit(?int $id, array $data): array;

    public function close(?int $id): array;   
}
