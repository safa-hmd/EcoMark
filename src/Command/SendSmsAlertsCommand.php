<?php

namespace App\Command;

use App\Service\SmsAlertService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:sms:send-alerts',
    description: 'Scanner les stocks et envoyer les alertes SMS'
)]
class SendSmsAlertsCommand extends Command
{
    private SmsAlertService $smsService;

    public function __construct(SmsAlertService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📱 Scan des Stocks & Alertes SMS');

        $rapport = $this->smsService->scannerEtAlerterStocksCritiques();

        $io->section('Configuration');
        $io->text([
            'Mode: ' . ($rapport['mode'] === 'REEL' ? '🔵 REEL (Twilio)' : '🟡 SIMULATION'),
            'Produits scannés: ' . $rapport['total_produits'],
            'Date: ' . $rapport['date_scan']->format('d/m/Y H:i:s')
        ]);

        if (empty($rapport['produits_critiques'])) {
            $io->success('✅ Aucun produit critique détecté');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($rapport['produits_critiques'] as $alerte) {
            $rows[] = [
                $alerte['produit'],
                '<fg=red>' . $alerte['stock'] . '</>',
                $alerte['sms_envoyes'],
                implode(', ', $alerte['destinataires']),
                $alerte['statut']
            ];
        }

        $io->table(
            ['Produit', 'Stock', 'SMS', 'Destinataires', 'Statut'],
            $rows
        );

        $io->success([
            "📤 Résumé:",
            "SMS envoyés: " . $rapport['total_sms_envoyes'],
            "Produits critiques: " . count($rapport['produits_critiques']),
            "Mode: " . $rapport['mode']
        ]);

        return Command::SUCCESS;
    }
}
