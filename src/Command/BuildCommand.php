<?php

namespace WhatsappClient\Command;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use PhpParser\BuilderFactory;
use PhpParser\PrettyPrinter;
use PhpParser\Node;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WhatsappClient\Client;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build application.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Download JS dependency...');

        $wapi = file_get_contents('https://raw.githubusercontent.com/mukulhase/WebWhatsapp-Wrapper/master/webwhatsapi/js/wapi.js');
        $wapi = str_replace(
            'webpackChunkbuild.push([',
            '(window.webpackChunkbuild || window.webpackChunkwhatsapp_web_client).push([',
            $wapi
        );
        file_put_contents(
            __DIR__ . '/../webwhatsapi/js/wapi.js',
            $wapi
        );

        $output->writeln('Setting annotations...');

        $capabilities = DesiredCapabilities::firefox();
        $Client = new Client('http://selenium-hub:4444/wd/hub', $capabilities);
        $Client->sessionStart();

        $availableFunctions = $Client->WsapiWrapper->getAvailableFunctions();

        $methods = ' * @method ' . implode("\n * @method ", $availableFunctions);

        $class = <<<CLASS
            <?php

            namespace WhatsappClient\webwhatsapi;

            /**
            $methods
             */
            class JSAdapter
            {
            }
            CLASS;
        file_put_contents(__DIR__ . '/../webwhatsapi/JSAdapter.php', $class);
        $output->writeln('Done!');
        return Command::SUCCESS;
    }
}
