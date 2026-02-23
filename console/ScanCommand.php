<?php namespace RainLab\Translate\Console;

use Illuminate\Console\Command;
use RainLab\Translate\Classes\ThemeScanner;
use RainLab\Translate\Models\Message;

/**
 * ScanCommand
 */
class ScanCommand extends Command
{
    /**
     * @var string signature for the console command
     */
    protected $signature = 'translate:scan
        {--purge : First purge existing messages.}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Scan theme localization files for new messages.';

    /**
     * handle
     */
    public function handle()
    {
        if ($this->option('purge')) {
            $this->output->writeln('Purging messages...');
            Message::truncate();
        }

        ThemeScanner::scan();
        $this->output->success('Messages scanned successfully.');
        $this->output->note('You may need to run cache:clear for updated messages to take effect.');
    }

}
