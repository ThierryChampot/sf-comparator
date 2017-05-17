<?php

namespace AppBundle\Feed;

use AppBundle\Entity\Merchant;
use AppBundle\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class Reader
 * @package AppBundle\Feed
 * @author  Etienne Dauvergne <contact@ekyna.com>
 */
class Reader
{
    /**
     * @var EntityManagerInterface
     */
    private $em;


    /**
     * Constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Reads the merchant's feed and creates or update the resulting offers.
     *
     * @param Merchant $merchant
     *
     * @return int The number of created or updated offers.
     */
    public function read(Merchant $merchant)
    {
        // $count = 0;
        $count=0;

        // Lire le flux de données du marchand
        $content = file_get_contents($merchant->getFeedUrl());
        var_dump($content);

        // Convertir les données JSON en tableau
        $array = json_decode($content, true); // true pour obtenir des tableaux associatifs
        var_dump($array);

        // Pour chaque couple de données "code ean / prix"
        foreach ($array as $data) {
            $count ++;

            // Utiliser le product repository pour trouver le produit correspondant au code ean
            $eanCode =$data['ean_code'];
            var_dump($eanCode);

            $price = $data['price'];
            var_dump($price);

            $product= $this->em
                ->getRepository('AppBundle:Product')
                ->findOneBy(['eanCode' => $eanCode]);
            var_dump($product);

            // Trouver le produit correspondant
            if(!$product){
                continue; // Sinon passer à l'itération suivante
            } 
            
            // Trouver l'offre correspondant à ce produit et ce marchand
            $offer= $this->em
                ->getRepository('AppBundle:Offer')
                ->findOneBy([
                    'product' => $product,
                    'merchant' => $merchant
                    ]);

                if (!$offer){
                // Sinon créer l'offre
                    $datetime = new \DateTime();
                    var_dump($datetime);

                    $offer = new Offer();
                    var_dump($offer);

                    $offer
                    ->setMerchant($merchant)
                    ->setProduct($product);
                    }

            // Mettre à jour le prix et la date de mise à jour de l'offre
            $offer
            ->setPrice($price)
            ->setUpdatedAt( $datetime );

            var_dump($offer);

            // Enregistrer l'offre et incrémenter le compteur d'offres
            $this->em->persist($offer);

            $this->em->flush();
        }
        // Renvoyer le nombre d'offres
        return $count;
    }
}
