<?php

/** 
 * LICENSE: ##LICENSE##
 * 
 * @category   Anahita
 * @package    Com_Subscriptions
 * @subpackage Domain_Behavior
 * @author     Arash Sanieyan <ash@anahitapolis.com>
 * @author     Rastin Mehr <rastin@anahitapolis.com>
 * @copyright  2008 - 2010 rmdStudio Inc./Peerglobe Technology Inc
 * @license    GNU GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @version    SVN: $Id: resource.php 11985 2012-01-12 10:53:20Z asanieyan $
 * @link       http://www.anahitapolis.com
 */

/**
 * Subscriber Behavior. Mixed in with Person entity
 *
 * @category   Anahita
 * @package    Com_Subscriptions
 * @subpackage Domain_Behavior
 * @author     Arash Sanieyan <ash@anahitapolis.com>
 * @author     Rastin Mehr <rastin@anahitapolis.com>
 * @license    GNU GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @link       http://www.anahitapolis.com
 */
 class ComSubscriptionsDomainBehaviorSubscriber extends AnDomainBehaviorAbstract
 { 		
    /**
	 * Initializes the default configuration for the object
	 *
	 * Called from {@link __construct()} as a first step of object instantiation.
	 *
	 * @param KConfig $config An optional KConfig object with configuration options.
	 *
	 * @return void
	 */
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
			'relationships' => array(
				'subscription' => array(
				    'type' => 'has',    					
					'child'	=> 'com:subscriptions.domain.entity.subscription'
				)
			)
		));
		
		parent::_initialize($config);
	}
	
 	/**
	 * Upgrades a person current subscription to a new one. It calculates the duration
	 * difference of the old package and new package and adds to the remaining time
	 *
	 * @param  ComSubscriptionsDomainEntityPackage $package
	 * @return ComSubscriptionsDomainEntitySubscription
	 */
	public function changeSubscriptionTo( $package )
	{
		if ( $this->hasSubscription(false) && !$this->_mixer->subscription->package->eql( $package ) ) 
		{
			$diff = max( 0, $package->duration - $this->subscription->package->duration );
			
			$end_date = clone $this->subscription->endDate;
			
			$end_date->addSeconds( $diff );
            
            $this->_mixer->subscription->package = $package;
            $this->_mixer->subscription->endDate = $end_date;
		}

        return $this->_mixer->subscription;
	}
		
	/**
	 * Subscribe to  a package
	 *
	 * @param  ComSubscriptionsDomainEntityPackage $package
	 * @return void
	 */
	public function subscribeTo( $package ) 
	{
        if( !$this->hasSubscription( false ) )
        {    
    		$this->_mixer->subscription = $this->getService('repos://site/subscriptions.subscription')->getEntity(
    		array(
    		  'data'=>array( 				
     		     'package' => $package 		
     		)));           
        }
 		
 	 	return $this->_mixer->subscription;
	}
	
    /**
     * Check if the person has subcription
     * 
     * @param if true also check to see if the subscription hasn't expired yet
     * @return boolean
     */
    public function hasSubscription( $checkValidity = true )
    {
        if( !isset( $this->_mixer->subscription ) )
        {
            return false;
        }    
            
        if ( $checkValidity && $this->_mixer->subscription->getTimeLeft() <= 0 )
        {
            return false;
        }    
            
        return true;
    }
	
	/**
	 * Unsubscribe the person 
	 *	 
	 * @return void
	 */
	public function unsubscribe() 
	{    
		$this->_mixer->subscription->delete();
	}
	
	/**
	 * Return whether the person is subscribed to a package or not 
	 *
	 * @param  ComSubscriptionsDomainEntityPackage $package
	 * @return boolean
	 */
	public function isSubscribedTo( $package )
	{
		return $this->hasSubscription() && $this->_mixer->subscription->package->eql( $package ); 
	}
}