<?php/** * Wave Framework <http://www.waveframework.com> * API Documentation Generator * * This script lists all controllers and their methods, as well as all API profiles that can use  * these methods. This is meant for an overview of API commands for documentation purposes or  * otherwise. It shows all API commands based on what their 'www-command' value should be as well  * as displays comments that have been written next to these methods. It is also possible to show  * commands for only specific API profile by defining GET variable 'profile'. * * @package    Tools * @author     Kristo Vaher <kristo@waher.net> * @copyright  Copyright (c) 2012, Kristo Vaher * @license    GNU Lesser General Public License Version 3 * @tutorial   /doc/pages/guide_tools.htm * @since      2.2.1 * @version    3.6.0 */// This initializes tools and authenticationrequire('.'.DIRECTORY_SEPARATOR.'tools_autoload.php');// Log is printed out in plain text formatheader('Content-Type: text/html;charset=utf-8');?><!DOCTYPE html><html lang="en">	<head>		<title>API Info</title>		<meta charset="utf-8">		<meta name="viewport" content="width=device-width"/> 		<link type="text/css" href="style.css" rel="stylesheet" media="all"/>		<link rel="icon" href="../favicon.ico" type="image/x-icon"/>		<link rel="icon" href="../favicon.ico" type="image/vnd.microsoft.icon"/>		<meta content="noindex,nocache,nofollow,noarchive,noimageindex,nosnippet" name="robots"/>		<meta http-equiv="cache-control" content="no-cache"/>		<meta http-equiv="pragma" content="no-cache"/>		<meta http-equiv="expires" content="0"/>	</head>	<body>		<?php				// Pops up an alert about default password		passwordNotification($config['http-authentication-password']);		// If API commands for specific profile only are returned		if(isset($_GET['profile'])){			$apiProfile=$_GET['profile'];		} else {			$apiProfile='*';		}		// Factory class is required by all of custom-classes		$apiProfiles=parse_ini_file('../resources/api.profiles.ini',true,INI_SCANNER_RAW);		$configuration=parse_ini_file('../config.ini',false,INI_SCANNER_RAW);		// Getting list of controller files		$controllerFiles=fileIndex('../controllers/','files');        // Methods and their comments are stored here        $methodComments=array();		// If controllers were found		if($controllerFiles && !empty($controllerFiles)){						// Looping over controllers and finding suitable ones			foreach($controllerFiles as $controllerFile){				// Controller file name will be checked for controller validity				$controllerFileInfo=explode('.',$controllerFile);				// Simple file-name check				$extension=array_pop($controllerFileInfo);				$className=array_pop($controllerFileInfo);								if($extension=='php' && array_pop($controllerFileInfo)=='/controllers/controller'){									// Getting tokens from the file					$tokens=token_get_all(file_get_contents($controllerFile));					// Temporary comment gatherer					$tmpComments=array();					// This is used to check what level token is at					$gatherComments=false;					// This is used to double-check, making sure that the same token is not detected twice in a row					$prevToken=0;					$prevPrevToken=0;										// Looping over tokens					foreach($tokens as $token){						// Ignoring single matches						if(!is_string($token)){													// Testing based on values							if($token[0]==T_COMMENT || $token[0]==T_DOC_COMMENT){								// If token is a comment then it is added to comment array								$tmpComments[]=$token[1];							} elseif($token[0]==T_FUNCTION && $prevPrevToken!=T_PROTECTED && $prevPrevToken!=T_PRIVATE){								// Raising stage if function key is detected								$gatherComments=true;							} elseif($token[0]==T_STRING && $gatherComments){								// If token is function name that is not in illegal methods list, it is added to functions array								if(strpos($token[1],'WWW_')===false){									$methodComments[$className.'-'.strtolower(str_replace('_','-',$token[1]))]=$tmpComments;								}								// Comments are cleared for next method or token								$tmpComments=array();								$gatherComments=false;							} else if($token[0]!=T_WHITESPACE && $token[0]!=T_PUBLIC){								// Since token is not whitespace or is not public, token is cleared								$tmpComments=array();								$gatherComments=false;							}														// Assigning token as previous							$prevPrevToken=$prevToken;							$prevToken=$token[0];													}					}									}							}					}		// Header		echo '<h1>API Documentation</h1>';		echo '<h4 class="highlight">';		foreach($softwareVersions as $software=>$version){			// Adding version numbers			echo '<b>'.$software.'</b> ('.$version.') ';		}		echo '</h4>';			echo '<h2>Reference</h2>';		echo '<div class="border box small">';			echo '<div class="box">';				echo '<span style="font-weight:bold;">{www-command value}</span><br/>';				if($apiProfile=='*'){					echo '<span style="font-style:italic; font-size:11px;">Allowed API profiles: {comma-separated list of API profiles that can use this command, if any}<br/>';				}				echo '<p style="font-style:italic;font-size:11px;padding:5px;margin:0px;">';					echo '{Comments and description, details about input/output keys and response codes, if any. Input and output keys marked with aterisk (*) should be considered as mandatory or always present.}';				echo '</p>';			echo '</div>';			echo '<div class="disabled italic box">';				echo '<span style="font-weight:bold;">{Internal-only API command}</span><br/>';				echo '<p style="font-style:italic;font-size:11px;padding:5px;margin:0px;">';					echo '{Comments and description, details about input/output keys and response codes, if any. Input and output keys marked with aterisk (*) should be considered as mandatory or always present. Note that API command is considered internal only if no API profile has access to it.}';				echo '</p>';			echo '</div>';		echo '</div>';		// Title for API commands		if($apiProfile!='*'){			echo '<h2>API commands for <b>'.$apiProfile.'</b></h2>';			echo '<p>Show commands for <a href="api-info.php">all profiles</a></p>';			echo '<p><i>Note that the documentation below might not define \'www-response-code\' or \'www-message\' as output data keys. These keys might however still be present in the output array and they define system specific verbose message and response codes.</i></p>';			if(isset($apiProfiles[$apiProfile])){				echo '<h3 class="highlight">Authentication</h3>';				if((isset($configuration['api-public-profile']) && $apiProfile==$configuration['api-public-profile']) || (!isset($configuration['api-public-profile']) && $apiProfile=='public')){					?>						<p><i>This is a public API profile. Public API profiles do not need to be authentication through Wave Framework API itself, but might need common session token based validation or some other method, depending if it is defined in the API profile commands or not. Public profiles do not need to define 'www-profile' in the input data either.</i></p>					<?php				} else {					if(!isset($apiProfiles[$apiProfile]['hash-validation']) || $apiProfiles[$apiProfile]['hash-validation']==1){						?>						<div class="box">							<p class="bold lighthighlight">www-create-session</p>							<p class="italic">								This creates a session token based on hash created with secret key and input data and API profile.							</p>							<p class="small">								<b>Input keys</b>								<ul class="small">									<li>[www-profile] your profile name</li>									<li>[www-hash] validation hash</li>								</ul>							</p>							<p class="small">								<b>Output keys</b>								<ul class="small">									<li>[www-message] message if the token was created or not</li>									<li>[www-token] token value</li>									<li>[www-token-timeout] how long the token is valid if left unused</li>									<li>[www-response-code] response code from the system</li>								</ul>							</p>							<p class="small">								<b>Response codes</b>								<ul class="small">									<li>[500] token was created</li>									<li>[1XX] validation problems</li>								</ul>							</p>						</div>						<div class="box">							<p class="bold lighthighlight">www-destroy-session</p>							<p class="italic">								This removes session token from the system and a new token has to be generated to use the API Profile again.							</p>							<p class="small">								<b>Input keys</b>								<ul class="small">									<li>[www-profile] your profile name</li>									<li>[www-hash] validation hash</li>								</ul>							</p>							<p class="small">								<b>Output keys</b>								<ul class="small">									<li>[www-message] message if the session was destroyed or not</li>									<li>[www-response-code] response code from the system</li>								</ul>							</p>							<p class="small">								<b>Response codes</b>								<ul class="small">									<li>[500] token was destroyed</li>									<li>[1XX] validation problems</li>								</ul>							</p>						</div>						<div class="box">							<p class="bold lighthighlight">www-validate-session</p>							<p class="italic">								This simply tests if API session token is still active or not. This command only works if the command is still active. It will return an error otherwise. This is used as a safe command to be sent to API that does not do anything else.							</p>							<p class="small">								<b>Input keys</b>								<ul class="small">									<li>[www-profile] your profile name</li>									<li>[www-hash] validation hash</li>								</ul>							</p>							<p class="small">								<b>Output keys</b>								<ul class="small">									<li>[www-message] message if the session token is active</li>									<li>[www-response-code] response code from the system</li>								</ul>							</p>							<p class="small">								<b>Response codes</b>								<ul class="small">									<li>[500] token is active</li>									<li>[1XX] session not active</li>								</ul>							</p>						</div>						<p><i>Please remember that the rest of the API commands always require 'www-profile' and 'www-hash' to be sent with the request for an API profile, otherwise the command will fail. The 'www-hash' value is a hash that is created from serialized input data and the secret key or session token, there is more information about this in Wave Framework documentation.</i></p>						<?php					} else {						?>						<div class="box">										<p class="bold lighthighlight">www-create-session</p>							<p class="italic">								This creates a session token based on secret key and API profile. No hash validation is used with this API profile, so it is recommended to use HTTPS or IP restrictions for this type of API profile.							</p>							<p class="small">								<b>Input keys</b>								<ul class="small">									<li>[www-profile] your profile name</li>									<li>[www-secret-key] your profile secret key</li>								</ul>							</p>							<p class="small">								<b>Output keys</b>								<ul class="small">									<li>[www-message] message if the token was created or not</li>									<li>[www-token] token value</li>									<li>[www-token-timeout] how long the token is valid if left unused</li>									<li>[www-response-code] response code from the system</li>								</ul>							</p>							<p class="small">								<b>Response codes</b>								<ul class="small">									<li>[500] token was created</li>									<li>[1XX] validation problems</li>								</ul>							</p>						</div>						<div class="box">							<p class="bold lighthighlight">www-destroy-session</p>							<p class="italic">								This removes session token from the system and a new token has to be generated to use the API Profile again.							</p>							<p class="small">								<b>Input keys</b>								<ul class="small">									<li>[www-profile] your profile name</li>									<li>[www-token] your session token</li>								</ul>							</p>							<p class="small">								<b>Output keys</b>								<ul class="small">									<li>[www-message] message if the session was destroyed or not</li>									<li>[www-response-code] response code from the system</li>								</ul>							</p>							<p class="small">								<b>Response codes</b>								<ul class="small">									<li>[500] token was destroyed</li>									<li>[1XX] validation problems</li>								</ul>							</p>						</div>						<div class="box">							<p class="bold lighthighlight">www-validate-session</p>							<p class="italic">								This simply tests if API session token is still active or not. This command only works if the command is still active. It will return an error otherwise. This is used as a safe command to be sent to API that does not do anything else.							</p>							<p class="small">								<b>Input keys</b>								<ul class="small">									<li>[www-profile] your profile name</li>									<li>[www-token] your session token</li>								</ul>							</p>							<p class="small">								<b>Output keys</b>								<ul class="small">									<li>[www-message] message if the session token is active</li>									<li>[www-response-code] response code from the system</li>								</ul>							</p>							<p class="small">								<b>Response codes</b>								<ul class="small">									<li>[500] token is active</li>									<li>[1XX] session not active</li>								</ul>							</p>						</div>						<p><i>Please remember that the rest of the API commands always require 'www-profile' and 'www-token' to be sent with the request for an API profile, otherwise the command will fail.</i></p>						<?php					}				}			}		} else {			echo '<h2>API commands for all profiles</h2>';			echo '<p>Show profile-specific commands and authentication: ';			$links=array();			// Looping over each API profile			foreach($apiProfiles as $key=>$profile){				$links[]='<a href="api-info.php?profile='.$key.'">'.$key.'</a>';			}			echo implode(', ',$links);			echo '</p>';			echo '<p><i>Note that the documentation below might not define \'www-response-code\' or \'www-message\' as output data keys. These keys might however still be present in the output array and they define system specific verbose message and response codes.</i></p>';			echo '<h3 class="highlight">Authentication</h3>';			echo '<p><i>Authentication is API profile specific. To see the commands that are required for authentication please check the API documentation links for each API profile in the above section.</i></p>';		}        // Last controller data is stored here        $lastController='';				// Looping over found methods		foreach($methodComments as $command=>$comments){					// Getting current controller name			$tmp=explode('-',$command);			$controller=array_shift($tmp);			// Displaying controller-header, if new controller			if($controller!=$lastController){				echo '<h3 class="highlight">Controller: '.$controller.'</h3>';			}			$lastController=$controller;						// This array stores allowed profiles for current method			$allowedProfiles=array();			// Looping over each API profile			foreach($apiProfiles as $key=>$profile){				$commands=explode(',',$profile['commands']);				if(isset($profile['commands']) && ((in_array('*',$commands) && !in_array('!'.$command,$commands)) || (!in_array('*',$commands) && in_array($command,$commands)))){					$allowedProfiles[]=$key;				}			}						// Displaying information if all API data is shown or if selected profile is listed as allowed for that command			if($apiProfile=='*' || in_array($apiProfile,$allowedProfiles)){				if(empty($allowedProfiles)){					echo '<div class="disabled italic box">';				} else {					echo '<div class="box">';				}					// printing out main information					echo '<p class="bold lighthighlight">'.$command.'</p>';					if($apiProfile=='*' && !empty($allowedProfiles)){						echo '<span class="small italic">Allowed API profiles:</span> '.implode(',',$allowedProfiles).'<br/>';					}					// Printing out comment information, if exists					if(!empty($comments)){						echo '<p class="italic">';						$input=array();						$output=array();						$response=array();						foreach($comments as $comment){							// Stripping comment tags							$commentLines=explode("\n",$comment);							foreach($commentLines as $key=>$c){								unset($commentLines[$key]);								$c=trim($c);								if(!in_array($c,array('*','//','/**','/*','*/'))){									if(isset($c[0],$c[1]) && $c[0]=='*' && $c[1]==' '){										$comment=substr($c,2);									} elseif(isset($c[0],$c[1],$c[2]) && $c[0]=='/' && $c[1]=='/' && $c[2]==' '){										$comment=substr($c,3);									} else {										$comment=$c;									}									if(preg_match('/@input /i',$comment)){										$input[]=str_replace('@input ','',$comment);									} elseif(preg_match('/@output /i',$comment)){										$output[]=str_replace('@output ','',$comment);									} elseif(preg_match('/@response /i',$comment)){										$response[]=str_replace('@response ','',$comment);									} elseif($comment[0]!='@') {										echo htmlspecialchars($comment).'<br/>';									}								} elseif($key>0 && !empty($commentLines)){									echo '<br/>';								}							}						}						echo '</p>';						if(!empty($input)){							echo '<p class="small">';								echo '<b>Input keys</b>';								echo '<ul class="small">';									foreach($input as $i){										echo '<li>'.htmlspecialchars($i).'</li>';									}								echo '</ul>';							echo '</p>';						}						if(!empty($output)){							echo '<p class="small">';								echo '<b>Output keys</b>';								echo '<ul class="small">';									foreach($output as $o){										echo '<li>'.htmlspecialchars($o).'</li>';									}								echo '</ul>';							echo '</p>';						}						if(!empty($response)){							echo '<p class="small">';								echo '<b>Response codes</b>';								echo '<ul class="small">';									foreach($response as $r){										echo '<li>'.htmlspecialchars($r).'</li>';									}								echo '</ul>';							echo '</p>';						}					}				echo '</div>';			}					}				// Footer		echo '<p class="footer small bold">Generated at '.date('d.m.Y h:i').' GMT '.date('P').' for '.$_SERVER['HTTP_HOST'].'</p>';					?>	</body></html>