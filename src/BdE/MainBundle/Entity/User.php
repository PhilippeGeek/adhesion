<?php
/**
 * Created by PhpStorm.
 * User: pvienne
 * Date: 14/08/15
 * Time: 22:46
 */

namespace BdE\MainBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Users")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $azureAccessToken;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    public function setAzureAccessToken($accessToken)
    {
        $this->azureAccessToken=$accessToken;
    }
}