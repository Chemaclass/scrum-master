<?php

declare(strict_types=1);

namespace App\Tests\Unit\ScrumMaster\Command;

use App\ScrumMaster\Command\SlackNotifierCommand;
use App\ScrumMaster\Command\SlackNotifierInput;
use App\ScrumMaster\Jira\JiraHttpClient;
use App\ScrumMaster\Slack\SlackHttpClient;
use App\Tests\Unit\ScrumMaster\JiraApiResource;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SlackNotifierCommandTest extends TestCase
{
    use JiraApiResource;

    /** @test */
    public function zeroNotificationsWereSent(): void
    {
        $output = new InMemoryOutput();

        $command = new SlackNotifierCommand(
            new JiraHttpClient($this->createMock(HttpClientInterface::class)),
            new SlackHttpClient($this->createMock(HttpClientInterface::class))
        );

        $command->execute(SlackNotifierInput::fromArray([
            'COMPANY_NAME' => 'company',
            'JIRA_PROJECT_NAME' => 'project',
            'DAYS_FOR_STATUS' => '{"status":1}',
            'SLACK_MAPPING_IDS' => '{"jira.id":"slack.id"}',
        ]), $output);

        $this->assertEquals([
            'Total notifications: 0',
            'Total successful notifications sent: 0',
            'Total failed notifications sent: 0',
        ], $output->lines());
    }

    /** @test */
    public function twoSuccessfulNotificationsWereSent(): void
    {
        $output = new InMemoryOutput();

        $jiraIssues = [
            $this->createAnIssueAsArray('user.1.jira'),
            $this->createAnIssueAsArray('user.2.jira'),
        ];

        $command = new SlackNotifierCommand(
            new JiraHttpClient($this->mockJiraClient($jiraIssues)),
            new SlackHttpClient($this->createMock(HttpClientInterface::class))
        );

        $command->execute(SlackNotifierInput::fromArray([
            'COMPANY_NAME' => 'company',
            'JIRA_PROJECT_NAME' => 'project',
            'DAYS_FOR_STATUS' => '{"status":1}',
            'SLACK_MAPPING_IDS' => '{"jira.id":"slack.id"}',
        ]), $output);

        $this->assertContains('Total notifications: 2', $output->lines());
    }
}
