<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\DomCrawler\Crawler;
use AppBundle\Entity\Distributor;

class ImportCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('app:distributor:import')
            ->setDescription('Import from json')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Import base path'
            )
        ;
    }

    protected function getManager() 
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path'); //Importing dir

        $finder = new Finder();
        $files = $finder->files()->in($path)->depth('== 0')->name('*.json');

        $time_start = microtime(true);
        
        foreach ($files as $file) {
            $data = file_get_contents($file->getRealpath());
            $json = json_decode($data,true);

            foreach ($json['results']['collection1'] as $result ) {

                $obj = new Distributor();
                $obj->setAddress($result['address']);
                // $obj->setTown($result['row2_value_2']);
                // $obj->setProvince($result['row2_value_1']);
                $obj->setName($result['address']);
                $obj->setLink($result['url']);
                $obj->setOpeningHours($result['opening_hours']);
                //$obj->setStatus($result['row2_value_4']=='Aperto' ? 1 : 0 );
                $output_array = array();
                preg_match_all("/([-+]?[0-9]*\.?[0-9]+)/", $result['location'], $output_array);

                $obj->setLat($output_array[0][0]);
                $obj->setLng($output_array[0][1]);

                $this->getManager()->persist($obj);
            }
        }

        $this->getManager()->flush();

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $output->writeln( "Importing executed in " . $time . " seconds"  );
    }   
}