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

    private function get_opts()
    {
        $opts = Options::get_group( 'rabbitmq' );

        $opts = Plugins::filter( 'rabbitmq_options', $opts );

        return $opts;
    }

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

        $ui->append('text', 'host', 'option:rabbitmq__host')->label( _t('Host', 'rabbitmq') );
        $ui->append('text', 'port', 'option:rabbitmq__port')->label( _t('Port', 'rabbitmq') );
        $ui->append('text', 'user', 'option:rabbitmq__user')->label( _t('User', 'rabbitmq') );
        $ui->append('text', 'pass', 'option:rabbitmq__pass')->label( _t('Password', 'rabbitmq') );


        $ui->append( 'submit', 'save', _t( 'Save' ) );
        $ui->out();
    }

    public function action_queue_send( $routing, $message )
    {
        $message = new PhpAmqpLib\Message\AMQPMessage( json_encode( $message ) );
        $this->channel()->basic_publish($message, $routing['exchange'], $routing['key']);
    }

    private function set_defaults()
    {
        Options::set_group( 'rabbitmq', array(
            'host' => '127.0.0.1',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/'
        ));
    }

    private function connection()
    {
        if ( !isset( $this->_connection ) ) {
            $opts = $this->get_opts();
            $this->_connection = new PhpAmqpLib\Connection\AMQPConnection($opts['host'], $opts['port'], $opts['user'], $opts['pass'], $opts['vhost']);
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