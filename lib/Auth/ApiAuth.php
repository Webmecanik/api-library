<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     MIT http://opensource.org/licenses/MIT
 */

namespace Mautic\Auth;

/**
 * OAuth Client modified from https://code.google.com/p/simple-php-oauth/
 */
class ApiAuth
{
    /**
     * Get an API Auth object
     *
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return $this
     *
     * @deprecated
     */
    public static function initiate($parameters = array(), $authMethod = 'OAuth')
    {
        $object = new self;

        return $object->newAuth($parameters, $authMethod);
    }

    /**
     * Get an API Auth object
     *
     * @param array  $parameters
     * @param string $authMethod
     *
     * @return $this
     */
    public function newAuth($parameters = array(), $authMethod = 'OAuth')
    {

        echo " newAuth on \n";

        $class      = 'Mautic\\Auth\\'.$authMethod;
        $authObject = new $class();

        $reflection = new \ReflectionMethod($class, 'setup');
        $pass       = array();

        echo " reflection->getParameters => ".print_r($reflection->getParameters(), true);

        foreach ($reflection->getParameters() as $param) {
            if (isset($parameters[$param->getName()])) {
                $pass[] = $parameters[$param->getName()];
            } else {
                echo "param is null";
                $pass[] = null;
            }
        }

        echo " pass = ".print_r($pass, true);

        $reflection->invokeArgs($authObject, $pass);

        echo " newAuth off \n";

        return $authObject;
    }
}
