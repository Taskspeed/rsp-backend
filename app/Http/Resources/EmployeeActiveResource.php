<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeActiveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    protected array $reassignedControlNos;

    public function __construct($resource, array $reassignedControlNos = [])
    {
        parent::__construct($resource);
        $this->reassignedControlNos = $reassignedControlNos;
    }

    public function toArray($request)
    {
        return [
            'ControlNo'   => $this->ControlNo,
            'Name' => $this->Name4,
            'Designation' => $this->Designation,
            'Status'      => $this->Status,
            'Office'      => $this->Office,
            'Office2'       => $this->office2,
            'Group'       => $this->group,
            'Division'       => $this->division,
            'Section'       => $this->section,
            'Unit'       => $this->unit,
            're_assign'   => in_array($this->ControlNo, $this->reassignedControlNos),
        ];
    }
}
