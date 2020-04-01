<?php

namespace Gbo\PhpGithubCli;

use Github\Client;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class GithubCommandWebhook extends GithubCommand
{
    
    /**
     * Default execute, this allow for parsing of github's API output
     * in a central place
     *
     * The real exec is therefore githubExec
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $githubOutput = $this->githubExec($input, $output);
        
        # Set sender information
        $sender = self::$githubClient->api('current_user')->show();
        $githubOutput['datas']['sender'] = $sender;
        
        # Set Repo information
        $repo = self::$githubClient->api('repo')->show($input->getArgument('org'),  $input->getArgument('repo'));
        unset ($repo['permissions']);
        $githubOutput['datas']['repository'] = $repo;
        
        # Retrieve webhookURL
        $hooks = self::$githubClient->api('repo')->hooks()->all($input->getArgument('org'), $input->getArgument('repo'));
        foreach($hooks as $hook){
            if (strpos($hook['config']["url"], $input->getArgument('pl_url')) === 0){
                $hookURL = $hook['config']["url"];
                break;
            }
        }
        # Send request to webhook
        $b=new \Github\HttpClient\Builder();
        $response = $b->getHttpClient()->post($hookURL, $githubOutput['header'], json_encode($githubOutput['datas']));
        if ($response->getStatusCode() !== 200) {
            $response->message = "Drone return code is " . $response->getStatusCode();
        }
    }

    /**
     * Add some default options and call githubConfigure()
     */
    protected function configure()
    {
        $this->addOption(
            'output-format',
            'of',
            InputOption::VALUE_REQUIRED,
            'Output format (human, json)',
            'human'
        );
        $this->githubConfigure();
    }


}
