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

namespace App\Http\Middleware;

ini_set('max_execution_time', 300);

use App\Models\TimeZone;
use Closure;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InstallationChecker
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->segment(1) == 'install') {
            // Check if installation is processing
            $InstallInProgress = false;
            if (
                !empty($request->session()->get('database_imported')) ||
                !empty($request->session()->get('cron_jobs')) ||
                !empty($request->session()->get('install_finish'))
            )
            {
                $InstallInProgress = true;
            }
            if ($this->alreadyInstalled($request) && $this->properlyInstalled() && !$InstallInProgress) {
                return redirect('/');
            }
        } else {
			// Check if an update is available
			if (File::exists(base_path('.env')) && $this->checkUpdates()) {
				return headerLocation(getRawBaseUrl() . '/upgrade');
			}
			
			// Check if the website is installed
            if (!$this->alreadyInstalled($request) || !$this->properlyInstalled()) {
                return redirect(getRawBaseUrl() . '/install');
            }
        }

        return $next($request);
    }

    /**
     * If application is already installed.
     *
     * @param $request
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    public function alreadyInstalled($request)
    {
        // Check if installation has just finished
        $installHasJustFinished = false;
        if (!empty($request->session()->get('install_finish'))) {
            $installHasJustFinished = true;
        }

        if ($installHasJustFinished === true) {
            // Write file
			File::put(storage_path('installed'), '');

            $request->session()->forget('install_finish');
            $request->session()->flush();

            // Redirect to the homepage after installation
            return redirect('/');
        }

        return File::exists(storage_path('installed'));
    }

    /**
     * @return bool
     */
    public function properlyInstalled()
    {
        // Check Installation Setup
        $properly = true;
        try {
            // Check if .env file exists
            if (!$this->envFileExists()) {
                $properly = false;
            }

            // Check if all database tables exists
            $namespace = 'App\\Models\\';
            $modelsPath = app_path('Models');
            $modelFiles = array_filter(File::glob($modelsPath . '/' . '*.php'), 'is_file');

            if (count($modelFiles) > 0) {
                foreach ($modelFiles as $filePath) {
                    $filename = last(explode('/', $filePath));
                    $modelname = head(explode('.', $filename));

                    if (!str_contains(strtolower($filename), '.php') or str_contains(strtolower($modelname), 'base')) {
                        continue;
                    }

                    eval('$model = new ' . $namespace . $modelname . '();');
                    if (!Schema::hasTable($model->getTable())) {
                        $properly = false;
                    }
                }
            }

            // Check Settings table
            if (Setting::count() <= 0) {
                $properly = false;
            }
            // Check TimeZone table
            if (TimeZone::count() <= 0) {
                $properly = false;
            }
        } catch (\PDOException $e) {
            $properly = false;
        } catch (\Exception $e) {
            $properly = false;
        }

        return $properly;
    }

    /**
     * Check if /.env file exists
     *
     * @return bool
     */
    public function envFileExists()
    {
        return File::exists(base_path('.env'));
    }
	
	/**
	 * Check if an update is available
	 *
	 * @return bool
	 */
	private function checkUpdates()
	{
		$updateIsAvailable = false;
		
		// Get eventual new version value & the current (installed) version value
		$scriptVersionInt = strToInt(config('app.version'));
		$installedVersionInt = strToInt(getInstalledVersion());
		
		// Check the update
		if ($scriptVersionInt > $installedVersionInt) {
			$updateIsAvailable = true;
		}
		
		return $updateIsAvailable;
	}
}
