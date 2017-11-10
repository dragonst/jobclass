<?php
/**
 * JobClass - Geolocalized Job Board Script
 * Copyright (c) BedigitCom. All Rights Reserved
 *
 * Website: http://www.bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from Codecanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Http\Controllers\Account;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Torann\LaravelMetaTags\Facades\MetaTag;


class EmployeeController extends AccountBaseController
{
  private $perPage = 10;
	public $pagePath = 'employees';


  public function __construct()
  {
    parent::__construct();

    $this->perPage = (is_numeric(config('settings.posts_per_page'))) ? config('settings.posts_per_page') : $this->perPage;

    view()->share('pagePath', $this->pagePath);
  }

  public function index()
  {
    $employees = $this->employees->paginate($this->perPage);

        // Meta Tags
        MetaTag::set('title', t('My Companies List'));
        MetaTag::set('description', t('My Companies List - :app_name', ['app_name' => config('settings.app_name')]));

        return view('account.employee.index')->with('employees', $employees);
  }

  public function create()
	{
		// Meta Tags
		MetaTag::set('title', t('Create a new company'));
		MetaTag::set('description', t('Create a new company - :app_name', ['app_name' => config('settings.app_name')]));

		return view('account.employee.create');
	}

  public function store(EmployeeRequest $request)
{
  // Get Company Info
  $employeeInfo = $request->input('employee');
  if (!isset($employeeInfo['company_id']) || empty($employeeInfo['company_id'])) {
    $employeeInfo += ['company_id' =>2 ];
  }


  // Create the User's Company
  $employee = new Employee($employeeInfo);
  $employee->save();

  flash(t("Employee added successfully."))->success();

  // Save the Company's Logo

  // Redirection
  return redirect(config('app.locale') . '/account/companies');
}

public function show($id)
{}
  public function edit($id)
	{}

    public function update($id, CompanyRequest $request)
  	{}
      public function destroy($id = null)
    	{}
}
