<?php

namespace App\Repositories;

use App;
use App\Interfaces\Repositories\DashboardRepositoryInterface;
use App\Lib\QueryManager\QueryFilter;
use App\Lib\QueryManager\QueryManager;
use App\Models\Operators;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getTabs($idOperator)
    {
        $items = Operators::select(DB::raw("distinct operators.id, operators.nm"))
            ->join('stat_key as sk', 'sk.node_id', '=', 'operators.id')
            ->whereRaw("now() between operators.sd and operators.ed");

        if(isset($idOperator))
            $items = $items->where("operators.id", (int) $idOperator);

        $items = $items->orderBy("operators.id")->get();

        return $items;
    }

    // Завершившиеся процессы с таймаутами (за последние 24 часа)
    public function getProcess(?int $operator_id) 
    {                        
        if (!isset($operator_id) ) {            
            $processes = DB::select('select operator_name, is_timeout, is_succeed from board.admin_get_count_ended_with_timeout(now() - INTERVAL \'1 day\', now())');               
        } else {            
            $processes = DB::select('select hours, is_timeout, is_succeed from board.operator_get_count_ended_with_timeout(?, now() - INTERVAL \'1 day\', now())', [$operator_id]);             
        };   

        $parameters = [];
        $is_timeout = [];
        $is_succeed = [];

        foreach ($processes as $process) {            
            $parameters[] = isset($process->operator_name) ? $process->operator_name : (isset($process->hours) ? $process->hours : null);            
            $is_timeout[] = $process->is_timeout;
            $is_succeed[] = $process->is_succeed;
        };

        $data = [
            'parameters' => $parameters,
            'series' => [
                'names' => ['is_succeed', 'is_timeout'],
                'colors' => ['#6fbd6e', '#d08977'],
            ],
            'values' => [$is_succeed, $is_timeout]                        
        ];

        return json_encode($data);     
    }        

    // Количество портированных номеров (period - количество дней 7/30/365)
    public function getMsisdnPorts(int $period) 
    {                           
        $msisdn_ports = DB::select("select operator_name, cnt_in, cnt_out from board.all_msisdn_ports(now() - make_interval(days => ?), now())", [$period]);

        $parameters = [];
        $cnt_in = [];
        $cnt_out = [];

        foreach ($msisdn_ports as $msisdn_port) {            
            $parameters[] = $msisdn_port->operator_name;            
            $cnt_in[] = $msisdn_port->cnt_in;
            $cnt_out[] = $msisdn_port->cnt_out;
        };

        $data = [
            'parameters' => $parameters,
            'series' => [
                'names' => ['cnt_in', 'cnt_out'],
                'colors' => ['#78a1d1', '#d0aa42'],
            ],
            'values' => [$cnt_in, $cnt_out]                        
        ];

        return json_encode($data);     
    }    

    // Количество успешных процессов (period - количество дней 7/30/365)
    public function getSuccessProcesses(int $period, string $locale) 
    {          
        $sql = <<<SQL
            select
                pt.id,
                pt.nm,
                coalesce(ptt.nm, pt.nm) as name
            from process_types pt  left join process_type_translations as ptt on pt.id=ptt.process_type_id and ptt.locale = :locale
            where lower(pt.nm) in ('port_personal', 'port_organization', 'return', 'reverse')
            order by case lower(pt.nm)
                when 'port_personal' then 1
                when 'port_organization' then 2
                when 'return' then 3
                when 'reverse' then 4
                else 5
            end;
        SQL;            

        $process_types = DB::select($sql, ['locale' => $locale]);

        $process_names = array_map(function($item) {
            return $item->name;
        }, $process_types);

        $sql = <<<SQL
            with p as ( 
                select operator_name, process_type, cnt from board.all_success_processes(now() - make_interval(days => :period), now())
            )
            select
            operator_name,
            max(case when lower(process_type) = 'port_personal' then cnt end) as port_personal,
            max(case when lower(process_type) = 'port_organization' then cnt end) as port_organization,
            max(case when lower(process_type) = 'return' then cnt end) as return,
            max(case when lower(process_type) = 'reverse' then cnt end) as reverse
            from p
            group by operator_name;  
        SQL;                   

        $success_processes = DB::select($sql, ['period' => $period]);
        
        $parameters = [];
        $port_personal = [];
        $port_organization = [];
        $return = [];
        $reverse = [];

        foreach ($success_processes as $success_process) {            
            $parameters[] = $success_process->operator_name;            
            $port_personal[] = $success_process->port_personal;
            $port_organization[] = $success_process->port_organization;
            $return[] = $success_process->return;
            $reverse[] = $success_process->reverse;
        };    

        $data = [
            'parameters' => $parameters,
            'series' => [
                'names' => $process_names,
                'colors' => ['#78a1d1', '#d0aa42', '#d08977', '#a786d1'],
            ],
            'values' => [$port_personal, $port_organization, $return, $reverse]                        
        ];

        return json_encode($data);     
    }    

    // Перенесенные номера
    public function getChangeMsisdns(int $period) 
    {                          
        // за сегодня  
        if ($period === 0) {
            $change_msisdns = DB::select('SELECT operator_nm, 
                to_char(in_cnt, \'999 999 999\') as in_cnt, to_char(out_cnt, \'999 999 999\') as out_cnt, 
                to_char(SUM(in_cnt) OVER(), \'999 999 999\') AS total FROM board.all_change_msisdns_today()');
        // за все время            
        } else {
            $change_msisdns = DB::select('SELECT operator_nm, 
                to_char(in_cnt, \'999 999 999\') as in_cnt, to_char(out_cnt, \'999 999 999\') as out_cnt, 
                to_char(SUM(in_cnt) OVER(), \'999 999 999\') AS total FROM board.all_change_msisdns_all_time()');
        };

        $change_msisdns_total = isset($change_msisdns[0]->total) ? $change_msisdns[0]->total : '—';

        $change_msisdns_data = array_map(function($item) {
            return [
                'operator_name' => $item->operator_nm,
                'cnt_in' => $item->in_cnt,
                'cnt_out' => $item->out_cnt,
            ];
        }, $change_msisdns);
                            
        $data = [                            
            'data' => $change_msisdns_data,
            'total' => $change_msisdns_total                
        ];
        
        return json_encode($data);     
    }        

    // Среднее время портации (period = 0 "За сегодня", иначе "За последние 7 дней")    
    public function getAvgPorts(int $period) 
    {                
        $sql = <<<SQL
            WITH x AS (
                SELECT 
                    operator_name, 
                    COALESCE(NULLIF(to_char(make_interval(secs => ROUND(EXTRACT(EPOCH FROM in_time))), 'HH24:MI:SS'), '0'), '—') AS cnt_in, 
                    COALESCE(NULLIF(to_char(make_interval(secs => ROUND(EXTRACT(EPOCH FROM out_time))), 'HH24:MI:SS'), '0'), '—') AS cnt_out                    
                FROM board.all_avg_portable_time(
                    CASE WHEN :period = 0 THEN date_trunc('day', now())
                        ELSE now() - INTERVAL '7 days'
                    END,
                    now()
                )
            )
            SELECT 
                x.*, 
                (SELECT cnt_in FROM x WHERE operator_name IS NULL LIMIT 1) AS total
            FROM x
            WHERE operator_name IS NOT NULL;
        SQL;

        $avg_ports = DB::select($sql, ['period' => $period]);

        $avg_ports_total = isset($avg_ports[0]->total) ? $avg_ports[0]->total : '—';

        $avg_ports_data = array_map(function($item) {
            return [
                'operator_name' => $item->operator_name,
                'cnt_in' => $item->cnt_in,
                'cnt_out' => $item->cnt_out,
            ];
        }, $avg_ports);     

        $data = [                            
            'data' => $avg_ports_data,
            'total' => $avg_ports_total                
        ];                  
        
        return json_encode($data);    
    }    

    // Общие данные
    public function getData() 
    {        
        // Текущие работы
        $current_work = DB::select('select oup.operator_name as operator, to_char(oup.bts, \'DD.MM.YYYY HH24:MI\') as sd, COALESCE(to_char(oup.ets, \'DD.MM.YYYY HH24:MI\'), \'—\') as ed, oup.is_unexpected
            from board.all_unavailability_periods(now()) as oup
            order by oup.bts');
        
        $timezone = config('app.timezone');
        DB::statement("SET TIME ZONE '{$timezone}'");
    
        $all_time = DB::select('select now() as current_time, p_is_work_time, p_timestamptz_for_next_period as end_period from board.all_time()');
      
        if (!empty($all_time)) {
            $all_time_row = $all_time[0];            
        };
        
        $user_timezone = config('app.user_timezone');
        DB::statement("SET TIME ZONE '{$user_timezone}'");            

        // Номерная емкость операторов
        $all_msisdn = DB::select('select operator_name, to_char(quantity, \'999 999 999\') AS quantity, 
            to_char(SUM(quantity) OVER(), \'999 999 999\') AS total from board.all_msisdn_ranges() order by operator_name');
        $all_msisdn_total = isset($all_msisdn[0]->total) ? $all_msisdn[0]->total : 0;
        $all_msisdn_data = array_map(function($item) {
            return [
                'operator_name' => $item->operator_name,
                'quantity' => $item->quantity,
            ];
        }, $all_msisdn);

        // Количество портированных номеров (за все время)
        $today_msisdn_ports = DB::select('SELECT operator_name, cnt_in, cnt_out, SUM(cnt_in) OVER() AS total FROM board.all_msisdn_ports(date_trunc(\'day\', now()),
            date_trunc(\'day\', now()) + interval \'1 day\' - interval \'1 second\')');
        $today_msisdn_ports_total = isset($today_msisdn_ports[0]->total) ? $today_msisdn_ports[0]->total : 0;
        $today_msisdn_ports_data = array_map(function($item) {
            return [
                'operator_name' => $item->operator_name,
                'cnt_in' => $item->cnt_in,
                'cnt_out' => $item->cnt_out,
            ];
        }, $today_msisdn_ports);

        $all_msisdn_ports = DB::select('SELECT operator_name, cnt_in, cnt_out, SUM(cnt_in) OVER() AS total FROM board.all_msisdn_ports(\'2000-01-01 00:00:00\'::timestamp,
            get_end_tz())');
        $all_msisdn_ports_total = isset($all_msisdn_ports[0]->total) ? $all_msisdn_ports[0]->total : 0;
        $all_msisdn_ports_data = array_map(function($item) {
            return [
                'operator_name' => $item->operator_name,
                'cnt_in' => $item->cnt_in,
                'cnt_out' => $item->cnt_out,
            ];
        }, $all_msisdn_ports);    

         // Итоговый Json
        $data = [
            'work_time' => [
                'current_time' => $all_time_row->current_time,                
                'is_work_time' => $all_time_row->p_is_work_time,
                'end_period' => $all_time_row->end_period
            ],  
            'current_works' => $current_work,
            'all_msisdn' => 
                (object)[                    
                    'data' => $all_msisdn_data, 
                    'total' => $all_msisdn_total
                ],                
        ];                      

        return json_encode($data);              
    }    

    // Данные по оператору (если operator_id = null, то по "Общей сводке")
    public function getOperatorData(?int $operator_id, string $locale) 
    {                  
        // Активные процессы                    
        if (!isset($operator_id) ) {
            $act_msisdn = DB::select('select admin_active_msisdns as cnt_in, 0 as cnt_out from board.admin_active_msisdns()');     
            $act_processes = DB::select('select admin_active_processes as cnt_in, 0 as cnt_out from board.admin_active_processes()');               
        } else {
            $act_msisdn = DB::select('select cnt_in, cnt_out from board.operator_active_msisdns(?)', [$operator_id]);     
            $act_processes = DB::select('select cnt_in, cnt_out from board.operator_active_processes(?)', [$operator_id]);             
        };        

        if (!empty($act_msisdn)) {
            $act_msisdn_row = $act_msisdn[0];
        }

        if (!empty($act_processes)) {
            $act_processes_row = $act_processes[0];
        }        

        // Ошибочные данные в процессах
        if (!isset($operator_id) ) {
            $process_errors = DB::select('
                with tbl as (
                    SELECT 
                    null as donor_nm,
                    operator_nm as recipient_nm, 
                    last_error_nm, 
                    to_char(last_error_dt, \'DD.MM.YYYY HH24:MI\') as last_error_dt, 
                    perc,
                    error_cnt, 
                    SUM(error_cnt) OVER() AS error_total,
                    SUM(total_cnt) OVER() AS total,
                    row_number() over (ORDER BY last_error_dt desc) rn_last_error_dt                
                    FROM board.admin_logical_errors(now() - INTERVAL \'1 day\', now(), ?)
                )
                SELECT
                    donor_nm, 
                    recipient_nm,
                    last_error_nm,
                    perc,
                    error_cnt, 
                    last_error_dt, 
                    error_total,
                    total,
                    (SELECT last_error_dt FROM tbl where rn_last_error_dt = 1 LIMIT 1) AS last_error_dt_last_row,
                    (SELECT last_error_nm FROM tbl where rn_last_error_dt = 1 LIMIT 1) AS last_error_nm_last_row
                FROM tbl', [$locale]);
        } else {
            $process_errors = DB::select('
                with tbl as (
                    SELECT 
                    donor_nm,
                    recipient_nm, 
                    last_error_nm, 
                    to_char(last_error_dt, \'DD.MM.YYYY HH24:MI\') as last_error_dt, 
                    perc,
                    error_cnt, 
                    SUM(error_cnt) OVER() AS error_total,
                    SUM(total_cnt) OVER() AS total,
                    row_number() over (ORDER BY last_error_dt desc) rn_last_error_dt                
                    FROM board.operator_logical_errors(?, now() - INTERVAL \'1 day\', now(), ?)                
                )
                SELECT
                    donor_nm, 
                    recipient_nm,
                    last_error_nm,
                    perc,
                    error_cnt, 
                    last_error_dt, 
                    error_total,
                    total,
                    (SELECT last_error_dt FROM tbl where rn_last_error_dt = 1 LIMIT 1) AS last_error_dt_last_row,
                    (SELECT last_error_nm FROM tbl where rn_last_error_dt = 1 LIMIT 1) AS last_error_nm_last_row
                FROM tbl', [$operator_id, $locale]);
        };

        $process_errors_data = array_map(function($item) {
            return [
                'donor_nm' => $item->donor_nm,
                'recipient_nm' => $item->recipient_nm,
                'perc' => $item->perc,
                'error_cnt' => $item->error_cnt,
                'last_error_dt' => $item->last_error_dt,                
                'last_error_nm' => $item->last_error_nm
            ];
        }, $process_errors);

        $process_errors_total = isset($process_errors[0]->error_total) ? $process_errors[0]->error_total : 0;
        $process_total = isset($process_errors[0]->total) ? $process_errors[0]->total : 0;
        $process_last_error_dt_last_row = isset($process_errors[0]->last_error_dt_last_row) ? $process_errors[0]->last_error_dt_last_row : '—';
        $process_last_error_nm_last_row = isset($process_errors[0]->last_error_nm_last_row) ? $process_errors[0]->last_error_nm_last_row : '—';        

        // Ошибки API (за последние 24 часа)
        $api_errors = DB::select('
            with tbl as (
                SELECT 
                operator_name, 
                error_cnt, 
                to_char(last_error_dt, \'DD.MM.YYYY HH24:MI\') as last_error_dt, 
                to_char(last_success_dt, \'DD.MM.YYYY HH24:MI\') as last_success_dt,
                last_error_dsc, 
                SUM(error_cnt) OVER() AS error_total,                
                row_number() over (ORDER BY last_error_dt desc) rn_last_error_dt,
                row_number() over (ORDER BY last_success_dt desc) rn_last_success_dt                
                FROM board.all_sending_errors(?, now() - INTERVAL \'1 day\', now())
            )
            select 
            operator_name, 
            error_cnt, 
            last_error_dt, 
            last_success_dt,
            last_error_dsc,
            error_total,            
            (SELECT last_error_dt FROM tbl where rn_last_error_dt = 1 LIMIT 1) AS last_error_dt_last_row,
            (SELECT last_error_dsc FROM tbl where rn_last_error_dt = 1 LIMIT 1) AS last_error_dsc_last_row,
            (SELECT last_success_dt FROM tbl where rn_last_success_dt = 1 LIMIT 1) AS last_success_dt_last_row
            from tbl', [$operator_id]);
        
        $api_errors_data = array_map(function($item) {
            return [
                'operator_name' => $item->operator_name,
                'error_cnt' => $item->error_cnt,
                'last_error_dt' => $item->last_error_dt,
                'last_success_dt' => $item->last_success_dt,
                'last_error_dsc' => $item->last_error_dsc
            ];
        }, $api_errors);    
        
        $api_errors_total = isset($api_errors[0]->error_total) ? $api_errors[0]->error_total : 0;
        $api_last_error_dt_last_row = isset($api_errors[0]->last_error_dt_last_row) ? $api_errors[0]->last_error_dt_last_row : '—';
        $api_last_error_dsc_last_row = isset($api_errors[0]->last_error_dsc_last_row) ? $api_errors[0]->last_error_dsc_last_row : '—';
        $api_last_success_dt_last_row = isset($api_errors[0]->last_success_dt_last_row) ? $api_errors[0]->last_success_dt_last_row : '—';

        // Итоговый Json
        $data = [
            'active_processes' => [
                'cnt_in' =>  $act_processes_row->cnt_in,
                'cnt_out' => $act_processes_row->cnt_out
            ],  
            'active_msisdns' => [
                'cnt_in' => $act_msisdn_row->cnt_in,
                'cnt_out' => $act_msisdn_row->cnt_out
            ],                      
            'process_errors' => 
                (object)[                    
                    'data' => $process_errors_data,                     
                    'error_percent' => $process_total != 0 ? round($process_errors_total / $process_total * 100) : 0,
                    'error_total' => $process_errors_total,
                    'process_last_error_dt_last_row' => $process_last_error_dt_last_row,
                    'process_last_error_nm_last_row' => $process_last_error_nm_last_row                    
                ],    
            'api_errors' => 
                (object)[                    
                    'data' => $api_errors_data,                     
                    'error_total' => $api_errors_total,
                    'api_last_error_dt_last_row' => $api_last_error_dt_last_row,
                    'api_last_error_dsc_last_row' => $api_last_error_dsc_last_row,
                    'api_last_success_dt_last_row' => $api_last_success_dt_last_row
                ],       
          
        ];                      

        return json_encode($data);     
    }

}
