<?php/* WWW - PHP micro-frameworkRobots handlerIt is always good to add robots.txt to any web service or website, even if search engine robots may not always follow these rules. This file can be used to restrict access to specific URL's from being cached until service is ready to go live. If robots.txt does not exist in root folder of the website, then this script here generates it. Otherwise it simply returns the file contents.Author and support: Kristo Vaher - kristo@waher.net*/// Robots.txt file is always returned in plain text formatheader('Content-Type: text/plain;charset=utf-8;'); // Robots file is generated only if it does not exist in rootif(!file_exists(__ROOT__.DIRECTORY_SEPARATOR.'robots.txt')){	// Currently known location of the file	$resource=str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$_SERVER['DOCUMENT_ROOT'].$_SERVER['REDIRECT_URL']);	// Getting information about current resource	$fileInfo=pathinfo($resource);	// Assigning file information	$file=$fileInfo['basename'];	// If filename includes & symbol, then system assumes it should be dynamically generated	$parameters=array_unique(explode('&',$file));	// Looking for cache	$cacheFilename=md5('robots.txt'.$_SERVER['REDIRECT_URL']).'.tmp';	$cacheDirectory=__ROOT__.DIRECTORY_SEPARATOR.'filesystem'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.substr($cacheFilename,0,2).DIRECTORY_SEPARATOR;	// If cache file exists then cache modified is considered that time	if(file_exists($cacheDirectory.$cacheFilename)){		$lastModified=filemtime($cacheDirectory.$cacheFilename);	} else {		// Otherwise it is server request time		$lastModified=$_SERVER['REQUEST_TIME'];	}	// Default cache timeout of one month, unless timeout is set	if(!isset($config['resource-cache-timeout'])){		$config['resource-cache-timeout']=31536000; // A year	}	// If resource cannot be found from cache, it is generated	if(in_array('nocache',$parameters) || ($lastModified==$_SERVER['REQUEST_TIME'] || $lastModified<($_SERVER['REQUEST_TIME']-$config['resource-cache-timeout']))){			// Connecting to database, if configuration is set		if(isset($config['database-name']) && isset($config['database-type']) && isset($config['database-host']) && isset($config['database-username']) && isset($config['database-password'])){			// Including the required class and creating the object			require(__ROOT__.DIRECTORY_SEPARATOR.'engine'.DIRECTORY_SEPARATOR.'class.www-database.php');			$databaseConnection=new WWW_Database();					// Assigning database variables and creating the connection			$databaseConnection->type=$config['database-type'];			$databaseConnection->host=$config['database-host'];			$databaseConnection->username=$config['database-username'];			$databaseConnection->password=$config['database-password'];			$databaseConnection->database=$config['database-name'];			$databaseConnection->connect();						// Passing the database to State object			$state->databaseConnection=$databaseConnection;						// If Logger is defined, then database connection is passed to Logger as well			if(isset($logger)){				$logger->databaseConnection=$databaseConnection;			}				}				// Robots string is stored here		$robots='';		$robots.='User-agent: *'."\n";		$robots.='Disallow: '."\n";		$robots.='Sitemap: '.((isset($config['https-limiter']) && $config['https-limiter']==true)?'https://':'http://').$_SERVER['HTTP_HOST'].$state->data['web-root'].'sitemap.xml';			// Resource cache is cached in subdirectories, if directory does not exist then it is created		if(!is_dir($cacheDirectory)){			if(!mkdir($cacheDirectory,0777)){				trigger_error('Cannot create cache folder',E_USER_ERROR);			}		}				// Data is written to cache file		if(!file_put_contents($cacheDirectory.$cacheFilename,$robots)){			trigger_error('Cannot create resource cache',E_USER_ERROR);		}		} else {		// Notifying logger that cache was used		if(isset($logger)){			$logger->cacheUsed=true;		}			}	// If cache is used, then proper headers will be sent	if(!in_array('nocache',$parameters)){				// Client is told to cache these results for set duration		header('Cache-Control: public,max-age='.$config['resource-cache-timeout'].',must-revalidate');		header('Expires: '.gmdate('D, d M Y H:i:s',($lastModified+$config['resource-cache-timeout'])).' GMT');		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');	} else {		// Client is told to cache these results for set duration		header('Cache-Control: public,max-age=0,must-revalidate');		header('Expires: '.gmdate('D, d M Y H:i:s',$_SERVER['REQUEST_TIME']).' GMT');		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');			}	// Pragma header removed should the server happen to set it automatically	header_remove('Pragma');	// Content length is defined that can speed up website requests, letting client to determine file size	header('Content-Length: '.filesize($cacheDirectory.$cacheFilename));  	// Returning the file to client	readfile($cacheDirectory.$cacheFilename);	// File is deleted if cache was requested to be off	if(in_array('nocache',$parameters)){		unlink($cacheDirectory.$cacheFilename);	}	} else {	// Content length is defined that can speed up website requests, letting client to determine file size	header('Content-Length: '.filesize(__ROOT__.DIRECTORY_SEPARATOR.'robots.txt')); 	// Since robots.txt did exist in root, it is simply returned	readfile(__ROOT__.DIRECTORY_SEPARATOR.'robots.txt');}?>