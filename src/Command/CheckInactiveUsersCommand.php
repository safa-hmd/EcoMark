<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\UserActivityService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:check-inactive-users',
    description: 'Check inactive users and mark them or send email'
)]
class CheckInactiveUsersCommand extends Command
{
    private UserRepository $userRepository;
    private UserActivityService $activityService;

    public function __construct(UserRepository $userRepository, UserActivityService $activityService)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->activityService = $activityService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            if ($this->activityService->isUserInactive($user, 15)) {
                $output->writeln($user->getEmail() . ' is inactive');
            }
        }

        return Command::SUCCESS;
    }
}