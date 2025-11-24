<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateConditions extends Command
{
    protected $signature = 'vizzbud:update-all';
    protected $description = 'Run all Vizzbud data refresh commands sequentially';

    public function handle()
    {
        $this->call('vizzbud:fetch-conditions');
        $this->call('vizzbud:fetch-forecast');
        $this->call('vizzbud:build-dayparts');

        $this->info('All Vizzbud data updated successfully!');
    }
}