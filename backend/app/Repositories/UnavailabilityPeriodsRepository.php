<?php

namespace App\Repositories;

use App\Filters\UnavailabilityPeriodsFilter;
use App\Interfaces\Repositories\IUnavailabilityPeriodsRepository;
use App\Lib\QueryManager\QueryManager;
use App\Models\Operators;
use App\Models\OperatorUnavailabilityPeriod;
use Illuminate\Support\Facades\DB;
use Log;

class UnavailabilityPeriodsRepository implements IUnavailabilityPeriodsRepository
{

    public function paginate(array $data): array
    {
        $builder = OperatorUnavailabilityPeriod::from('operator_unavailability_periods as oup')->select([
                'oup.id',
                'oup.operator_id',
                'o.nm',
                'oup.dsc',
                'oup.bts',
                'oup.ets',
                'oup.is_unexpected',
                DB::raw("case when oup.ets <= now() then 1 else 0 end as is_closed")                
            ]
        )
            ->join('operators as o', function ($join) {
                $join->on('o.id', '=', 'oup.operator_id')->select('o.nm')
                    ->whereRaw('now() between o.sd and o.ed');                    
            });

        $filter = new UnavailabilityPeriodsFilter();

        $qm = new QueryManager($data, []);
        $qm->setOrderNullLast(true);
        $qm->filter($filter, $builder);        
        $qm->sort($builder);           
        
      /*  Log::info('UnavailabilityPeriods SQL with filters:', [
            'sql'      => $builder->toSql(),
            'bindings' => $builder->getBindings(),
        ]);*/

        $count = $builder->count();
        $qm->paginate($builder);
        
        $items = $builder->get();

        return [$items, $count];
    }

    public function getUnPeriod(int $id): array
    {
        $item = OperatorUnavailabilityPeriod::from('operator_unavailability_periods as oup')->select(                
                'oup.operator_id',
                'o.nm',
                'oup.dsc',
                'oup.bts',
                'oup.ets',
                'oup.is_unexpected'                                            
        )
            ->join('operators as o', function ($join) {
                $join->on('o.id', '=', 'oup.operator_id')->select('o.nm')
                    ->whereRaw('now() between o.sd and o.ed');                    
            })
            ->where('oup.id', '=', $id);  

           /* Log::info('getUnPeriod SQL with filters:', [
                'item'      => $item->toSql(),
                'bindings' => $item->getBindings(),
            ]);*/

        return [
            'item' => $item->first(),
        ];                
    }

    public function getAvailableOperators()
    {
        $builder = Operators::from('operators as op')->select('op.id' , 'op.nm', 'op.is_viewer')
            ->join('operator_types as ot', 'op.operator_type_id', '=', 'ot.id')
            ->where('ot.code', '=', 'mobile')
            ->whereRaw('now() between op.sd and op.ed');        
            
       // Log::debug(['$builder(SQL)'=> $builder->toSql()]);

        $items = $builder->get();

        $count = $items->count();

        return [$items, $count];
    }

    public function edit(?int $id, array $data): array
    {
        ["dsc" => $dsc, "bts" => $bts, "ets" => $ets, "is_unexpected" => $is_unexpected] = $data;
        $operator_id = $data['operators']['id'] ?? ($data['operator_id'] ?? null);

        $result = (array) DB::connection("db_main")->selectOne(
            "call operator_unavailability_period_save(?::integer, ?::integer, ?::integer, ?::text, date_trunc('second', ?::timestamptz), date_trunc('second', ?::timestamptz), ?::boolean)",
            [
                $id, // p_id (INOUT)
                null, // p_is_error (INOUT)
                $operator_id,
                $dsc,
                $bts,
                $ets,
                $is_unexpected,
            ]
        );

        return [
            'id' => (int)($result['p_id'] ?? ($id ?? 0)),
            'code' => (int)($result['p_is_error'] ?? 0),
        ];
    }    

    public function close(?int $id): array
    {        
        $result = (array) DB::connection("db_main")->selectOne(
            "call operator_unavailability_period_close(?::integer, ?::integer)",
            [
                $id, 
                null, // p_is_error (INOUT)
            ]
        );

        return [            
            'code' => (int)($result['p_is_error'] ?? 0),
        ];
    }           
    

}
