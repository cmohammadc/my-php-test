<?php

namespace App\Repositories\Call\CallTypes;

use App\Call;
use App\Employee;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class UrgentCallRepository implements CallTypesRepositoryInterface
{
    const DEFAULT_PRIORITY = "high";
    const DEFAULT_STATE = "waiting";
    const DOING_STATE = "doing";
    const BUSY_EMPLOYEE = "busy";
    const SUCCESS = "Successfully added";
    const FAILED_TO_ADD = "failed to add it";
    const SERVER_ERROR_CODE = 503;
    const SUCCESS_CODE = 200;
                
    private $hash;
    private $employee;

    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
    }

    public function setData($hash)
    {
        $this->hash = $hash;
    }

    protected function save($state = self::DEFAULT_STATE)
    {
        $call = new Call;
        $call->hash = $this->hash;
        $call->priority = self::DEFAULT_PRIORITY;
        $call->state = $state;
        $call->save();
        return $call;
    }

    public function initOpration()
    {
        $call = $this->save();
        if ($this->employee->isThereFreeEmployee()) {
            $employee = $this->employee->getFreeEmployee();

            DB::beginTransaction();
            try {
                // update employee status
                $employee->state = self::BUSY_EMPLOYEE;
                $employee->save();
                // update call data
                $call->employee_id = $employee->id;
                $call->state = self::DOING_STATE;
                $call->save();
                DB::commit();
            } catch (Exception $ex) {
                DB::rollback();
                return ["statusCode" => self::SERVER_ERROR_CODE, "message" => self::FAILED_TO_ADD];
            }
        }

        return ["statusCode" => self::SUCCESS_CODE, "message" => self::SUCCESS];
    }
}
