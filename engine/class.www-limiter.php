<?php

/*
WWW Framework
Request limiter class

This is an optional class that is used to limit requests based on user agent by Index gateway. 
WWW_Limiter can be used to block IP's if they make too many requests per minute, block requests 
if server load is detected as too high, block the request if it comes from blacklist provided 
by the system, ask for HTTP authentication or force the user agent to use HTTPS. Note that some 
of this functionality can be achieved by Apache configuration and modules, but it is provided 
here for cases where the project developer might not have control over server configuration.

* Requires /filesystem/limiter/ folder to be writeable by server
* Request rate limiter
* Server load limiter (does not work on Windows)
* Whitelisted IP limiter
* Blacklisted IP limiter
* HTTP authentication limiter
* HTTPS-only limiter

Author and support: Kristo Vaher - kristo@waher.net
*/

class WWW_Limiter {

	// Directory of log files
	private $logDir;
	
	// Logger object
	public $logger=false;
	
	// Default values are assigned when Limiter is constructed
	// It is good to initiate Limiter as early as possible
	// * logDir - location of directory to store log files at
	public function __construct($logDir){
		
		// Checking if log directory is valid
		if(is_dir($logDir)){
			// Log directory is assigned
			$this->logDir=$logDir;
		} else {
			// Assigned folder is not detected as being a folder
			throw new Exception('Assigned limiter folder does not exist');
		}
		
	}
	
	// Blocks the user agent if too many requests have been called per minute
	// * limit - Amount of requests that cannot be exceeded per minute
	// * duration - Duration of how long the IP will be blocked if limit is exceeded
	// Returns true if not limited, throws 403 page if limit exceeded, throws error if log file cannot be created
	public function limitRequestCount($limit=400,$duration=3600){
	
		// Limiter is only used if limit is set higher than 0 and request does not originate from the same server
		if($limit!=0 && $_SERVER['REMOTE_ADDR']!=$_SERVER['SERVER_ADDR']){
		
			// Log filename is hashed user agents IP
			$logFilename=md5($_SERVER['REMOTE_ADDR']);
			// Subfolder name is derived from log filename
			$cacheSubfolder=substr($logFilename,0,2);
			
			// If log directory does not exist, then it is created
			$this->logDir.=$cacheSubfolder.DIRECTORY_SEPARATOR;
			if(!is_dir($this->logDir)){
				// Error is returned if creating the limiter folder with proper permissions does not work
				if(!mkdir($this->logDir,0777)){
					throw new Exception('Cannot create limiter folder');
				}
			}
			
			// If file exists, then the amount of requests are checked, if file does not exist then it is created
			if(file_exists($this->logDir.$logFilename.'.tmp')){
			
				// Loading current contents of the file
				$data=file_get_contents($this->logDir.$logFilename.'.tmp');
				
				// If current file does not say that the IP is blocked, the request frequency is checked
				if($data!='BLOCKED'){
				
					// Limit is checked by counting the most recent requests stored in the file					
					$data=explode("\n",$data);
					if(count($data)>=$limit){
					
						// Limited amount of rows is taken from the file before data is flipped, minimizing the timestamps for check
						$data=array_slice($data,-$limit); 
						$checkData=array_flip($data);
						
						// Limit has been reached by all of the requests happening in the same minute
						if(count($checkData)==1){
							// Request is logged and can be used for performance review later
							if($this->logger){
								$this->logger->setCustomLogData(array('response-code'=>403,'category'=>'limiter','reason'=>'Too many requests'));
								$this->logger->writeLog();
							}
							// Block file is created and 403 page thrown to the user agent
							file_put_contents($this->logDir.$logFilename.'.tmp','BLOCKED');
							// Returning proper header
							header('HTTP/1.1 403 Forbidden');
							die();
						}
						
					}
					
					// When limit was not exceeded, file is stored again with new data
					$limiterData=implode("\n",$data);
					file_put_contents($this->logDir.$logFilename.'.tmp',$limiterData."\n".date('Y-m-d H:i',$_SERVER['REQUEST_TIME']));
					
				} else {
				
					// If the file that has blocked the requests is older than the limit duration, then block is deleted, otherwise 403 page is shown
					if(time()-filemtime($this->logDir.$logFilename.'.tmp')>=$duration){
						// Block file is removed
						unlink($this->logDir.$logFilename.'.tmp');
					} else {
						// Request is logged and can be used for performance review later
						if($this->logger){
							$this->logger->setCustomLogData(array('response-code'=>403,'category'=>'limiter','reason'=>'Too many requests'));
							$this->logger->writeLog();
						}
						// Returning 403 header
						header('HTTP/1.1 403 Forbidden');
						die();
					}
					
				}
				
			} else {
			
				// Current date, hour and minute are stored in the file
				file_put_contents($this->logDir.$logFilename.'.tmp',date('Y-m-d H:i',$_SERVER['REQUEST_TIME']));
				
			}
		}
		
		// Request limiter processed
		return true;
		
	}
	
	// Blocks user agents request if server load is too high
	// * limit - Server load that, if exceeded, causes the user agents request to be blocked
	// Returns true if server load below limit, throws 503 page if load above limit
	public function limitServerLoad($limit=80){
	
		// System load is checked only if limit is not set
		if($limit!=0){
			// This function does not return on Windows servers
			if(function_exists('sys_getloadavg')){
				// Returns system load in the last 1, 5 and 15 minutes.
				$load=sys_getloadavg();
				// 503 page is returned if load is above limit
				if($load[0]>$limit){
					// Request is logged and can be used for performance review later
					if($this->logger){
						$this->logger->setCustomLogData(array('response-code'=>503,'category'=>'limiter','reason'=>'Server load exceeded, current load is '.$load[0].', limit is '.$limit));
						$this->logger->writeLog();
					}
					// Returning 503 header
					header('HTTP/1.1 503 Service Unavailable');
					die();
				}
			} else {
				return true;
			}
		}
		
		// Server load limiter processed
		return true;
	
	}
	
	// Checks if current IP is listed in an array of whitelisted IP's
	// * whiteList - comma-separated list of whitelisted IP addresses
	// Returns true, if whitelisted, throws 403 error if not whitelisted
	public function limitWhitelisted($whiteList=''){
	
		// This value should be a comma-separated string of blacklisted IP's
		if($whiteList!=''){
			// Exploding string of IP's into an array
			$whiteList=explode(',',$whiteList);
			// Checking if the user agent IP is set in blacklist array
			if(empty($whiteList) || !in_array($_SERVER['REMOTE_ADDR'],$whiteList)){
				// Request is logged and can be used for performance review later
				if($this->logger){
					$this->logger->setCustomLogData(array('response-code'=>403,'category'=>'limiter','reason'=>'Not whitelisted'));
					$this->logger->writeLog();
				}
				// Returning 403 data
				header('HTTP/1.1 403 Forbidden');
				die();
			}
		}
		
		// Blacklist processed
		return true;
		
	}
	
	// Checks if current IP is listed in an array of blacklisted IP's
	// * blackList - comma-separated list of blacklisted IP addresses
	// Returns true, if not blacklisted, throws 403 error if blacklisted
	public function limitBlacklisted($blackList=''){
	
		// This value should be a comma-separated string of blacklisted IP's
		if($blackList!=''){
			// Exploding string of IP's into an array
			$blackList=explode(',',$blackList);
			// Checking if the user agent IP is set in blacklist array
			if(!empty($blackList) && in_array($_SERVER['REMOTE_ADDR'],$blackList)){
				// Request is logged and can be used for performance review later
				if($this->logger){
					$this->logger->setCustomLogData(array('response-code'=>403,'category'=>'limiter','reason'=>'Blacklisted'));
					$this->logger->writeLog();
				}
				// Returning 403 data
				header('HTTP/1.1 403 Forbidden');
				die();
			}
		}
		
		// Blacklist processed
		return true;
		
	}
	
	// Checks if user agent is authenticated and has provided HTTP credentials
	// * username - correct username for the request
	// * password - correct password for the request
	// Returns true if authorized, throws 401 error if incorrect credentials
	public function limitUnauthorized($username,$password){
	
		// If provided username and password are not correct, then 401 page is displayed to the user agent
		if(!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER']!=$username || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_PW']!=$password){
			// Request is logged and can be used for performance review later
			if($this->logger){
				$this->logger->setCustomLogData(array('response-code'=>401,'category'=>'limiter','reason'=>'Authorization required'));
				$this->logger->writeLog();
			}
			// Returning 401 headers
			header('WWW-Authenticate: Basic realm="'.$_SERVER['HTTP_HOST'].'"');
			header('HTTP/1.1 401 Unauthorized');
			die();
		}
		
		// HTTP authorization processed
		return true;
		
	}
	
	// Redirects the user agent to HTTPS or throws an error if HTTPS is not used
	// * autoRedirect - If this is set to true, then system redirects user agent to HTTPS
	// Returns true if on HTTPS, redirects the user agent or throws 401 page if not
	public function limitNonSecureRequests($autoRedirect=true){
	
		// HTTPS is detected from $_SERVER variables
		if(!isset($_SERVER['HTTPS']) || ($_SERVER['HTTPS']!=1 && $_SERVER['HTTPS']!='on')){
			// If auto redirect is on, user agent is forwarded by replacing the http:// protocol with https://
			if($autoRedirect){
				// Redirecting to HTTPS address
				header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			} else {
				// Request is logged and can be used for performance review later
				if($this->logger){
					$this->logger->setCustomLogData(array('response-code'=>401,'category'=>'limiter','reason'=>'HTTPS required'));
					$this->logger->writeLog();
				}
				// Returning 401 header
				header('HTTP/1.1 401 Unauthorized');
			}
			// Script is halted
			die();
		}
		
		// HTTPS check processed
		return true;
		
	}

}
	
?>