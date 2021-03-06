<?php

namespace App\Http\Controllers;

use App\User;

use App\{Expenses,Employee_detail};
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\{Rule,ValidationException};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller {

    /**
     * @return Factory|View
     */
    public function index () {
        return view('dashboard');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function home () {

        $emp_id = auth()->user()->id;
        $data['user'] = DB::table('users as u')->join('employee_details as ed', 'u.id', '=', 'ed.id')->select('ed.*')->where('u.id', '=', $emp_id)->first();

        return view('home', $data);
    }

    /**
     * @return Factory|View
     */
    public function employeeRegistration () {
        return view('employee-registration-2');
    }

    /**
     * @param Request $request
     */
    public function expenses (Request $request) {
        $data = $request->all();
        Expenses::insert($data);
    }

    /**
     *
     */
    public function expenses_list () {
        ob_start();
        if (auth()->user()->user_type == 'is_admin') {
            $expense = Expenses::where(['delete_status' => NULL, 'status' => NULL])->get();
            foreach ($expense as $expense_list) {
                ?>
                <tr style="margin-bottom:10px;">
                    <td><?php echo $expense_list->date ?></td>
                    <td><?php echo $expense_list->description ?></td>
                    <td><?php echo $expense_list->total ?></td>
                    <td>
                        <a href="javascript:void(0)" onclick="expense_approve(<?= $expense_list->id ?>)"><i
                                class="fa fa-check-circle" title="Approved"></i></a>
                        <a href="javascript:void(0)" title="Reject!" onclick="expense_reject(<?= $expense_list->id ?>)"><i
                                class="fa fa-ban"></i></a>
                    </td>
                    <td class="action-box"><a href="javascript:void(0);"
                                              onclick="edit_view_ajax(<?= $expense_list->id ?>)">EDIT</a><a
                            href="javascript:void(0);" class="down" onclick="delete_expense(<?= $expense_list->id ?>)">DELETE</a>
                    </td>
                </tr>
                <tr class="spacer"></tr>
                <?php
            }
            $data = ob_get_clean();
        }
        else {
            $expense = Expenses::where(['emp_id' => auth()->user()->id, 'delete_status' => NULL, 'status' => NULL])->get();
            foreach ($expense as $expense_list) {
                ?>
                <tr style="margin-bottom:10px;">
                    <td><?php echo $expense_list->date ?></td>
                    <td><?php echo $expense_list->description ?></td>
                    <td><?php echo $expense_list->total ?></td>
                    <td class="action-box"><a href="javascript:void(0);"
                                              onclick="edit_view_ajax(<?= $expense_list->id ?>)">EDIT</a><a
                            href="javascript:void(0);" class="down" onclick="delete_expense(<?= $expense_list->id ?>)">DELETE</a>
                    </td>
                </tr>
                <tr class="spacer"></tr>
                <?php
            }
            $data = ob_get_clean();
        }

        echo json_encode([
            "data" => $data,
        ]);
    }


    /**
     * @param Request $request
     */
    public function expenses_edit (Request $request) {
        $data = $request->all();
        $id = $data['id'];
        Expenses::where('id', $id)->update($data);
    }



    /**
     *
     */
    public function expenses_historical () {
        ob_start();
        if (auth()->user()->user_type == 'is_admin') {
            $expense = Expenses::where(['delete_status' => NULL, 'status' => 1])->orWhere(['status' => 2])->get();
            foreach ($expense as $expense_list) {
                ?>
                <tr style="margin-bottom:10px;">
                    <td><?php echo $expense_list->date ?></td>
                    <td><?php echo $expense_list->description ?></td>
                    <td><?php echo $expense_list->total ?></td>
                    <td class="action-box">
                        <!--<a href="javascript:void(0);" onclick="edit_view_ajax(<?= $expense_list->id ?>)" >EDIT</a>--><a
                            href="javascript:void(0);" class="down" onclick="delete_expense(<?= $expense_list->id ?>)">DELETE</a>
                    </td>
                </tr>
                <tr class="spacer"></tr>
                <?php
            }
            $data = ob_get_clean();
        }
        else {
            $expense = Expenses::where(['emp_id' => auth()->user()->id, 'delete_status' => NULL, 'status' => 1])->orWhere(['status' => 2])->get();
            foreach ($expense as $expense_list) {
                ?>
                <tr style="margin-bottom:10px;">
                    <td><?php echo $expense_list->date ?></td>
                    <td><?php echo $expense_list->description ?></td>
                    <td><?php echo $expense_list->total ?></td>
                    <td class="action-box">
                        <!--<a href="javascript:void(0);" onclick="edit_view_ajax(<?= $expense_list->id ?>)" >EDIT</a>--><a
                            href="javascript:void(0);" class="down" onclick="delete_expense(<?= $expense_list->id ?>)">DELETE</a>
                    </td>
                </tr>
                <tr class="spacer"></tr>
                <?php
            }
            $data = ob_get_clean();
        }

        echo json_encode([
            "data" => $data,
        ]);

    }


    /**
     * @return Factory|View
     */
    public function benefits () {
        return view('benefits');
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function edit_employee (Request $request) {
        /**
         * @todo There are about 40 lines of duplicate code in here that is also in UserController@store ... this should be cleaned up
         */
        $id = $request->input('id');

        //Validate name, email and password fields
        $rules = [
            'firstname' => 'required|max:120',
            'lastname'  => 'required|max:120',
            'email'     => Rule::unique('users')->ignore($id) // require unique email address
        ];

        // morph input fields to match user table
        $input['name'] = $request->input('firstname') . ' ' . $request->input('lastname');
        $input['email'] = $request->input('workemail');

        // if password is entered or it's a new user
        if ($request->input('password') || !$id) {
            //$rules['password'] = 'required|min:6|confirmed';
            $rules['password'] = ['required', 'string', 'min:8', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\X])(?=.*[!$#%]).*$/'];
            $input['password'] = Hash::make($request->input('password'));
        }

        $this->validate($request, $rules);

        // check for admin details
        $input['is_admin'] = $request->input('is_admin', 0);

        //profile pic  code
        $profilepicname = '';
        if ($request->hasFile('profile_pic')) {

            $file = $request->file('profile_pic');
            $profilepicname = rand(11111, 99999) . '.' . $file->getClientOriginalExtension();
            $request->file('profile_pic')->move("public/profile");
        }
        /// end profile pic

        // employee details array start
        $user_array = $request->only([
            'firstname',
            'lastname',
            'dob',
            'personalemail',
            'phone_no',
            'address',
            'workemail',
            'profile_pic',
            'marital_status',
            'no_ofchildren',
            'family_inarea',
            'spcifamilycircumstace',
            'prsnl_belief',
            'known_medical_conditions',
            'allergies',
            'dietary_restrictions',
            'known_health_concerns',
            'aversion_phyactivity',
            'emergency_contact_name',
            'reltn_emergency_contact',
            'emergency_contact_phone',
            'emergency_contact_email'
        ]);
        $user_array['profile_pic'] = $profilepicname;

        if ($id) {
            $user = User::find($id);
            $user->update($input);
            $emp_id = $id;
            /*            $user_detailsupdate = Employee_detail::find($id);
                        $user_detailsupdate->update($user_array);*/
            $msg = 'User successfully updated';
        }
        else {
            $user = User::create($input);

            $msg = 'User successfully Added';
        }

        if (isset($emp_id)) {
            //$user->employee_details()->update($user_array);
            Employee_detail::where('id', '=', $emp_id)->update($user_array);
        }
        else {
            $user->employee_details()->create($user_array);

        }

        //Redirect to the users.index view and display message
        return redirect()->route('home')->with('alert-info', $msg);
    }



}
