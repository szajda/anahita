<?php 
/**
 * @category	Com_Subscriptions
 * @package		Controller
 * @copyright   (C) 2008 - 2010 rmdStudio Inc. and Peerglobe Technology Inc. All rights reserved.
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @link        http://www.GetAnahita.com
 */

/**
 * Subscription Controller
 * 
 * @package	Com_Subscriptions
 * @controller		Controller
 */
class ComSubscriptionsControllerSubscription extends ComBaseControllerService
{
    /**
     * The subscription order
     * 
     * @var ComSubscriptionsDomainEntityOrder
     */
    protected $_order;
    
    /**
     * The gateway
     *
     * @var ComSubscriptionsDomainPaymentGatewayInterface
     */    
    protected $_gateway;
    
    /** 
     * Constructor.
     *
     * @param KConfig $config An optional KConfig object with configuration options.
     * 
     * @return void
     */ 
    public function __construct( KConfig $config )
    {
        parent::__construct( $config );
        
        if( !$config->gateway instanceof ComSubscriptionsDomainPaymentGatewayInterface )
        {
            $config->gateway = $this->getService( $config->gateway ); 
        }
            
        $this->_gateway = $config->gateway;
        
        $this->registerCallback( 'after.add', array( $this, 'mailInvoice' ) );
    }
    
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
        $config->append( array (
        
            'serviceable'=> array('except'=>array('browse', 'read', 'edit')),
            'behaviors' => array('com://site/mailer.controller.behavior.mailer'),
            'gateway' => 'com://site/subscriptions.domain.payment.gateway.paypal'
        
        ) );
    
        parent::_initialize($config);
    }
        
    /**
     * Adds a subscription
     *
     * @param KCommandContext $context
     *
     * @return void
     */    
    protected function _actionAdd( KCommandContext $context )
    {    
        $payload = $this->_order->getPayload();
                
        if( !$this->_gateway->process( $payload ) ) 
        {
            throw new ComSubscriptionsDomainPaymentException('Payment error. Check the log');
        }
        
        $person  = $this->_order->getSubscriber();
        
        $package = $this->_order->getPackage();
        
        $set = new AnObjectSet();
        
        $set->insert($this->_order);
        
        if( !$person->persisted() ) 
        {            
            $person->reset();
            
            $user = $person->getJUserObject();
            
            //encrypt the password
            $user->set('password', $person->getPassword( true ));

            $user->set('block', '0');
            
            $user->save();
            
            $person = $this->getService('repos://site/people.person')->find(array('userId'=>$user->id));
            
            if ( $person ) 
            {
                $set->insert($person);
                
                $this->_order->setSubscriber($person);                
            }
        }

        if ( !$package->recurring && $person->hasSubscription() )
        {
            $subscription = $person->changeSubscriptionTo( $package );    
        }
        else
        {
            $subscription = $person->subscribeTo( $package );
        }    
        
        $set->insert( $subscription );
        
        if ( $payload->getRecurring() )
        {
            $subscription->setValue( 'profileId', $payload->getRecurring()->profile_id );
            
            $subscription->setValue( 'profileStatus', $payload->getRecurring()->profile_status );
        }
        
        if ( !$this->commit() ) 
        {
            $set->delete();
            
            $this->commit();
            
            throw new RuntimeException("Subscription can not be added");
        }
        
        $this->getResponse()->status = KHttpResponse::CREATED;

        dispatch_plugin('subscriptions.onAfterSubscribe', array( 'subscription' => $subscription ));
        
        $this->setItem( $subscription );
        
        return $subscription;        
    }
    
    /**
     * Mail an invoice after adding a subscription
     * 
     * @param KCommandContext $context
     * 
     * @return void
     */
    public function mailInvoice(KCommandContext $context)
    {
        if ( $this->getItem() ) 
        {                       
            $this->mail(array(
                'to' => $this->getItem()->person->email,
                'subject' => JText::_('COM-SUBSCRIPTIONS-CONFIRMATION-MESSAGE-SUBJECT'),
                'template' => 'invoice'
            ));
        }
    }
    
    /**
     * Return the payment gateway
     * 
     * @return ComSubscriptionsDomainPaymentGatewayInterface
     */
    public function getGateway()
    {
        return $this->_gateway;
    }
    
    /**
     * Sets the package
     * 
     * @param ComSubscriptionsDomainEntityOrder $order
     * 
     * @return void
     */
    public function setOrder($order)
    {
        $this->_order = $order; 
        $this->_state->order = $order;
        
        return $this;
    }
    
    /**
     * Return the order
     * 
     * @return ComSubscriptionsDomainEntityOrder
     */
    public function getOrder()
    {
        return $this->_order;
    }
 }
 
 