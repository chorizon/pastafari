<?php

use PhangoApp\PhaRouter\Routes;
use PhangoApp\PhaRouter\Controller;
use PhangoApp\PhaUtils\Utils;
use Symfony\Component\Process\Process;

global $arr_api;

$arr_api=array('ERROR' => 1, 'MESSAGE' => 'Who are you?', 'CODE_ERROR' => 0);

class indexController extends Controller {

	public function home()
	{
	
        global $arr_api;

		//Check if the server have permissions to make tasks
		//Of course, when we installed pastafari with protozoo, we created configs, a key and only can access via https to this server from only an ip
		//The port can be defined in protozoo script
		//API:
		//ERROR=0
		//MESSAGE=""
		//CODE_ERROR=0
        
        $settings=array();
        
        Utils::load_config('config', __DIR__.'/../settings/');
        Utils::load_config('configerrors', __DIR__.'/../settings/');
	
        settype($settings['path_scripts'], 'string');
        
        if($settings['path_scripts']=='')
        {
        
           $settings['path_scripts'] =Routes::$base_path.'/modules/pastafari/scripts';
        
        }
	
		if(isset($_GET['secret_key']))
		{
            
			if(password_verify(SECRET_KEY.'+'.$_GET['secret_key'], SECRET_KEY_HASHED_WITH_PASS)==true)
			{
			
				//I can execute the script. 
				
				//Execute script
				
				if(isset($_GET['category']))
				{
					$_GET['category']=basename(Utils::slugify($_GET['category']));
				
					if(isset($_GET['module']))
					{
					
						$_GET['module']=basename(Utils::slugify($_GET['module']));
				
						if(isset($_GET['script']))
						{
                            
							$_GET['script']=basename(Utils::slugify($_GET['script']));
						
							//Load process
							
							$arr_save=array();
							
							$command=$settings['path_scripts'].'/'.$_GET['category'].'/'.$_GET['module'].'/'.$_GET['script'];
							
							$arr_param=$_GET;
							
							unset($arr_param['category']);
							unset($arr_param['module']);
							unset($arr_param['script']);
							unset($arr_param['secret_key']);
							
							while(list($key, $value)=each($arr_param))
							{
								$arr_save[]='--'.$key.'='.$value;
							}
							
							$parameters=implode(' ', $arr_save);
							
							$process = new Process('sudo php '.Routes::$base_path.'/console.php -m pastafari -c load --command=\''.$command.'\' --arguments=\''.$parameters.'\'');
                            
                            $process->run(function ($type, $buffer) {
                            
                                global $arr_api;
                            
                                if (Process::ERR === $type) 
                                {
                                    
                                    $arr_api['ERROR']=1;
                                    $arr_api['CODE_ERROR']=PASTA_ERROR_SCRIPT;
                                    $arr_api['MESSAGE']=$buffer;
                                } 
                                else 
                                {
                                    #settype($buffer, 'integer');
                                    $arr_buffer=json_decode($buffer, true);
                                    
                                    settype($arr_buffer['ERROR'], 'integer');
                                    
                                    settype($arr_buffer['TOKEN'], 'string');
                                    
                                    $arr_api['TOKEN']=$arr_buffer['TOKEN'];
                                    
                                    if($arr_buffer['ERROR']>0)
                                    {
                                        
                                        $arr_api['ERROR']=1;
                                        $arr_api['CODE_ERROR']=PASTA_ERROR_FILE;
                                        $arr_api['MESSAGE']=$arr_buffer['MESSAGE'];
                                        
                                    }
                                    elseif($arr_api['TOKEN']!='')
                                    {
                                    
                                        //Die the script
                                        $arr_api['ERROR']=0;
                                        $arr_api['MESSAGE']='Script running...';
                                        
                                    
                                    }
                                }
                                
                                header('Content-type: text/plain');
        
                                echo json_encode($arr_api);
                                
                                die;
                            });
                            
                            header('Content-type: text/plain');
        
                            echo json_encode($arr_api);
                            
                            die;
							
						}
				
					}
				
				}
				
				$arr_api['MESSAGE']='No tasks specified...';
				
			}
		
		}
	
		header('Content-type: text/plain');
		
		echo json_encode($arr_api);
		
		die;

	}
	
	public function check_process_progress()
	{
	
        $settings=array();
	
        if(!isset($settings['logs']))
        {
        
            $settings['logs']='./logs';
        
        }
	
        Utils::load_config('config', __DIR__.'/../settings/');
        Utils::load_config('configerrors', __DIR__.'/../settings/');
	
        $arr_api=array('ERROR' => 1, 'MESSAGE' => 'Who are you?');
	
		if(isset($_GET['secret_key']))
        {
            
            if(password_verify(SECRET_KEY.'+'.$_GET['secret_key'], SECRET_KEY_HASHED_WITH_PASS)==true)
            {
            
                //Check key of log
                if(isset($_GET['token']))
                {
                    $token=str_replace('/', '', $_GET['token']);
                    
                    $log=$settings['logs'].'/log_'.$token.'.log';
                    
                    if(is_file($log))
                    {
                    
                        //Obtain last line
                        
                        $arr_file=file($log);
                        
                        $last_line=$arr_file[count($arr_file)-1];
                        
                        $arr_api=json_decode($last_line, true);
                    
                    }
                    else
                    {
                    
                        $arr_api=array('ERROR' => 1, 'MESSAGE' => 'Don\'t exists tasks');
                    
                    }
                    
                
                }
            
            }
            
        }
        
        header('Content-type: text/plain');
        
        echo json_encode($arr_api);
        
        die;
	
	}

}


?>