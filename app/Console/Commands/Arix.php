<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class Arix extends Command
{
    protected $signature = 'arix {action?}';
    protected $description = 'All commands for Arix Theme for Pterodactyl.';

    public function handle()
    {
        $action = $this->argument('action');

        $title = new OutputFormatterStyle('#fff', null, ['bold']);
        $this->output->getFormatter()->setStyle('title', $title);

        $b = new OutputFormatterStyle(null, null, ['bold']);
        $this->output->getFormatter()->setStyle('b', $b);

        if ($action === null) {
            $this->line("
            <title>
            ░█████╗░██████╗░██╗██╗░░██╗
            ██╔══██╗██╔══██╗██║╚██╗██╔╝
            ███████║██████╔╝██║░╚███╔╝░
            ██╔══██║██╔══██╗██║░██╔██╗░
            ██║░░██║██║░░██║██║██╔╝╚██╗
            ╚═╝░░╚═╝╚═╝░░╚═╝╚═╝╚═╝░░╚═╝

           Thank you for purchasing Arix</title>

           > php artisan arix (this window)
           > php artisan arix install
           > php artisan arix update
           > php artisan arix uninstall
            ");
        } elseif ($action === 'install') {
            $this->install();
        } elseif ($action === 'update') {
            $this->update();
        } elseif ($action === 'uninstall') {
            $this->uninstall();
        } else {
            $this->error("Invalid action. Supported actions: install, update, uninstall");
        }
    }

    public function installOrUpdate($isUpdate=false){if($isUpdate){$this->info(base64_decode('DQogICAgVGhpcyBjb21tYW5kIGlzIG5vdCByZWNvbW1lbmRlZCB0byB1c2UuIA0KICAgIFRoaXMgY29tbWFuZCBza2lwcyBmcmVxdWVudGx5IHVzZWQgZmlsZXMgYnkgYWRkb25zIGR1cmluZyB0aGVtZSB1cGRhdGluZyB0byBhdm9pZCBsb3NpbmcgeW91ciBhZGRvbiBjdXN0b21pemF0aW9ucy4NCiAgICBJZiB5b3Ugc3RpbGwgZXhwZXJpZW5jZSBhbiBlcnJvciBhZnRlciB1cGRhdGluZyBwbGVhc2UgY29udGFjdCB1cy4='));}$confirmation=$this->confirm(base64_decode('QXJlIGFsbCB0aGUgcmVxdWlyZWQgZGVwZW5kZW5jaWVzIGluc3RhbGxlZCBmcm9tIHRoZSByZWFkbWUgZmlsZT8='),base64_decode('eWVz'));if(!$confirmation){return;}$seed='ARc83c0fcf3213436cca43069cc9b456e8';$endpoint=base64_decode('aHR0cHM6Ly9hcGkuYXJpeC5nZy9yZXNvdXJjZS9hcml4LXB0ZXJvZGFjdHlsL3ZlcmlmeQ==');$respond=base64_decode('c3VjY2Vzcw==');$response=Http::asForm()->post($endpoint,[base64_decode('bGljZW5zZQ==')=>$seed,]);$responseData=$response->json();if(!$responseData[$respond]){return $this->error(base64_decode('RmF0YWw6IENhbGwgdG8gdW5kZWZpbmVkIG1ldGhvZCBDbGFzc05hbWU6OmFyaXhNZXRob2QoKSBpbiBQdGVyb2RhY3R5bC5waHAgb24gbGluZSA4Mw=='));}$versions=File::directories(base64_decode('Li9hcml4'));if(empty($versions)){$this->info(base64_decode('Tm8gdmVyc2lvbnMgZm91bmQgaW4gL2FyaXggZGlyZWN0b3J5Lg=='));return;}$version=basename($this->choice(base64_decode('U2VsZWN0IGEgdmVyc2lvbjo='),$versions));$this->info("Installing Arix Theme $version...");$excludeOption=$isUpdate?base64_decode('LS1leGNsdWRlPVwncm91dGVzLnRzXCcgLS1leGNsdWRlPVwnZ2V0U2VydmVyLnRzXCcgLS1leGNsdWRlPVwnYWRtaW4uYmxhZGUucGhwXCcgLS1leGNsdWRlPVwnYWRtaW4ucGhwXCcgLS1leGNsdWRlPVwnU2VydmVyVHJhbnNmb3JtZXIucGhwXCc='):'';exec("rsync -a $excludeOption arix/{$version}/ ./");$directoryPath=app_path(base64_decode('SHR0cC9Db250cm9sbGVycy9BZG1pbi9Bcml4'));File::makeDirectory($directoryPath,0755,true,true);$filesOne=[base64_decode('QXJpeENvbnRyb2xsZXI='),base64_decode('QXJpeEFkdmFuY2VkQ29udHJvbGxlcg=='),base64_decode('QXJpeEFubm91bmNlbWVudENvbnRyb2xsZXI='),base64_decode('QXJpeENvbG9yc0NvbnRyb2xsZXI='),];$this->info(base64_decode('UHJvY2VlZGluZyB3aXRoIHRoZSBpbnN0YWxsYXRpb24uLi4='));$filesTwo=[base64_decode('QXJpeENvbXBvbmVudHNDb250cm9sbGVy'),base64_decode('QXJpeExheW91dENvbnRyb2xsZXI='),base64_decode('QXJpeE1haWxDb250cm9sbGVy'),base64_decode('QXJpeE1ldGFDb250cm9sbGVy'),base64_decode('QXJpeFN0eWxpbmdDb250cm9sbGVy'),];$this->info(base64_decode('TWlncmF0aW5nIGRhdGFiYXNlLi4u'));$this->command(base64_decode('cGhwIGFydGlzYW4gbWlncmF0ZSAtLWZvcmNl'));$this->info(base64_decode('SW5zdGFsbGluZyByZXF1aXJlZCBwYWNrYWdlcy4uLg=='));$this->info(base64_decode('VGhpcyBjYW4gdGFrZSBhIG1pbnV0ZS4uLg=='));$this->command(base64_decode('eWFybiBhZGQgQHR5cGVzL21kNSBtZDUgcmVhY3QtaWNvbnNANS40LjAgQHR5cGVzL2JiY29kZS10by1yZWFjdCBiYmNvZGUtdG8tcmVhY3QgaTE4bmV4dC1icm93c2VyLWxhbmd1YWdlZGV0ZWN0b3JANy4yLjE='));foreach($filesOne as $file){$this->aa($file,$version,$seed,$directoryPath);sleep(1);}foreach($filesTwo as $file){$this->aa($file,$version,$seed,$directoryPath);sleep(1);}$this->info(base64_decode('QnVpbGRpbmcgcGFuZWwgYXNzZXRzLi4u'));$this->info(base64_decode('VGhpcyBjYW4gdGFrZSBhIG1pbnV0ZS4uLg=='));$nodeVersion=shell_exec(base64_decode('bm9kZSAtdg=='));$nodeVersion=(int)ltrim($nodeVersion,base64_decode('dg=='));if($nodeVersion>=17){$this->info(base64_decode('Tm9kZS5qcyB2ZXJzaW9uIGlzIHY=').$nodeVersion.base64_decode('ICg+PSAxNyk='));putenv(base64_decode('ZXhwb3J0IE5PREVfT1BUSU9OUz0tLW9wZW5zc2wtbGVnYWN5LXByb3ZpZGVy'));}else{$this->info(base64_decode('Tm9kZS5qcyB2ZXJzaW9uIGlzIHY=').$nodeVersion.base64_decode('ICg8IDE3KQ=='));}$this->command(base64_decode('eWFybiBidWlsZDpwcm9kdWN0aW9u'));$this->info(base64_decode('U2V0IHBlcm1pc3Npb25zLi4u'));$this->command(base64_decode('Y2hvd24gLVIgd3d3LWRhdGE6d3d3LWRhdGEg').base_path().base64_decode('Lyo='));$this->command(base64_decode('Y2hvd24gLVIgbmdpbng6bmdpbngg').base_path().base64_decode('Lyo='));$this->command(base64_decode('Y2hvd24gLVIgYXBhY2hlOmFwYWNoZSA=').base_path().base64_decode('Lyo='));$this->info(base64_decode('T3B0aW1pemUgYXBwbGljYXRpb24uLi4='));$this->command(base64_decode('cGhwIGFydGlzYW4gb3B0aW1pemU6Y2xlYXI='));$this->command(base64_decode('cGhwIGFydGlzYW4gb3B0aW1pemU='));$message=$isUpdate?base64_decode('4pSCICAgIOKVreKUgOKVtCAgVGhlbWUgdXBkYXRlZCAgIOKVtuKUgOKVriAgIOKUgg=='):base64_decode('4pSCICAgIOKVreKUgOKVtCBUaGVtZSBpbnN0YWxsZWQgIOKVtuKUgOKVriAgIOKUgg==');$this->line("
            ╭───────────────────────────────╮
            │                               │
            $message
            │    ╰─╴   successfully   ╶─╯   │
            │                               │
            ╰───────────────────────────────╯
        ");}private function aa($filename,$version,$seed,$directoryPath){$url=base64_decode('aHR0cHM6Ly9kb3dubG9hZHMuYXJpeC5nZy8=').$version.base64_decode('Lw==').$filename.base64_decode('LnBocD9zZWVkPQ==').$seed;$response=Http::get($url);if($response->successful()){$filePath=$directoryPath.base64_decode('Lw==').$filename.base64_decode('LnBocA==');File::put($filePath,$response->body());}else{$this->error(base64_decode('RmFpbCwgcGxlYXNlIGNvbnRhY3QgV2VpamVycy5vbmUu'));}}public function install(){$this->installOrUpdate();}public function update(){$this->installOrUpdate(true);}
    
        
    private function uninstall()
    {
        $this->line("Uninstalling...");
        $this->command('php artisan down');
        $this->command('curl -L https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz | tar -xzv');
        $this->command('chmod -R 755 storage/* bootstrap/cache');
        $this->command('composer install --no-dev --optimize-autoloader');
        $this->command('php artisan view:clear');
        $this->command('php artisan config:clear');
        $this->command('php artisan config:clear');
        $this->command('php artisan migrate --seed --force');
        $this->command('chown -R www-data:www-data ' . base_path() . '/*');
        $this->command('chown -R nginx:nginx ' . base_path() . '/*');
        $this->command('chown -R apache:apache ' . base_path() . '/*');
        $this->command('php artisan queue:restart');
        $this->command('php artisan up');
    }

    private function command($cmd)
    {
        return exec($cmd);
    }

}
