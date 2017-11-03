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

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;

class PostRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$cat = null;
		
		$rules = [
			'category'     => 'required|not_in:0',
			'post_type'    => 'required|not_in:0',
			'title'        => 'required|mb_between:2,200|whitelist_word_title',
			'description'  => 'required|mb_between:5,3000|whitelist_word',
			'salary_type'  => 'required|not_in:0',
			'contact_name' => 'required|mb_between:2,200',
			'email'        => 'max:100|whitelist_email|whitelist_domain',
			'phone'        => 'max:20',
			'city'         => 'required|not_in:0',
		];
		
		// CREATE
		if (in_array($this->method(), ['POST', 'CREATE'])) {
			$rules['parent'] = 'required|not_in:0';
			
			// Recaptcha
			if (config('settings.activation_recaptcha')) {
				$rules['g-recaptcha-response'] = 'required';
			}
		}
		
		// UPDATE
		// if (in_array($this->method(), ['PUT', 'PATCH', 'UPDATE'])) {}
		
		// COMMON
		
		// Location
		if (in_array(config('country.admin_type'), ['1', '2']) && config('country.admin_field_active') == 1) {
			$rules['admin_code'] = 'required|not_in:0';
		}
		
		// Email
		if ($this->filled('email')) {
			$rules['email'] = 'email|' . $rules['email'];
		}
		if (isEnabledField('email')) {
			if (isEnabledField('phone') && isEnabledField('email')) {
				if (Auth::check()) {
					$rules['email'] = 'required_without:phone|' . $rules['email'];
				} else {
					// Email address is required for Guests
					$rules['email'] = 'required|' . $rules['email'];
				}
			} else {
				$rules['email'] = 'required|' . $rules['email'];
			}
		}
		
		// Phone
		if (config('settings.phone_verification') == 1) {
			if ($this->filled('phone')) {
				$countryCode = $this->input('country', config('country.code'));
				if ($countryCode == 'UK') {
					$countryCode = 'GB';
				}
				$rules['phone'] = 'phone:' . $countryCode . ',mobile|' . $rules['phone'];
			}
		}
		if (isEnabledField('phone')) {
			if (isEnabledField('phone') && isEnabledField('email')) {
				$rules['phone'] = 'required_without:email|' . $rules['phone'];
			} else {
				$rules['phone'] = 'required|' . $rules['phone'];
			}
		}
		
		// Company
		if (!$this->filled('company_id') || empty($this->input('company_id'))) {
			$rules['company.name'] = 'required|mb_between:2,200|whitelist_word_title';
			$rules['company.description'] = 'required|mb_between:5,1000|whitelist_word';
			
			// Check 'logo' is required
			if ($this->file('logo')) {
				$rules['logo'] = 'required|image|mimes:' . getUploadFileTypes('image') . '|max:' . (int)config('settings.upload_max_file_size', 1000);
			}
		} else {
			$rules['company_id'] = 'required|not_in:0';
		}
		
		// Application URL
		if ($this->filled('application_url')) {
			$rules['application_url'] = 'url';
		}
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages()
	{
		$messages = [];
		
		return $messages;
	}
}
