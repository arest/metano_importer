<?php

namespace AppBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\ElasticaBundle\Event\TransformEvent;
use Elastica\Type;
use Elastica\Document;
use Symfony\Component\Locale\Locale;

class DistributorListener implements EventSubscriberInterface
{
    protected $geocode;

    public function __construct( $geocode )
    {
        $this->geocode = $geocode;
    }

    public function addCustomProperty(TransformEvent $event)
    {
        $object = $event->getObject();
        $document = $event->getDocument();

        $document->addGeoPoint('location', $object->getLat(), $object->getLng() );

        if (!$object->getProvince()) {
            $locale = 'it';
            $countries = Locale::getDisplayCountries($locale);
            $country = $countries[ $object->getCountry() ];
            $address = $object->getAddress().' '.$object->getTown().' '.$country;
            try {
                $result = $this->geocode
                           ->using('google_maps')
                           ->reverse( $object->getLat(), $object->getLng() )
                ;
                $document->set('town', $result->getCity() );
                $document->set('zipcode', $result->getZipcode() );
                $document->set('province', $result->getCountyCode() );
                $document->set('country', $result->getCountryCode() );
                $document->set('address', $result->getStreetName() . ' '.  $result->getStreetNumber() );
                $document->set('region', $result->getRegion() );

            } catch (\Exception $e) {
                print $object->getId().'::'.$e->getMessage()."\n";
            }
        }
    }


    public static function getSubscribedEvents()
    {
        return array(
            TransformEvent::POST_TRANSFORM => 'addCustomProperty',
        );
    }
}