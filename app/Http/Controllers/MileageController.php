<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\{Company, Mileage, Project, Purchases, Categorys};
use Illuminate\View\View;

class MileageController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct () {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     *
     * @return Factory|View
     */
    public function mileagelist (Request $request) {

        $type = auth()->user()->is_admin;
        if ($type == '1') {
            $data['mileage_list'] = Mileage::where('status','<>','D')->orderByDesc('created_at')->with('employee:id,firstname,lastname')->get();
        }
        else {
            $data['mileage_list'] = Auth::user()->mileage()->where('status','A')->orderByDesc('created_at')->get();

        }

        $data['companies'] = Company::all();

        return view('mileagelist', $data);
    }

    /**
     * @param Request $request
     */
    public function addmileage (Request $request) {
        $mileagearray = [
            'emp_id'        => auth()->user()->id,
            'company'       => $request->companyname,
            'date'          => $request->date,
            'vehicle'       => $request->vehicle,
            'kilometers'    => $request->kilometers,
            'reasonmileage' => $request->reasonformileage,
        ];

        DB::table('mileages')->insert($mileagearray);

    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    function updatemileage (Request $request) {
        $emp_id = auth()->user()->id;
        $conditions = ['id' => $request->editmileage_id, 'emp_id' => $emp_id];
        DB::table('mileages')->where($conditions)->update([
            'date'          => $request->date,
            'company'       => $request->companyname,
            'vehicle'       => $request->vehicle,
            'kilometers'    => $request->kilometers,
            'reasonmileage' => $request->reasonformileage,
        ]);
        $msg = 'Mileage updated successfully';

        return redirect()->back()->with('alert-info', $msg);
    }

    /**
     * @param $id
     *
     * @return Factory|View
     */
    function get_mileage ($id) {
        $data['companies'] = Company::all();
        $emp_id = auth()->user()->id;
        $type = auth()->user()->id_admin;
        if ($type == 1) {
            $data['mileage_list'] = DB::table('mileages')->first();
        }
        else {
            $data['mileagedetails'] = DB::table('mileages')->where(['id' => $id, 'emp_id' => $emp_id])->first();
        }

        return view('ajaxview.editmileage', $data);
    }

    /**
     * @param $id
     */
    function deletemileage ($id) {
        $emp_id = auth()->user()->id;
        $conditions = ['id' => $id, 'emp_id' => $emp_id];
        DB::table('mileages')->where($conditions)->update(['status' => 'D']);

    }

}
