<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Session;

class ExcelExport implements FromArray
{

    public function array(): array
    {
        $downloadAry = Session::get('download.exceltoexport',[]);
        Session::forget('download.exceltoexport');
        return $downloadAry;
    }
}