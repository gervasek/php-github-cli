<?php
namespace Gbo\PhpGithubCli\Module\Webhook;

use Gbo\PhpGithubCli\GithubCommandWebhook;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Github\HttpClient\Message\ResponseMediator;

use Github\HttpClient\Builder;


class PullRequest extends GithubCommandWebhook
{

    /**
     * Symfony cli module config
     */
    protected function githubConfigure()
    {
        $this
            ->setName('webhook:pull-request')
            ->setDescription('Trigger pull-resquest event to webhook')
            ->addArgument('org', InputArgument::REQUIRED, 'Repo owner')
            ->addArgument('repo', InputArgument::REQUIRED, 'Repo name')
            ->addArgument('pl_url', InputArgument::REQUIRED, 'Payload URL')
            ->addArgument('pr_id', InputArgument::REQUIRED, 'Pull request ID');
    }

    /**
     * githubExec implementation
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     * @throws \Github\Exception\MissingArgumentException
     */
    protected function githubExec(InputInterface $input, OutputInterface $output)
    {
        $DRONE_SERVER="https://drone.fpfis.eu";
        
        $REQUEST_HEADER = [
          "User-Agent" => "GitHub-Hookshot/f221634",
          "Content-Type" => "application/json",
          "X-GitHub-Event" => "pull_request",
        ];
        
        $pr = self::$githubClient->api('pull_request')->show(
            $input->getArgument('org'), 
            $input->getArgument('repo'),
            $input->getArgument('pr_id')
        );
        
        $DATAS = [
            "action" => "synchronize",
            "number" => $input->getArgument('pr_id'),
            "pull_request" => $pr
        ];
        
        $repo = self::$githubClient->api('repo')->show($input->getArgument('org'),  $input->getArgument('repo'));
        unset ($repo['permissions']);
        $DATAS['repository'] = $repo;
        
        return [ "datas" => $DATAS, "header" => $REQUEST_HEADER];
    }
}
