<?php

namespace Pimgento\Import\Console\Command;

use \Magento\Framework\App\Area;
use \Magento\Framework\App\State;
use \Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Pimgento\Import\Model\Import as ImportModel;
use \Exception;

class PimgentoImportCommand extends Command
{
    public const IMPORT_CODE = 'code';
    public const IMPORT_FILE = 'file';
    protected ImportModel $_import;
    protected State $_appState;

    /**
     * PimgentoImportCommand constructor.
     *
     * @param ImportModel $import
     * @param State       $appState
     * @param null        $name
     */
    public function __construct(ImportModel $import, State $appState, $name = null)
    {
        parent::__construct($name);
        $this->_import = $import;
        $this->_appState = $appState;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pimgento:import')
            ->setDescription('Import PIM files to Magento')
            ->addOption(self::IMPORT_CODE, null, InputOption::VALUE_REQUIRED)
            ->addOption(self::IMPORT_FILE, null, InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->_appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
            $output->writeln('Area code already set');
        }

        $code = $input->getOption(self::IMPORT_CODE);
        $file = $input->getOption(self::IMPORT_FILE);

        if (!$code) {
            $this->_usage($output);
        } else {
            $this->_import($code, $file, $output);
        }

        return Cli::RETURN_SUCCESS;
    }

    protected function _import(string $code, string $file, OutputInterface $output): void
    {
        try {
            $import = $this->_import->load($code);
            $import->setFile($file)->setStep(0);

            while ($import->canExecute()) {
                $import->execute();

                $output->writeln($import->getComment());
                $output->writeln($import->getMessage());

                if (!$import->getContinue()) {
                    break;
                }

                $import->next();
            }
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    protected function _usage(OutputInterface $output): void
    {
        $imports = $this->_import->getCollection();

        /* Options */
        $output->writeln('<comment>' . __('Options:') . '</comment>');
        $output->writeln(' <info>--code</info>');
        $output->writeln(' <info>--file</info>');
        $output->writeln('');

        /* Codes */
        $output->writeln('<comment>' . __('Available codes:') . '</comment>');
        foreach ($imports as $import) {
            $output->writeln(' <info>' . $import->getCode() . '</info>');
        }
        $output->writeln('');

        /* Example */
        $import = $imports->getFirstItem();
        if ($import->getCode()) {
            $output->writeln('<comment>' . __('Example:') . '</comment>');
            $output->writeln(
                ' <info>pimgento:import --code=' . $import->getCode() . ' --file=' . $import->getCode() . '.csv</info>'
            );
        }
    }
}
