<?php

declare(strict_types=1);

namespace Chemaclass\ScrumMaster\Command;

use Chemaclass\ScrumMaster\Channel\ChannelInterface;
use Chemaclass\ScrumMaster\Channel\Slack\ChannelResult;
use Chemaclass\ScrumMaster\Jira\Board;
use Chemaclass\ScrumMaster\Jira\JiraHttpClient;
use Chemaclass\ScrumMaster\Jira\JqlUrlBuilder;
use Chemaclass\ScrumMaster\Jira\JqlUrlFactory;
use Chemaclass\ScrumMaster\Jira\ReadModel\Company;

final class NotifierCommand
{
    /** @var JiraHttpClient */
    private $jiraHttpClient;

    /** @var ChannelInterface[] */
    private $channels;

    public function __construct(JiraHttpClient $jiraHttpClient, array $channels)
    {
        $this->jiraHttpClient = $jiraHttpClient;
        $this->channels = $channels;
    }

    /** @return array<string, ChannelResult> */
    public function execute(NotifierInput $input): array
    {
        $jiraBoard = new Board($input->daysForStatus());
        $company = Company::withNameAndProject($input->companyName(), $input->jiraProjectName());
        $result = [];

        foreach ($this->channels as $channel) {
            $result[get_class($channel)] = $channel->sendNotifications(
                $jiraBoard,
                $this->jiraHttpClient,
                $company,
                new JqlUrlFactory($jiraBoard, JqlUrlBuilder::inOpenSprints($company)),
                $input->jiraUsersToIgnore()
            );
        }

        return $result;
    }
}
