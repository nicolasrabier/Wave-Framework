<?php/** * Wave Framework <http://www.waveframework.com> * Robots Handler * * Robots Handler is used to return robots.txt files, if a request is made to such a file. This  * handler either returns the existing /robots.txt file, or generates a new one that allows  * all-access to robots. Robot directives for search engines and other crawlers are actually  * stored on files and pages themselves, so it is not needed to specifically allow or deny  * anything through robots.txt file. Robots Handler also pinpoints to sitemap.xml file. * * @package    Index Gateway * @author     Kristo Vaher <kristo@waher.net> * @copyright  Copyright (c) 2012, Kristo Vaher * @license    GNU Lesser General Public License Version 3 * @tutorial   /doc/pages/handler_robots.htm * @since      1.5.0 * @version    3.4.1 */// INITIALIZATION	// Stopping all requests that did not come from Index Gateway	if(!isset($resourceAddress)){		header('HTTP/1.1 403 Forbidden');		die();	}	// Robots.txt file is always returned in plain text format	header('Content-Type: text/plain;charset=utf-8;');		// This flag stores whether cache was used	$cacheUsed=false;	// Default cache timeout of one month, unless timeout is set	if(!isset($config['robots-cache-timeout'])){		$config['robots-cache-timeout']=14400; // Four hours	}// GENERATING ROBOTS FILE	// Robots file is generated only if it does not exist in root	if(!file_exists(__ROOT__.'robots.txt')){			// ASSIGNING PARAMETERS FROM REQUEST			// If filename includes & symbol, then system assumes it should be dynamically generated			$parameters=array_unique(explode('&',$resourceFile));			// Looking for cache			$cacheFilename=md5('robots.txt'.$config['version'].$resourceRequest).'.tmp';			$cacheDirectory=__ROOT__.'filesystem'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.substr($cacheFilename,0,2).DIRECTORY_SEPARATOR;			// If cache file exists then cache modified is considered that time			if(file_exists($cacheDirectory.$cacheFilename)){				$lastModified=filemtime($cacheDirectory.$cacheFilename);			} else {				// Otherwise it is server request time				$lastModified=$_SERVER['REQUEST_TIME'];			}					// GENERATING NEW ROBOTS FILE OR LOADING FROM CACHE			// If robots cannot be found from cache, it is generated			if(in_array('nocache',$parameters) || ($lastModified==$_SERVER['REQUEST_TIME'] || $lastModified<($_SERVER['REQUEST_TIME']-$config['robots-cache-timeout']))){							// STATE AND DATABASE									// State stores a lot of settings that are taken into account during Sitemap generation					require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-state.php');					$state=new WWW_State($config);					// Connecting to database, if configuration is set					// Uncomment this if you actually need to use database connection for robots.txt file					// if(isset($config['database-name']) && $config['database-name']!='' && isset($config['database-type']) && isset($config['database-host']) && isset($config['database-username']) && isset($config['database-password'])){						// require(__ROOT__.'engine'.DIRECTORY_SEPARATOR.'class.www-database.php');						// $databaseConnection=new WWW_Database($config['database-type'],$config['database-host'],$config['database-name'],$config['database-username'],$config['database-password'],((isset($config['database-errors']))?$config['database-errors']:false),((isset($config['database-persistent']))?$config['database-persistent']:false));					// }									// GENERATING ROBOTS STRING 									// Robots string is stored here					$robots='';					$robots.='User-agent: *'."\n";					$robots.='Disallow: '."\n";					$robots.='Sitemap: '.((isset($config['https-limiter']) && $config['https-limiter']==true)?'https://':'http://').$_SERVER['HTTP_HOST'].$state->data['web-root'].'sitemap.xml';									// WRITING TO CACHE								// Resource cache is cached in subdirectories, if directory does not exist then it is created					if(!is_dir($cacheDirectory)){						if(!mkdir($cacheDirectory,0755)){							trigger_error('Cannot create cache folder',E_USER_ERROR);						}					}					// Data is written to cache file					if(!file_put_contents($cacheDirectory.$cacheFilename,$robots)){						trigger_error('Cannot create resource cache',E_USER_ERROR);					}						} else {				// Setting the flag for logger				$cacheUsed=true;			}					// HEADERS					// If cache is used, then proper headers will be sent			if(in_array('nocache',$parameters)){				// user agent is told to cache these results for set duration				header('Cache-Control: public,max-age=0');				header('Expires: '.gmdate('D, d M Y H:i:s',$_SERVER['REQUEST_TIME']).' GMT');				header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');			} else {				// user agent is told to cache these results for set duration				header('Cache-Control: public,max-age='.$config['robots-cache-timeout']);				header('Expires: '.gmdate('D, d M Y H:i:s',($_SERVER['REQUEST_TIME']+$config['robots-cache-timeout'])).' GMT');				header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');			}						// Content length of the file			$contentLength=filesize($cacheDirectory.$cacheFilename);			// Content length is defined that can speed up website requests, letting user agent to determine file size			header('Content-Length: '.$contentLength);  					// OUTPUT			// Returning the file to user agent			readfile($cacheDirectory.$cacheFilename);			// File is deleted if cache was requested to be off			if(in_array('nocache',$parameters)){				unlink($cacheDirectory.$cacheFilename);			}			} else {				// RETURNING EXISTING ROBOTS FILE					// This is technically considered as using cache			$cacheUsed=true;						// Cache headers			header('Cache-Control: public,max-age='.$config['robots-cache-timeout']);			header('Expires: '.gmdate('D, d M Y H:i:s',($_SERVER['REQUEST_TIME']+$config['robots-cache-timeout'])).' GMT');			// Last modified header			header('Last-Modified: '.gmdate('D, d M Y H:i:s',filemtime(__ROOT__.'robots.txt')).' GMT');			// Content length of the file			$contentLength=filesize(__ROOT__.'robots.txt');			// Content length is defined that can speed up website requests, letting user agent to determine file size			header('Content-Length: '.$contentLength);			// Since robots.txt did exist in root, it is simply returned			readfile(__ROOT__.'robots.txt');	}	// WRITING TO LOG	// If Logger is defined then request is logged and can be used for performance review later	if(isset($logger)){		// Assigning custom log data to logger		$logger->setCustomLogData(array('category'=>'robots','cache-used'=>$cacheUsed,'content-length-used'=>$contentLength,'database-query-count'=>((isset($databaseConnection))?$databaseConnection->queryCounter:0)));		// Writing log entry		$logger->writeLog();	}?>