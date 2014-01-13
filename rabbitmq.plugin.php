<?php

if ( !defined( 'HABARI_PATH' ) ) {
	die( 'No direct access' );
}

require_once __DIR__ . '/vendor/autoload.php';

class HabariRabbitMQ extends Plugin
{
    private $_connection;
    private $_channel;
    private $_queues = array(); // internal cache of queues we have declared

    /**
     * function action_plugin_activation
     *
     * if the file being passed in is this file, sets the default options
     *
     * @param $file string name of the file
     */
    public function action_plugin_activation( $file )
    {
        if ( realpath( $file ) == __FILE__ ) {
            $this->set_defaults();
        }
    }

    /**
     * Build main plugin configuration form
     * @return void
     */
    public function configure()
    {
        $ui = new FormUI( 'Hbook' );

        $ui->append('text', 'host', 'option:rabbitmq__host', _t('Host', 'rabbitmq') );
        $ui->append('text', 'port', 'option:rabbitmq__port', _t('Port', 'rabbitmq') );
        $ui->append('text', 'user', 'option:rabbitmq__user', _t('User', 'rabbitmq') );
        $ui->append('text', 'pass', 'option:rabbitmq__pass', _t('Password', 'rabbitmq') );
        $ui->append('text', 'pass', 'option:rabbitmq__vhost', _t('Virtual Host', 'rabbitmq') );


        $ui->append( 'submit', 'save', _t( 'Save' ) );
        $ui->out();
    }

    public function action_queue_send( $queue, $message )
    {
        if (!$this->has_queue( $queue ) ) {
            $this->make_queue( $queue );
        }

        $message = new PhpAmqpLib\Message\AMQPMessage( $message );
        $this->channel()->basic_publish($message, '', $queue);
    }

    /*
        name: name of the queue
        passive: false
        durable: true // the queue will survive server restarts
        exclusive: false // the queue can be accessed in other channels
        auto_delete: false //the queue won't be deleted once the channel is closed.
    */
    private function make_queue( $name, $passive = false, $durable = true, $exclusive = false, $auto_delete = false )
    {
        $this->_queues[ $name ] = $this->channel()->queue_declare($name, $passive, $durable, $exclusive, $auto_delete);
    }

    private function has_queue( $name )
    {
        return isset( $this->_queues[ $name ] );
    }

    private function set_defaults()
    {
        Options::set_group( 'rabbitmq', array(
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest'
        ));
    }

    private function connection()
    {
        if ( !isset( $this->_connection ) ) {
            $opts = Options::get_group( 'rabbitmq' );
            $this->_connection = new PhpAmqpLib\Connection\AMQPConnection($opts['host'], $opts['port'], $opts['user'], $opts['pass']. $opts['vhost']);
        }
        return $this->_connection;
    }

    private function channel()
    {
        if ( !isset( $this->_channel ) ) {
            $this->_channel = $this->connection()->channel();
        }
        return $this->_channel;
    }
}

?>