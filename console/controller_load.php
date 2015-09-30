<?php

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Process\Process;
use PhangoApp\PhaUtils\Utils;
use Ramsey\Uuid\Uuid;

$logger;

function loadConsole()
{

	//ERROR=0 PROGRESS=100 MESSAGE=''
	
	
	$options=get_opts_console('', $arr_opts=array('command:', 'arguments:'));
	
	if(!isset($options['command']))
	{
	
		exit(1);
	
	}
	
	$arguments='';
	
	if(isset($options['arguments']))
    {
    
        $arguments=$options['arguments'];
    
    }
	
	//Make forking
	//Unique token for this element
	$uuid1=Uuid::uuid1();
    $token=$uuid1->toString();
	
	$settings=array();
        
    Utils::load_config('config', __DIR__.'/../settings/');
    Utils::load_config('configerrors', __DIR__.'/../settings/');

    if(!isset($settings['logs']))
    {
    
        $settings['logs']='./logs';
    
    }
    
    if(!is_dir($settings['logs']))
    {
    
        if(!mkdir($settings['logs'], 0755, true))
        {
        
            echo json_encode(array('ERROR' => 1, 'MESSAGE' => 'Cannot create logs directory in '.$settings['logs']));
        
            exit(1);
        
        }
    
    }
    
    //Check if exists command
    
    if(!file_exists($options['command']))
    {
    
        echo json_encode(array('ERROR' => 1, 'MESSAGE' => 'Error: command '.trim($options['command']).' not exists', 'CODE_ERROR' => PASTA_ERROR_CHILDREN_SCRIPT));
            
        exit(1);
    
    }
    
	$pid = pcntl_fork();
	
    if ($pid == -1) 
    {
        echo json_encode(array('ERROR' => 1, 'MESSAGE' => 'CANNOT FORK, check php configuration', 'CODE_ERROR' => PASTA_ERROR_FORK));
        exit(0);
    } 
    elseif ($pid) 
    {
        
        echo json_encode(array('TOKEN' => $token));
        exit(0);
        
    } 
    else 
    {
    
        global $logger;
    
        $sid = posix_setsid();
        
        $my_pid=posix_getpid();
        
        //Now i can execute the script
        
        $output = "%message%\n";
    
        //$date_format='Y-m-d H:i:s';
        
        $formatter = new LineFormatter($output);

        $logger = new Logger($options['command']);
        
        $stream_handler=new StreamHandler($settings['logs'].'/log_'.$token.'.log', Logger::INFO);
        
        $stream_handler->setFormatter($formatter);
        
        $logger->pushHandler($stream_handler);
        
        $logger->addInfo(json_encode(array('MESSAGE' => "Executing script ${options['command']}...", 'ERROR' => 0, 'CODE_ERROR' => 0) ) );
        
        //Check if exists script to execute
        //Check 
        
        $process = new Process($options['command'].' '.$arguments);
        
        $process->run(function ($type, $buffer) {
            
            global $logger;
        
            if (Process::ERR === $type) 
            {
                #$arr_error=$buffer;
                
                $logger->addInfo(json_encode(array('ERROR' => 1, 'CODE_ERROR' => PASTA_ERROR_CHILDREN_SCRIPT, 'MESSAGE' => $buffer)));
                
            } 
            else 
            {
                
                $logger->addInfo($buffer);
                
            }
            
        });
        
        //Check if error in command
        /*
        if (!$process->isSuccessful()) 
        {
            $logger->addInfo(json_encode(array('ERROR' => 1, 'CODE_ERROR' => PASTA_ERROR_CHILDREN_SCRIPT, 'MESSAGE' => $process->getOutput()) ) );
        }*/
    }


}

?>