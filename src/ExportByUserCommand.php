<?php namespace Acme;

use Illuminate\Support\Arr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportByUserCommand extends Command
{

    /**
     *
     */
    public function configure()
    {
        $this->setName("export")
            ->setDescription("Exports logs from a given User")
            ->addArgument('your_user', InputArgument::REQUIRED, 'Username to fetch database logs')
            ->addArgument('other_user', InputArgument::REQUIRED, 'Username used to find the logs to export')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, "Output format: json, csv, screen", 'screen')
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, "Destination folder for the output", './skype-log-<username>-<user>')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('your_user');
        $user = $input->getArgument('other_user');
        $format = $input->getOption('format');
        $destination = $this->getDestination($input);

        $output->writeln("<info>Exporting logs to {$username}");

        $this->outputByFormat($output, $username, $user, $format, $destination);
    }

    /**
     * @param array $data
     * @param array $columns
     * @return array
     */
    private function processResult(array $data, array $columns = [])
    {
        $format = "Y-m-d H:i:s";

        foreach($data as $index => $row)
        {
            $row['date'] = date($format, $row['timestamp']);
            $row['body_xml'] = html_entity_decode($row['body_xml']);
            $row['body_short'] = substr($row['body_xml'], 0, 45);
            if ( !empty($columns) )
                $data[$index] = Arr::only($row, $columns);
            else
                $data[$index] = $row;
        }

        return $data;
    }

    /**
     * @param OutputInterface $output
     * @param $username
     * @param $user
     * @param $format
     * @param $destination
     */
    private function outputByFormat(OutputInterface $output, $username, $user, $format, $destination)
    {
        $skypeDB = new SkypeDatabase(SkypeDatabase::constructPath($username));
        $data = $skypeDB->logsByUser($user);

        switch($format)
        {
            case "json":
                $this->toJson($output, $data, $destination);
                break;

            case "csv":
                $this->toCsv($output, $data, $destination);
                break;

            default:
                $this->toScreen($output, $data);
        }

    }

    /**
     * @param OutputInterface $output
     * @param $data
     */
    private function toScreen(OutputInterface $output, $data)
    {
        $result = $this->processResult($data, ['author', 'date', 'body_short']);
        $table = new Table($output);

        $table->setHeaders(['Author','Date', 'Body'])
            ->setRows($result)
            ->render();
    }

    /**
     * @param $output
     * @param $data
     * @param $destination
     */
    private function toJson($output, $data, $destination)
    {
        $data = $this->processResult($data);
        $destination = $destination . '.json';
        file_put_contents($destination, json_encode($data));
        $output->writeln("<info>Done, file generated at '{$destination}'</info>");
    }

    /**
     * @param $output
     * @param $data
     * @param $destination
     */
    private function toCsv($output, $data, $destination)
    {
        $data = $this->processResult($data);
        $destination = $destination . '.csv';

        $handle = fopen($destination, "w+");
        fputcsv($handle, array_keys(reset($data)), ',');

        foreach($data as $row)
        {
            fputcsv($handle, $row, ',');
        }
        fclose($handle);

        $output->writeln("<info>Done, file generated at '{$destination}'</info>");
    }

    /**
     * @param InputInterface $input
     * @return mixed
     */
    private function getDestination(InputInterface $input)
    {
        $destination = $input->getOption('destination');
        $destination = str_replace("<username>", $input->getArgument('your_user'), $destination);
        $destination = str_replace("<user>", $input->getArgument('other_user'), $destination);

        return $destination;
    }
}